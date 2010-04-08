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
 * Dynamic DNS server plugin
 *
 *
 * @author Sebastiaan Gibbon
 * @version 0.0
 */

class Dyndns implements Plugin {
	
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
	 *
	 * @param PluginFramework $framework Framework object, containing all information and plugins.
	 * @param Config $config Object with System Configuration
	 * @param int $runtype Running mode of the script. Can be PluginFramework::RUNTYPE_STARTUP or PluginFramework::RUNTYPE_BROWSER
	 */
	public function __construct($framework, $config, $options, $runtype) {
		$this->config = $config;
		$this->runtype = $runtype;
		$this->framework = $framework;
		
		//get config
		$this->data = $this->config->getElement ( 'dyndns' );
	}
	
	/**
	 * Is the Plugin a service?
	 * 
	 * @return bool
	 */
	public function isService() {
		return false;
	}
	
	/**
	 * Start the service
	 * 
	 * @return bool false when service failed to start
	 */
	public function start() {
		require_once PluginFramework::MODULE_PATH . '/Dyndns/updatedns.php';
		
		$interface = $this->framework->getPlugin ( "Wan" );
		if (empty ( $interface )) {
			Logger::getRootLogger ()->error ( 'Could not get WAN plugin.' );
			return false;
		}
		$interfaceIP = $interface->getIpAddress ();
		//TODO: The Wan IP adress is not the internet adress?
		
		$update = new updatedns ( $interfaceIP, //$wan_ip
									$this->data->client->type, //$dnsService
									$this->data->client->host, //$dnsHost
									$this->data->client->username, //$dnsUser
									$this->data->client->password, //$dnsPass
									$this->data->client->wildcards, //$dnsWildcard
									$this->data->client->mx, //$dnsMX
									'', //$dnsBackMX
									'', //$dnsWanip
									$this->data->client->server, //$dnsServer
									$this->data->client->port, //$dnsPort
									'' //$dnsUpdateURL
								); 
		}
	
	/**
	 * Stop the service
	 * 
	 * @return bool false when service failed to stop
	 */
	public function stop() {
	
	}
	
	/**
	 * Write configuration to the system
	 * 
	 * @return int Returns status of configuration. 0 is ok, 1 or higher means an error occurd.
	 */
	public function configure() {
		//TODO: Do something with the server data. It's in the XML but updatedns does not need it.
		//$this->data->server->host = $_POST ['host'];
		//$this->data->server->ttl = $_POST ['ttl'];
		//$this->data->server->key = $_POST ['key'];
		//$this->data->server->key ['name'] = $_POST ['name'];
		//$this->data->server->protocol = $_POST ['protocol'];
		return 0;
	}
	
	/**
	 * Starts the plugin
	 */
	public function runAtBoot() {
		Logger::getRootLogger ()->info ( "Init Dyndns" );
		if (( string ) $this->data ['enable'] == 'true') {
			$result = $this->configure ();
			//Don't start if config fails.
			if ($result < 1) {
				$this->start ();
			} else {
				Logger::getRootLogger ()->info ( "Dynamic DNS not starting due to configuration errors." );
			}
		}
	}
	
	/**
	 * Get info for a front-end page
	 */
	public function getPage() {
		if (isset ( $_POST ['page'] )) {
			switch ($_POST ['page']) {
				case 'getconfig' :
					echo '<reply action="ok">';
					echo $this->data->asXML ();
					echo '</reply>';
					break;
				case 'save' :
					$this->save ();
					break;
				default :
					throw new Exception ( "page request not valid" );
			}
		} else {
			throw new Exception ( "page request not valid" );
		}
	}
	
	/**
	 * Save DHCPD settings 
	 * After saving the config, the DHCPD server will be restarted. 
	 */
	private function save() {
		
		if (isset ( $_POST ['services_dyndns_enable'] ) && ($_POST ['services_dyndns_enable'] == 'true' || $_POST ['services_dyndns_enable'] == 'enabled')) {
			//Turn on the plugin
			$this->data ['services_dyndns_enable'] = 'true';
		} elseif (isset ( $_POST ['enable'] )) {
			//turn off the plugin
			$this->data ['services_dyndns_enable'] = 'false';
		}
		
		if (isset ( $_POST ['services_dyndns_type'] )) {
			//Update Dyndns settings
			$this->data->client->type = $_POST ['services_dyndns_type'];
			$this->data->client->username = $_POST ['services_dyndns_username'];
			$this->data->client->password = $_POST ['services_dyndns_password'];
			$this->data->client->server = $_POST ['services_dyndns_server'];
			$this->data->client->port = $_POST ['services_dyndns_port'];
			$this->data->client->host = $_POST ['services_dyndns_hostname'];
			$this->data->client->mx = $_POST ['services_dyndns_mx'];
			$this->data->client->wildcards = $_POST ['services_dyndns_wildcards'];
		}
		
		//Save config and print the data
		if ($this->config->saveConfig ()) {
			//restart httpd.
			echo '<reply action="ok">';
			echo $this->data->asXML ();
			echo '</reply>';
		} else {
			//The config file could not be written.
			throw new Exception ( "Error, could not save configuration file." );
		}
	}
	
	/**
	 * Gets a list of dependend plugins
	 */
	public function getDependency() {
	
	}
	
	/**
	 * Starts the plugin
	 * 
	 * @return string Status of the service/plugin
	 */
	public function getStatus() {
		return ( string ) $this->data ['enable'];
	}
	
	/**
	 * Shutsdown the Plugin.
	 * Called at program shutdown. 
	 */
	public function shutdown() {
		$this->stop ();
	}
}
?>