<?php
require_once(PluginFramework::FRAMEWORK_PATH.'/libs/Interfaces.php');
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
 * Plugin to configure Ext interfaces
 * 
 * @author Rick Woelders
 * @version 1.0
 */
class Ext extends Interfaces {
	/**
	 * 	Webinterface access control list
	 * 
	 * 	@access private
	 * 	@var 	Array
	 */
	private $acl = array('ROOT','OP');
	
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
			if (stristr ( ( string ) $interface['type'], 'Ext' )) {
				$this->data [] = $interface;
				break;
			}
		}
	}
	
	/**
	 * Configure $interface
	 * 
	 * @param integer $interface	Interface number
	 */
	public function configure($interface = null) {
		if ($interface >= 1) {
			//		Configure specific interface
			$this->logger->info ( 'Setting up EXT['.($interface).'] interface: ' . ( string ) $this->data[$interface - 1]->if );
			$this->stop ();
			
			//		Set MTU
			if (( string ) $this->data[$interface - 1]->mtu != '') {
				Functions::shellCommand ( "/sbin/ifconfig " . ( string ) $this->data[$interface - 1]->if . " mtu " . ( string ) $this->data[$interface - 1]->mtu );
			}
			
			//		Spoof mac address
			if (Functions::isMacAddress ( ( string ) $this->data[$interface - 1]->mac )) {
				Functions::shellCommand ( "/sbin/ifconfig " . escapeshellarg ( $this->data[$interface - 1]->if ) . " link " . escapeshellarg ( $this->data[$interface - 1]->mac ) );
			} else {
				$mac = $this->getMacAddress ();
				if ($mac == "ff:ff:ff:ff:ff:ff") {
					/*   this is not a valid mac address. */
					$this->logger->info ( "Generating new MAC address for WAN interface." );
					$random_mac = $this->generateMacAddress();
					Functions::shellCommand ( "/sbin/ifconfig " . escapeshellarg ( $this->data[$interface - 1]->if ) . " link " . escapeshellarg ( $random_mac ) );
					$this->data->mac = $random_mac;
					
					$this->config->saveConfig();
					$this->logger->info ( "The invalid MAC(ff:ff:ff:ff:ff:ff) on interface " . ( string ) $this->data[$interface - 1]->if . " has been replaced with " . $random_mac );
				}
			}
		
			//		Set IP address
			if (( string ) $this->data[$interface - 1]->ipaddr == 'dhcp') {
				$this->configureDHCP ();
			} elseif (Functions::is_ipAddr ( ( string ) $this->data[$interface - 1]->ipaddr )) {
				Functions::shellCommand ( "/sbin/ifconfig " . ( string ) $this->data[$interface - 1]->if . " " . ( string ) $this->data[$interface - 1]->ipaddr . "/" . ( string ) $this->data[$interface - 1]->subnet );
			}
		}
	}
	
	/**
	 * configure DHCPD for $interface
	 * 
	 * @param integer $interface Interface number
	 */
	public function configureDHCP($interface = null) {
		if ($interface >= 1) {
			$fd = fopen ( "/var/etc/dhclient_" . ( string ) $this->data [$interface - 1]->if . ".conf", "w" );
			if (! $fd) {
				$this->logger->error ( "Cannot open dhclient_" . ( string ) $this->data [$interface - 1]->if . ".conf for writing.\n" );
				return false;
			}
			
			if (( string ) $this->data [$interface - 1]->dhcphostname != '') {
				$dhclientconf_hostname = "send dhcp-client-identifier \"" . ( string ) $this->data [$interface - 1]->dhcpdhostname . "\";\n";
				$dhclientconf_hostname .= "\tsend host-name \"" . ( string ) $this->data [$interface - 1]->dhcpdhostname . "\";\n";
			} else {
				$dhclientconf_hostname = "";
			}
			
			$dhclientconf = "";
			
			$dhclientconf .= "timeout 60;\n
			retry 1;\n
			select-timeout 0;\n
			initial-interval 1;\n
			interface \"" . ( string ) $this->data [$interface - 1]->interface . "\" {\n
				{$dhclientconf_hostname}\n
				script \"/sbin/dhclient-script\";\n
			}";
			
			fwrite ( $fd, $dhclientconf );
			fclose ( $fd );
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Disable DHCPD for $interface
	 * 
	 * @param integer $interface Interface number
	 */
	public function disableDHCP($interface = null) {
		Functions::shellCommand ( "/sbin/ifconfig " . ( string ) $this->data [$interface - 1]->if . " down" );
		sleep ( 1 );
		$pid = Functions::shellCommand ( `ps awwwux | grep dhclient | grep -v grep | grep ` . ( string ) $this->data [$interface - 1]->if . ` | awk '{ print \$2 }'` );
		if (! empty ( $pid )) {
			Functions::shellCommand ( "kill {$pid}" );
		}
	}
	
	/**
	 * Enable DHCPD for $interface
	 * 
	 * @param integer $interface Interface number
	 */
	public function enableDHCP($interface = null) {
		/* fire up dhclient */
		Functions::shellCommand ( "/sbin/dhclient -c /var/etc/dhclient_" . ( string ) $this->data [$interface - 1]->if . ".conf " . ( string ) $this->data [$interface - 1]->if . " >/tmp/" . ( string ) $this->data [$interface - 1]->if . "_output >/tmp/" . ( string ) $this->data [$interface - 1]->if . "_error_output" );
	}
	
	/**
	 * 	Get this interface's IP address
	 * 
	 * @param Integer	$interface	Interface number
	 * @access public
	 * @return null|IpAddress
	 */
	public function getIpAddress($interface = 1) {
		if ($interface >= 1) {
			$tmp = Functions::shellCommand ( "/sbin/ifconfig " . ( string ) $this->data [$interface - 1]->if . " | /usr/bin/grep -w \"inet\" | /usr/bin/cut -d\" \" -f 2| /usr/bin/head -1" );
			$ip = str_replace ( "\n", "", $tmp );
			return $ip;
		} else {
			return null;
		}
	}
	
	/**
	 * Return $interface's mac address
	 * 
	 * @param Integer $interface	Interface number
	 * @returns String
	 * @access public
	 */
	public function getMacAddress($interface = null) {
		if ($interface >= 1) {
			$mac = Functions::shellCommand ( "ifconfig " . (( string ) $this->data [$interface - 1]->if) . " | awk '/ether/ {print $2}'" );
			if (Functions::isMacAddress ( $mac )) {
				return trim ( $mac );
			} else {
				return "";
			}
		} else {
			return "";
		}
	}
	
	/**
	 * Passthrough function to delegate AJAX frontend functionality
	 * 
	 * @access public
	 * @throws Exception
	 */
	public function getPage() {
		if(in_array($_SESSION['group'],$this->acl)){
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
				throw new Exception('Invalid page request');
			}
		}
		else{
			throw new Exception('You do not have permission to do this');
		}
	}
	
	/**
	 * Save EXT configuration XML based on input from AJAX frontend
	 * 
	 * @access private
	 * @throws Exception
	 */
	private function saveConfig($interface = 1){
		//		Check form input
		if($_POST['interfaces_ext_type'] != 'dhcp' && $_POST['interfaces_ext_type'] != 'static'){
			ErrorHandler::addError('formerror','interfaces_ext_type');
		}
		if(!empty($_POST['interfaces_ext_mac']) && !Functions::isMacAddress($_POST['interfaces_ext_mac'])){
			ErrorHandler::addError('formerror','interfaces_ext_mac');
		}
		if(!empty($_POST['interfaces_ext_mtu']) && !is_numeric($_POST['interfaces_ext_mtu'])){
			ErrorHandler::addError('formerror','interfaces_ext_mtu');
		}
		if($_POST['interfaces_ext_type'] == 'static' && !Functions::is_ipAddr($_POST['interfaces_ext_static_ipaddr'])){
			ErrorHandler::addError('formerror','interfaces_wan_static_ipaddr');
		}
		if($_POST['interfaces_ext_type'] == 'static' && !Functions::is_ipAddr($_POST['interfaces_ext_static_gateway'])){
			ErrorHandler::addError('formerror','interfaces_ext_static_gateway');
		}
		if($_POST['interfaces_ext_type'] == 'dhcp' && !Functions::is_hostname($_POST['interfaces_ext_dhcp_hostname'])){
			ErrorHandler::addError('formerror','interfaces_ext_dhcp_hostname');
		}
		if($_POST['interfaces_ext_type'] == 'static' && !Functions::is_ipAddr($_POST['interfaces_ext_static_subnetmask'])){
			ErrorHandler::addError('formerror','interfaces_ext_static_subnetmask');
		}
		
		//	Propagate exit on errors
		if(ErrorHandler::errorCount() > 0){
			throw new Exception('There is invalid form input');
		}
		
		//	Save actual configuration
		if(!empty($_POST['interfaces_ext_mac'])){
			$this->data[$interface -1]->mac = $_POST['interfaces_ext_mac'];
		}
		if(!empty($_POST['interfaces_ext_mtu'])){
			$this->data[$interface -1]->mtu = $_POST['interfaces_ext_mtu'];
		}
		
		if($_POST['interfaces_ext_type'] == 'dhcp'){
			$this->data[$interface -1]->ipaddr = 'dhcp';
			$this->data[$interface -1]->dhcphostname = $_POST['interfaces_ext_dhcp_hostname'];
		}
		elseif($_POST['interfaces_ext_type'] == 'static'){
			$this->data[$interface -1]->ipaddr = $_POST['interfaces_ext_static_ipaddr'];
			$this->data[$interface -1]->gateway = $_POST['interfaces_ext_static_gateway'];
			$this->data[$interface -1]->subnet = $_POST['interfaces_ext_static_subnetmask'];
		}
		echo '<reply action="ok">';
		echo $this->data[$interface -1]->asXML();
		echo '</reply>';
		$this->config->saveConfig();
	}
	
	/**
	 * Return EXT configuration XML
	 * 
	 * @access private
	 */
	private function getConfig($iface = null) {
		echo '<reply action="ok">';
		foreach($this->data as $interface){
			if($interface['type'] == 'Ext'.$iface){
				echo $interface->asXML();	
			}
		}
		echo '</reply>';
	}
	
	/**
	 * Get $interface's real interface name
	 * 
	 * @param Integer $interface	Interface number
	 * @access public
	 * @return String
	 */
	public function getRealInterfaceName($interface = null) {
		return ( string ) $this->data [$interface - 1]->if;
	}
	
	/**
	 * Initializes all interfaces during boot time
	 */
	public function runAtBoot() {
		$i = 1;
		while ( $i < count ( $this->data ) ) {
			$this->logger->info ( 'Initializing EXT[' . $i . '] interface' );
			$this->configure ( $i );
			$this->start ( $i );
		}
	}
	
	/**
	 * Bring $interface up and fire up DHCPclient if enabled
	 * 
	 * @param Integer $interface	Interface number
	 */
	public function start($interface = null) {
		if ($interface >= 1) {
			Functions::shellCommand ( "/sbin/ifconfig " . ( string ) $this->data [$interface - 1]->if . " up" );
			if (( string ) $this->data [$interface - 1]->ipaddr == 'dhcp') {
				$this->enableDHCP ( $interface );
			}
		}
	}
	
	/**
	 * Bring $interface down
	 * 
	 * @param Integer $interface	Interface number
	 */
	public function stop($interface = null) {
		if ($interface >= 1) {
			if (( string ) $this->data [$interface - 1]->ipaddr == 'dhcp') {
				$this->disableDHCP ( $interface );
			}
			Functions::shellCommand ( "/sbin/ifconfig " . ( string ) $this->data [$interface - 1]->if . " down" );
		}
	}
	
	/**
	 * Returns CIDR notation (0-32) subnet mask for the specified interface
	 * 
	 * @return Integer
	 * @param Integer $interface Interface number
	 */
	public function getSubnet($interface = null) {
		$real_if = $this->data[$interface - 1]->if;
		
		$tmp = Functions::shellCommand ( "/sbin/ifconfig " . ( string ) $real_if . " | /usr/bin/grep -w \"inet\" | /usr/bin/cut -d\" \" -f 2");
		$octets_hex = str_split($tmp,2);
		
		for ($i=2; $i < strlen($octets_hex); $i++) {
    		$str_bin .= decbin(hexdec($octets_hex[$i]));
  		}
  		
  		$subnet = strspn($str_bin,'1');
		
		return ( string ) $subnet;
	}
}
?>