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
 * Plugin to configure the WAN interface
 * 
 * @version 1.0
 */
class Wan extends Interfaces {
	
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
			if (( string ) $interface ['type'] == 'Wan') {
				$this->data = $interface;
				break;
			}
		}
	}
	
	/**
	 * Configure the WLAN interface
	 * 
	 * @access public
	 */
	public function configure() {
		$this->logger->info ( 'Setting up WAN interface: ' . ( string ) $this->data->if );
		$this->stop ();
		
		//		Set MTU
		if (( string ) $this->data->mtu != '') {
			Functions::shellCommand ( "/sbin/ifconfig " . ( string ) $this->data->if . " mtu " . ( string ) $this->data->mtu );
		}
		
		//		Spoof mac address
		if (Functions::isMacAddress ( ( string ) $this->data->mac )) {
			Functions::shellCommand ( "/sbin/ifconfig " . escapeshellarg ( $this->data->if ) . " link " . escapeshellarg ( $this->data->mac ) );
		} else {
			$mac = $this->getMacAddress ();
			if ($mac == "ff:ff:ff:ff:ff:ff") {
				/*   this is not a valid mac address. */
				$this->logger->info ( "Generating new MAC address for WAN interface." );
				$random_mac = $this->generateMacAddress ();
				Functions::shellCommand ( "/sbin/ifconfig " . escapeshellarg ( $this->data->if ) . " link " . escapeshellarg ( $random_mac ) );
				$this->data->mac = $random_mac;
				
				$this->config->saveConfig ();
				$this->logger->info ( "The invalid MAC(ff:ff:ff:ff:ff:ff) on interface " . ( string ) $this->data->if . " has been replaced with " . $random_mac );
			}
		}
		
		//		Set IP address
		if (( string ) $this->data->ipaddr == 'dhcp') {
			$this->configureDHCP ();
		} elseif (Functions::is_ipAddr ( ( string ) $this->data->ipaddr )) {
			Functions::shellCommand ( "/sbin/ifconfig " . ( string ) $this->data->if . " " . ( string ) $this->data->ipaddr . "/" . ( string ) $this->data->subnet );
			//		Default gateway
			if ($this->data->gateway != '') {
				if (Functions::is_ipAddr ( ( string ) $this->data->gateway )) {
					$return = Functions::shellCommand ( "/sbin/route add default " . escapeshellarg ( $this->data->gateway ) );
					if ($return != "") {
						$this->logger->error ( 'Could not add default gateway, route returned the following message: {' . $return . '}' );
					}
				} else {
					$this->logger->info ( 'Could not add default gateway from WAN (' . ( string ) $this->data->ipaddr . ') invalid address' );
				}
			}
		}
	}
	
	/**
	 * 	Configure DHCP client for the interface
	 * 
	 * 	@access public
	 */
	public function configureDHCP() {
		$fd = fopen ( "/var/etc/dhclient_" . ( string ) $this->data->if . ".conf", "w" );
		if (! $fd) {
			$this->logger->error ( "Cannot open dhclient_" . ( string ) $this->data->if . ".conf for writing.\n" );
			return false;
		}
		
		if (( string ) $this->data->dhcphostname != '') {
			$dhclientconf_hostname = "send dhcp-client-identifier \"" . ( string ) $this->data->dhcpdhostname . "\";\n";
			$dhclientconf_hostname .= "\tsend host-name \"" . ( string ) $this->data->dhcpdhostname . "\";\n";
		} else {
			$dhclientconf_hostname = "";
		}
		
		$dhclientconf = "";
		
		$dhclientconf .= "timeout 60;\n
		retry 1;\n
		select-timeout 0;\n
		initial-interval 1;\n
		interface \"" . ( string ) $this->data->interface . "\" {\n
			{$dhclientconf_hostname}\n
			script \"/sbin/dhclient-script\";\n
		}";
		
		fwrite ( $fd, $dhclientconf );
		fclose ( $fd );
		return true;
	}
	
	/**
	 * 	Disable DHCP client for the interface
	 * 
	 * 	@access public
	 */
	public function disableDHCP() {
		Functions::shellCommand ( "/sbin/ifconfig " . ( string ) $this->data->if . " down" );
		sleep ( 1 );
		$pid = Functions::shellCommand ( 'ps awux | grep dhclient | grep -v grep | grep ' . ( string ) $this->data->if . ' | awk \'{ print $2 }\'' );
		if (! empty ( $pid )) {
			Functions::shellCommand ( "kill {$pid}" );
		}
	}
	
	/**
	 * 	Enable DHCP client for the interface
	 * 
	 * 	@access public
	 */
	public function enableDHCP() {
		/* fire up dhclient */
		Functions::shellCommand ( "/sbin/dhclient -c /var/etc/dhclient_" . ( string ) $this->data->if . ".conf " . ( string ) $this->data->if . " >/tmp/" . ( string ) $this->data->if . "_output >/tmp/" . ( string ) $this->data->if . "_error_output" );
	}
	
	/**
	 * get front-end page info
	 * 
	 * @throws Exception
	 * @access public
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
	 * Save input from the AJAX GUI in the configuration XML
	 * 
	 * @access private
	 * @throws Exception
	 */
	private function saveConfig(){
		//		Check form input
		if($_POST['interfaces_wan_type'] != 'dhcp' && $_POST['interfaces_wan_type'] != 'static'){
			ErrorHandler::addError('formerror','interfaces_wan_type');
		}
		if(!empty($_POST['interfaces_wan_mac']) && !Functions::isMacAddress($_POST['interfaces_wan_mac'])){
			ErrorHandler::addError('formerror','interfaces_wan_mac');
		}
		if(!empty($_POST['interfaces_wan_mtu']) && !is_numeric($_POST['interfaces_wan_mtu'])){
			ErrorHandler::addError('formerror','interfaces_wan_mtu');
		}
		if($_POST['interfaces_wan_type'] == 'static' && !Functions::is_ipAddr($_POST['interfaces_wan_static_ipaddr'])){
			ErrorHandler::addError('formerror','interfaces_wan_static_ipaddr');
		}
		if($_POST['interfaces_wan_type'] == 'static' && !Functions::is_ipAddr($_POST['interfaces_wan_static_gateway'])){
			ErrorHandler::addError('formerror','interfaces_wan_static_gateway');
		}
		if($_POST['interfaces_wan_type'] == 'dhcp' && !Functions::is_hostname($_POST['interfaces_wan_dhcp_hostname'])){
			ErrorHandler::addError('formerror','interfaces_wan_dhcp_hostname');
		}
		
		//	Propagate exit on errors
		if(ErrorHandler::errorCount() > 0){
			throw new Exception('There is invalid form input');
		}
		
		//	Save actual configuration
		if(!empty($_POST['interfaces_wan_mac'])){
			$this->data->mac = $_POST['interfaces_wan_mac'];
		}
		if(!empty($_POST['interfaces_wan_mtu'])){
			$this->data->mtu = $_POST['interfaces_wan_mtu'];
		}
		
		if($_POST['interfaces_wan_type'] == 'dhcp'){
			$this->data->ipaddr = 'dhcp';
			$this->data->dhcphostname = $_POST['interfaces_wan_dhcp_hostname'];
		}
		elseif($_POST['interfaces_wan_type'] == 'static'){
			$this->data->ipaddr = $_POST['interfaces_wan_static_ipaddr'];
			$this->data->gateway = $_POST['interfaces_wan_static_gateway'];
		}
		echo '<reply action="ok">';
		echo $this->data->asXML();
		echo '</reply>';
		$this->config->saveConfig();
	}
	
	/**
	 * Return WAN configuration XML
	 * 
	 * @access private
	 */
	private function getConfig() {
		echo '<reply action="ok">';
		echo $this->data->asXML();
		echo '</reply>';
	}
	
	/**
	 * Configure plugin at boot-time
	 * 
	 * @access public
	 */
	public function runAtBoot() {
		if (isset ( $this->data )) {
			$this->configure ();
			$this->start ();
		} else {
			$this->logger->warn ( "No WAN config found." );
		}
	}
	
	/**
	 * Start the WAN interface
	 * 
	 * @access public
	 */
	public function start() {
		parent::start ();
		if (( string ) $this->data->ipaddr == 'dhcp') {
			$this->enableDHCP ();
		}
	}
	
	/**
	 * Stop the WAN interface
	 * 
	 * @access public
	 */
	public function stop() {
		if (( string ) $this->data->ipaddr == 'dhcp') {
			$this->disableDHCP ();
		}
		parent::stop ();
	}

}

?>