<?php
/*
 All rights reserved.
 Copyright (C) 2009-2010 GenericProxy <feedback@genericproxy.org>.
 All rights reserved.

 Redistribution and use in source and binary forms, with or without
 modification, are permitted provided that the following conditions are met:

 1. Redistributions of source code must retain the above copyright notice,
 this list of conditions and the following disclaimer.

 2. Redistributions in binary form must reproduce the above copyright
 notice, this list of conditions and the following disclaimer in the
 documentation and/or other materials provided with the distribution.

 THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * 	Update class
 * 
 * 	Handles update detection and notification for the appliance based
 * 	on update settings (when to check, and where to check for updates)
 *
 */
class Update implements Plugin{
	/**
	 * Contains a reference to the configuration object
	 * 
	 * @var Config
	 * @access private
	 */
	private $config;
	
	/**
	 * Contains the runtype, either boot or webgui
	 * 
	 * @access private
	 * @var Integer
	 */
	private $runtype;
	
	/**
	 * Contains reference to the plugin framework
	 * 
	 * @access private
	 * @var PluginFramework
	 */
	private $framework;
	
	/**
	 * Contains configuration data retrieved from $this->config
	 * 
	 * @access private
	 * @var SimpleXMLElement
	 */
	private $data;
	
	/**
	 * Is the Plugin a service?
	 * 
	 * @return bool
	 */
	public function isService() {
		return false;
	}
	
	/**
	 *
	 * @param PluginFramework $framework Framework object, containing all information and plugins.
	 * @param Config $config Object with System Configuration
	 * @param int $runtype Running mode of the script. Can be PluginFramework::RUNTYPE_STARTUP or PluginFramework::RUNTYPE_BROWSER
	 */
	public function __construct($framework, $config, $options, $runtype) {
		$this->config = $config;
		$this->runtype = $runtype;
		$this->framework = $framework;
		$this->plugin = $options;
		
		//get config
		$tmp = $this->config->getElement ( 'system' );
		$this->data = $tmp->update;
	}
	
	/**
	 * 	Code to run during device boot
	 * 
	 * 	This plugin does not run during boot
	 */
	public function runAtBoot() {}

	/**
	 * 	Get the status if this plugin's service
	 * 
	 * 	This plugin does not manage a service
	 */
	public function getStatus() {}

	/** 
	 * 	Passthrough function to delegate AJAX front-end functionality
	 * 
	 * 	@access public
	 * 	@throws Exception
	 */
	public function getPage() {
		switch($_POST['page']){
			case 'check':
				$this->checkForUpdates(true);
				break;
			case 'getconfig':
				$this->echoConfig();
				break;
			case 'save':
				$this->saveConfig();
				break;
			case 'updatefirmware':
				$this->updateFirmware();
				break;
			default:
				throw new Exception('Invalid page request');
		}
	}
	
	/**
	 * Automatically update Firmware
	 * 
	 * @access public
	 * @throws Exception
	 */
	public function updateFirmware(){
		echo '<reply action="ok" />';
		$data = $this->checkForUpdates('data');
		Logger::getRootLogger()->debug(print_r($data,true));
		
		if($data !== false){
			//		Set up a temporary ramdisk to download the new firmware into
			Logger::getRootLogger()->debug('Setting up ramdisk for firmware download');
			Functions::shellCommand('mdconfig -a -t swap -s 120M -u 10');
			Functions::shellCommand('newfs -U /dev/md10');
			Functions::shellCommand('mkdir /tmp/firmware');
			Functions::shellCommand('mount /dev/md10 /tmp/firmware');
			
			Logger::getRootLogger()->debug('ramdisk setup complete ');
			if(is_dir('/tmp/firmware')){
				//		Download the new firmware into the ramdisk
				Logger::getRootLogger()->debug('Changing directory');
				chdir('/tmp/firmware');
				Logger::getRootLogger()->debug('downloading the firmware ... ');
				Functions::shellCommand('wget http://'.$this->data->server.'/'.$data->filename);
				if(file_exists('/tmp/firmware/'.$data->filename)){
					//	TODO: Verify signature
					if($this->data->check_signature == 'false' || true){
						Logger::getRootLogger()->debug('verifying download hash');
						$hash = hash_file('sha256','/tmp/firmware/'.$data->filename);
						if($this->data->check_hash == 'false' || $hash == $data->hash){
							//	Execute the firmware upgrade
							//	Start notification led to signal upgrade is in progress
							if(is_dir('/dev/led')){
								Functions::shellCommand('/bin/echo 1 > /dev/led/error');
							}
							
							Functions::shellCommand('zcat /tmp/firmware/'.$data->filename.' | sh /root/updatep2');
							$this->framework->getPlugin('System')->reboot();
						}
						else{
							Logger::getRootLogger()->error('The hash of the downloaded file does not match');
							throw new Exception('The downloaded file is corrupt');
						}
					}
					else{
						Logger::getRootLogger()->error('The downloaded file has an invalid signature');
						throw new Exception('The downloaded file has an invalid signature');
					}
				}
				else{
					Logger::getRootLogger()->error('Error downloading http://'.$this->data->server.'/'.$data->filename);
					throw new Exception('Error downloading firmware file '.$data->filename);
				}
			}
			else{
				Logger::getRootLogger()->error('Could not create the ramdisk for the firmware');
				throw new Exception('Could not download the firmware');
			}
		}
		else{
			Logger::getRootLogger()->error('There is no firmware update available');
			throw new Exception('There is no firmware update available');
		}	
	}
	
	/**
	 * Checks for firmware updates
	 * 
	 * Checks using releases.xml on the specified update server
	 * If a newer version is found it returns the found XML for display in the
	 * AJAX frontend
	 * 
	 * @param bool returnXML Whether or not to echo the update XML, false if called internally
	 * @access public
	 * @throws Exception
	 */
	public function checkForUpdates($return = null){
		$xml = file_get_contents('http://'.((string)$this->data->server).'/releases.xml');
		if($xml !== false){
			$check = simplexml_load_string($xml);
			if($this->checkVersion($check->version)){
				if($this->runtype == PluginFramework::RUNTYPE_CLI){
					print_r($check);	
				}
				elseif($return == 'XML'){
					//	Add the current version to the reply XML, since the AJAX frontend is not aware of it
					$check->addChild('currentversion',PluginFramework::VERSION);
					echo '<reply action="ok">';
					$string =  $check->asXML();
					echo str_replace('<?xml version="1.0"?>','',$string);
					echo '</reply>';
					return true;
				}
				elseif($return == 'data'){
					return $check;
				}
				else{
					return true;
				}
			}
			else{
				//	No update was found
				if($return == 'XML'){
					echo '<reply action="ok" />';
				}
				return false;
			}
		}
		else{
			throw new Exception('Could not open releases.xml on the remote server');
		}
	}
	
	/**
	 * Compare $version with the hard-coded version number
	 * 
	 * @access private
	 * @return bool
	 * @param String $version
	 */
	private function checkVersion($version){
		if($version != PluginFramework::VERSION){
			//	Strings are not the same, so there's at least some update
			$cur_version = explode('.',PluginFramework::VERSION);
			$string = explode('.',$version);
			
			if(count($cur_version) < count($string)){
				//	new version has more keys, so probably an appendum of a minor or major version
				return true;
			}
			else{
				$i = 0;
				while($i < count($cur_version)){
					if($cur_version[$i] < $string[$i]){
						//	Higher number than the current version means something updated
						return true;	
					}
				}
				return false;
			}
		}
		else{
			return false;
		}
	}

	/**
	 * Get the dependencies of this module
	 * 
	 * AutoUpdate is not dependent on any plugins
	 * 
	 * @access public
	 * @return null
	 */
	public function getDependency(){
		return null;
	}

	/**
	 * 	Configure AutoUpdate service
	 * 
	 * 	 Because this does not run a daemon this function remains empty
	 */
	public function configure() {}
	
	/*
	 * Following functions remain empty as this plugin does not manage a service
	 */
	public function stop() {}
	public function start() {}
	public function shutdown() {}
	
}