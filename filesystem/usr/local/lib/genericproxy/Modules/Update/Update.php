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
	 * 	Webinterface access control list
	 * 
	 * 	@access private
	 * 	@var 	Array
	 */
	private $acl = array('ROOT','USR');
	
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
		if(in_array($_SESSION['group'],$this->acl)){
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
		else{
			throw new Exception('You do not have permission to do this');
		}
	}
	
	/**
	 * Determine the current active slice
	 * 
	 * Returns the slice the update should be written on
	 * to update.
	 * 
	 * @return int $slice 1|2
	 */
	private function getBootSlice(){
		$nano_drive = str_replace("\n",'',str_replace('NANO_DRIVE=','',file_get_contents('/etc/nanobsd.conf')));
		$check = Functions::shellCommand('mount | grep '.$nano_drive.'s2');

		if($check == ''){
			//	We're not on slice 2, so update it
			$slice = 2;
		}
		else{
			$slice = 1;
		}
		
		return $slice;
	}
	
	/**
	 * Automatically update Firmware
	 * 
	 * @access public
	 * @throws Exception
	 */
	public function updateFirmware(){
		$data = $this->checkForUpdates('data');
		Logger::getRootLogger()->debug((string)$data->filename);
		if($data !== false){
			Logger::getRootLogger()->debug((string)$data->filename);
			//		Set up a temporary ramdisk to download the new firmware into
			Logger::getRootLogger()->info('Setting up ramdisk for firmware download');
			Functions::shellCommand('mdconfig -a -t swap -s 120M -u 10');
			Functions::shellCommand('newfs -U /dev/md10');
			Functions::shellCommand('mkdir /tmp/firmware');
			Functions::shellCommand('mount /dev/md10 /tmp/firmware');
			
			if(file_exists('/tmp/firmware/'.$data->filename)){
				Logger::getRootLogger()->info('removing existing firmware file');
				unlink('/tmp/firmware/'.$data->filename);
			}
			
			if(is_dir('/tmp/firmware')){
				/*		Download the new firmware into the ramdisk
				 * 		system is used instead of Functions::shellCommand so the output is
				 * 		piped to stdout, which helped with debugging and provides useful
				 * 		information while upgrading from the shell
				 */
				chdir('/tmp/firmware');
				Logger::getRootLogger()->info('downloading the firmware ... ');
				system('wget http://'.$this->data->server.'/'.$data->filename,$output);
				if(file_exists('/tmp/firmware/'.$data->filename)){
					//	TODO: Verify signature
					if($this->data->check_signature == 'false' || true){
						if($this->data->check_hash == 'true'){
							Logger::getRootLogger()->info('Calculating download hash (can take a while)');
							$hash = hash_file('md5','/tmp/firmware/'.$data->filename);
						}
						if($this->data->check_hash == 'false' || $hash == $data->hash){
							//	Start notification led to signal upgrade is in progress
							if(is_dir('/dev/led')){
								Functions::shellCommand('/bin/echo 1 > /dev/led/error');
							}
							$slice = $this->getBootSlice();
							if($slice == 1 || $slice == 2){
								Logger::getRootLogger()->info('Flashing upgrade, do not power-down the device (can take a while)');
								Functions::shellCommand('zcat /tmp/firmware/'.$data->filename.' | sh /root/updatep'.$slice);
								$this->framework->getPlugin('System')->reboot();
							}
							else{
								throw new Exception('Could not determine the current boot slice!');
							}
						}
						else{
							throw new Exception('The downloaded file is corrupt');
						}
					}
					else{
						throw new Exception('The downloaded file has an invalid signature');
					}
				}
				else{
					throw new Exception('Error downloading firmware file '.$data->filename);
				}
			}
			else{
				throw new Exception('Could not download the firmware');
			}
		}
		else{
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
				if($return == 'XML'){
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
				elseif($this->runtype == PluginFramework::RUNTYPE_CLI){
					print_r($check);
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
			
			$i = 0;
			while($i < count($cur_version)){
				if(!isset($cur_version[$i])){
					//	Longer version string, minor version increase
					return true;
				}
				elseif($cur_version[$i] < $string[$i]){
					//	Higher number than the current version means something updated
					return true;	
				}
				$i++;
			}
			return false;
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