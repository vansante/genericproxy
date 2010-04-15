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
 * Interfaces class used by interfaces plugins 
 * 
 * Used by interface plugins like Lan, Wan and Ext
 * 
 * @abstract
 */
abstract class Interfaces implements Plugin {
	
	protected $config;
	protected $runtype;
	protected $framework;
	protected $logger;
	protected $data;
	
	/**
	 * 	Enable DHCP client for the interface
	 */
	public function enableDHCP() { }
	
	/**
	 * 	Disable DHCP client for the interface
	 */
	public function disableDHCP() {	}
	
	/**
	 * 	Stop the interface
	 * 
	 * 	@access public
	 */
	public function stop() {
		Functions::shellCommand ( "/sbin/ifconfig " . ( string ) $this->data->if . " down" );
	}
	
	/**
	 * 	Start the plugin
	 * 
	 *	@abstract
	 *	@access public
	 */
	public function runAtBoot(){}
	
	/**
	 * get front-end page info
	 * 
	 * @abstract
	 * @access public
	 */
	public function getPage(){}
	
	/**
	 *	Start the interface
	 *
	 *	@access public
	 */
	public function start() {
		Functions::shellCommand ( "/sbin/ifconfig " . ( string ) $this->data->if . " up" );
	}
	
	/**
	 *	Returns if this plugin runs a service
	 *
	 *	@access public
	 *	@return false
	 */
	public function isService() {
		return false;
	}
	
	/**
	 * 	Get service status
	 * 
	 *	@access public
	 *	@return false
	 */
	public function getStatus() {
		return false;
	}
	
	/**
	 * Get dependencies for this module
	 * 
	 * @access public
	 * @return null
	 */
	public function getDependency() {
		return null;
	}
	
	/**
	 * Configure this interface
	 * 
	 * @abstract
	 * @access public
	 */
	public function configure(){}
	
	/**
	 * Generates a random MAC address
	 * 
	 * @return String
	 * @access public
	 */
	public function generateMacAddress() {
		$mac = "02";
		for($x = 0; $x < 5; $x ++)
			$mac .= ":" . dechex ( rand ( 16, 255 ) );
		return $mac;
	}
	
	/**
	 * Get interface MAC address
	 * 
	 * @return String
	 * @access public
	 */
	public function getMacAddress() {
		$mac = Functions::shellCommand ( "ifconfig " . (( string ) $this->data->if) . " | awk '/ether/ {print $2}'" );
		if (Functions::isMacAddress ( $mac )) {
			return trim ( $mac );
		} else {
			return "";
		}
	}
	
	/**
	 * Return the real interface name
	 * 
	 * @access public
	 * @return String
	 */
	public function getRealInterfaceName() {
		if(!empty($this->data)){
			return ( string ) $this->data->if;
		}
		else{
			throw new Exception('Could not find the configuration data, or the config Object');
		}
	}
	
	/**
	 * Get IP address of the interface
	 * 
	 * @return String
	 * @access public
	 */
	public function getIpAddress() {
		$tmp = Functions::shellCommand ( "/sbin/ifconfig " . ( string ) $this->data->if . " | /usr/bin/grep -w \"inet\" | /usr/bin/cut -d\" \" -f 2| /usr/bin/head -1" );
		$ip = str_replace ( "\n", "", $tmp );
		return $ip;
	}
	
	/**
	 * Get the subnet mask (as int)
	 * 
	 * @return Int
	 * @access public
	 */
	public function getSubnet() {
		$str_bin = null;
		
		$tmp = Functions::shellCommand ( "/sbin/ifconfig " . ( string ) $this->data->if . " | /usr/bin/grep -w \"inet\" | /usr/bin/cut -d\" \" -f 2");
		$octets_hex = str_split($tmp,2);
		
		for ($i=2; $i < count($octets_hex); $i++) {
    		$str_bin .= decbin(hexdec($octets_hex[$i]));
  		}
  		
  		$subnet = strspn($str_bin,'1');
		
		return ( string ) $subnet;
	}
	
	/**
	 * Shutsdown the Plugin.
	 * 
	 * Called at program shutdown. 
	 *
	 */
	public function shutdown(){
		$this->stop();
	}
}

?>