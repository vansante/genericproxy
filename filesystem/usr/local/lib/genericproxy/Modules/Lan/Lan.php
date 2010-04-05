<?php
require_once (PluginFramework::FRAMEWORK_PATH . '/libs/Interfaces.php');
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
 * Plugin to set up the Lan interface
 */
class Lan extends Interfaces {
	
	/**
	 * Constructor
	 *
	 * @access public
	 * @param PluginFramework 	$framework 	plugin framework object reference
	 * @param Config 			$config 	Config object reference
	 * @param Integer			$runtype 	Run type, either boot or webgui
	 */
	public function __construct($framework, $config, $options, $runtype) {
		$this->config = $config;
		$this->runtype = $runtype;
		$this->framework = $framework;
		$this->logger = Logger::getRootLogger ();
		
		//      Get firewall XML configuration
		$tmp = $this->config->getElement ( 'interfaces' );
		foreach ( $tmp as $interface ) {
			if (( string ) $interface ['type'] == 'Lan') {
				$this->data = $interface;
				break;
			}
		}
	}
	
	/**
	 * Configure this interface
	 * 
	 * @access public
	 */
	public function configure() {
		//	Set IP address
		Functions::shellCommand ( "/sbin/ifconfig " . ( string ) $this->data->if . " " . ( string ) $this->data->ipaddr . "/" . ( string ) Functions::mask2prefix($this->data->subnet ));
	}
	
	/**
	 * Disabled DHCP Client for this interface
	 * 
	 * Returns false for Lan because this interface cannot do DHCP
	 * 
	 * @access public
	 * @return false;
	 */
	public function disableDHCP() {
		return false;
	}
	
	/**
	 * Enable DHCP Client for this interface
	 * 
	 * Returns false for Lan because this interface cannot do DHCP
	 * 
	 * @access public
	 * @return false
	 */
	public function enableDHCP() {
		return false;
	}
	
	/**
	 * Start the plugin
	 * 
	 * @access public
	 */
	public function runAtBoot() {
		if (isset ( $this->data )) {
			$this->configure ();
			$this->start ();
		} else {
			$this->logger->warn ( "No LAN config found." );
		}
	}
	
	/**
	 * Give page information to the front-end
	 * 
	 * @access public
	 * @throws Exception
	 */
	public function getPage() {
		if (isset ( $_POST ['page'] )) {
			switch ($_POST ['page']) {
				case 'getconfig' :
					$this->getConfig();
					break;
				case 'save':
					$this->saveConfig();
					break;
				default:
					throw new Exception('Invalid page request');
					break;
			}
		} else {
			$this->logger->error ( 'A page was requested without a page identifier' );
			throw new Exception('Invalid page request');
		}
	}

	/**
	 * 
	 * @access private
	 * @throws Exception
	 */
	private function saveConfig(){
		if(!Functions::is_ipAddr($_POST['interfaces_lan_ipaddr'])){
			ErrorHandler::addError('formerror','interfaces_lan_ipaddr');
		}
		if(!Functions::is_ipAddr($_POST['interfaces_lan_subnetmask'])){
			ErrorHandler::addError('formerror','interfaces_lan_subnetmask');
		}
		if(!empty($_POST['interfaces_lan_mtu']) && !is_numeric($_POST['interfaces_lan_mtu'])){
			ErrorHandler::addError('interfaces_lan_mtu');
		}
		
		//		Halt on error
		if(ErrorHandler::errorCount() > 0){
			throw new Exception('There is invalid form input');
		}
		
		$this->data->ipaddr = $_POST['interfaces_lan_ipaddr'];
		$this->data->subnet = $_POST['interfaces_lan_subnetmask'];
		if(!empty($_POST['interfaces_lan_mtu'])){
			$this->data->mtu = $_POST['interfaces_lan_mtu'];
		}
		
		echo '<reply action="ok">';
		echo $this->data->asXML();
		echo '</reply>';
		$this->config->saveConfig();
	}
	
	/**
	 * Return LAN configuration XML
	 * 
	 * @access private
	 */
	private function getConfig() {
		echo '<reply action="ok">';
		echo $this->data->asXML();
		echo '</reply>';
	}
}
?>