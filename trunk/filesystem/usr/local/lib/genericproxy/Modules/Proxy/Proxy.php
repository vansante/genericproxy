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
 * Proxy server plugin
 *
 * Manages the proxy service (TinyProxy) installed on the device
 *
 * @author Sebastiaan Gibbon
 * @version 0.0
 * @uses Plugin
 */

class Proxy implements Plugin {
	
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
	 * path and filename to the dhcpd config file
	 * 
	 * @var string
	 */
	const CONFIG_PATH = '/var/etc/proxy.conf';
	
	/**
	 * Path and filename to the dhcpd PID file
	 * 
	 * @var string
	 */
	const PID_PATH = '/var/run/tinyproxy.pid';
	
	/**
	 * Class Constructor
	 * 
	 * @param PluginFramework $framework Framework object, containing all information and plugins.
	 * @param Config $config Object with System Configuration
	 * @param int $runtype Running mode of the script. Can be PluginFramework::RUNTYPE_STARTUP or PluginFramework::RUNTYPE_BROWSER
	 */
	public function __construct($framework, $config, $options, $runtype) {
		$this->config = $config;
		$this->runtype = $runtype;
		$this->framework = $framework;
		
		//get Proxy config
		$this->data = $this->config->getElement ( 'proxy' );
	}
	
	/**
	 * Is the Plugin a service?
	 * 
	 * @return bool
	 */
	public function isService() {
		return true;
	}
	
	/**
	 * Start the service
	 * 
	 * @return bool false when service failed to start
	 */
	public function start() {
		$pid = file_exists ( self::PID_PATH ) ? Functions::shellCommand ( "pgrep -F " . self::PID_PATH ) : 0;
		if ($pid > 0) {
			Logger::getRootLogger ()->info ( 'Proxy server was already running' );
			return true;
		}
		
		Logger::getRootLogger ()->info ( "Starting Proxy server" );
		
		//Check if the config file exists.
		if (! file_exists ( self::CONFIG_PATH )) {
			Logger::getRootLogger ()->error ( 'Config file not found. Aborting Proxy server startup.' );
			return false;
		}
		
		Functions::shellCommand ( "/usr/local/sbin/tinyproxy -c " . self::CONFIG_PATH );
		return true;
	}
	
	/**
	 * Stop the service
	 * 
	 * @return bool false when service failed to stop
	 */
	public function stop() {
		Logger::getRootLogger ()->info ( "Stopping Proxy server" );
		$pid = file_exists ( self::PID_PATH ) ? Functions::shellCommand ( "pgrep -F " . self::PID_PATH ) : 0;
		if ($pid > 0) {
			Functions::shellCommand ( "/bin/kill {$pid}" );
		}
	}
	
	/**
	 * Write configuration to the system
	 * 
	 * @return int Returns status of configuration. 0 is ok, 1 or higher means an error occurd.
	 */
	public function configure() {
		Logger::getRootLogger ()->info ( "Configuring Proxy server" );
		Functions::mountFilesystem ( 'w' );
		
		//Write away the DHCPD config.
		$fd = fopen ( self::CONFIG_PATH, "w" );
		if (! $fd) {
			Logger::getRootLogger ()->error ( "Error: Could not write Proxy conifg to " . self::CONFIG_PATH );
			return 1;
		}
		
		$interface = $this->framework->getPlugin ( "Ext" );
		if (empty ( $interface )) {
			Logger::getRootLogger ()->error ( 'Could not get LAN plugin.' );
			return false;
		}
		
		$interfaceIP = $interface->getIpAddress (1);
		
		$proxyconf = "PidFile \"" . self::PID_PATH . "\"\n";
		$proxyconf .= <<<EOD
# tinyproxy daemon configuration file

# Name of the user the tinyproxy daemon should switch to after the port
# has been bound.
User nobody
Group nogroup

# Port to listen on.
Port {$this->data->port}

# If you have multiple interfaces this allows you to bind to only one. If
# this is commented out, tinyproxy will bind to all interfaces present.
Listen $interfaceIP

# Timeout: The number of seconds of inactivity a connection is allowed to
# have before it closed by tinyproxy.
Timeout 600

# Where to log the information. Either LogFile or Syslog should be set,
# but not both.
# Logfile "/var/log/tinyproxy.log" OR Syslog On
#LogLevel Warning

# Include the X-Tinyproxy header, which has the client's IP address when
# connecting to the sites listed.
#XTinyproxy mydomain.com

# This is the absolute highest number of threads which will be created. In
# other words, only MaxClients number of clients can be connected at the
# same time.
MaxClients 100

# These settings set the upper and lower limit for the number of
# spare servers which should be available. If the number of spare servers
# falls below MinSpareServers then new ones will be created. If the number
# of servers exceeds MaxSpareServers then the extras will be killed off.
MinSpareServers 2
MaxSpareServers 20

# Number of servers to start initially.
StartServers 5

# MaxRequestsPerChild is the number of connections a thread will handle
# before it is killed. In practise this should be set to 0, which disables
# thread reaping. If you do notice problems with memory leakage, then set
# this to something like 10000
MaxRequestsPerChild 0

# The following is the authorization controls. If there are any access
# control keywords then the default action is to DENY. Otherwise, the
# default action is ALLOW.
#
# Also the order of the controls are important. The incoming connections
# are tested against the controls based on order.
# Allow all as we only listen to one interface. 
#Allow 127.0.0.1
#Allow 192.168.1.0/25

# The "Via" header is required by the HTTP RFC, but using the real host name
# is a security concern.  If the following directive is enabled, the string
# supplied will be used as the host name in the Via header; otherwise, the
# server's host name will be used.
ViaProxyName "tinyproxy"

# If an Anonymous keyword is present, then anonymous proxying is enabled.
# The headers listed are allowed through, while all others are denied. If
# no Anonymous keyword is present, then all header are allowed through.
# You must include quotes around the headers.
#Anonymous "Host"
#Anonymous "Authorization"

# This is a list of ports allowed by tinyproxy when the CONNECT method
# is used.  To disable the CONNECT method altogether, set the value to 0.
# If no ConnectPort line is found, all ports are allowed (which is not
# very secure.)

EOD;
		
		foreach ( $this->data->rule as $rule ) {
			$proxyconf .= "ConnectPort {$rule->port}\n";
		}
		
		fwrite ( $fd, $proxyconf );
		fclose ( $fd );
		Functions::mountFilesystem ( 'r' );
		
		return 0;
	}
	
	/**
	 * Starts the plugin
	 * 
	 * Start the plugin in boot mode launching the proxy service.
	 * 
	 * @access public
	 */
	public function runAtBoot() {
		Logger::getRootLogger ()->info ( "Init Proxy" );
		//if (( string ) $this->data ['enable'] == true) {
		$result = $this->configure ();
		//Don't start if config fails.
		if ($result < 1) {
			$this->start ();
		} else {
			Logger::getRootLogger ()->info ( "Proxy server not starting due to configuration errors." );
		}
		//}
	}
	
	/**
	 * Get info for a front-end page
	 * 
	 * Passthrough function to delegate page requests to the proper functions
	 * checks $_POST['page'] for input
	 * 
	 * @throws Exception
	 * @access public
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
				case 'editport' :
					$this->saveRule ( $_POST ['services_proxy_port_id'] );
					break;
				case 'addport' :
					$this->saveRule ( null );
					break;
				case 'deleteport' :
					$this->delRule ($_POST['ruleid']);
					break;
				default :
					throw new Exception ( "page request not valid" );
			}
		} else {
			throw new Exception ( "page request not valid" );
		}
	}
	
	/**
	 * Save Proxy port rule
	 * 
	 * 
	 * @throws Exception
	 * @access private
	 */
	private function save() {
		if($_POST['services_proxy_settings_port'] < 0 || $_POST['services_proxy_settings_port'] > 65535){
			ErrorHandler::addError('formerror','services_proxy_settings_port');
		}
		
		if(!Functions::is_ipAddr($_POST['services_proxy_settings_allow_ipaddr'])){
			ErrorHandler::addError('formerror','services_proxy_settings_allow_ipaddr');
		}
		
		if(!Functions::is_subnet($_POST['services_proxy_settings_allow_subnet'])){
			ErrorHandler::addError('formerror','services_proxy_settings_allow_subnet');
		}
		
		if(!is_numeric($_POST['services_proxy_settings_maxclients'])){
			ErrorHandler::addError('formerror','services_proxy_settings_maxclients');
		}
		
		if(!is_numeric($_POST['services_proxy_settings_timeout'])){
			ErrorHandler::addError('formerror','services_proxy_settings_timeout');
		}
		
		if(ErrorHandler::errorCount() == 0){
			$this->data->port = $_POST ['services_proxy_settings_port'];
			$this->data->allow_from->ip = $_POST['services_proxy_settings_allow_ipaddr'];
			$this->data->allow_from->subnet = $_POST['services_proxy_settings_allow_subnet'];
			$this->data->proxyname = $_POST['services_proxy_settings_proxyname'];
			$this->data->maxclients = $_POST['services_proxy_settings_maxclients'];
			$this->data->timeout = $_POST['services_proxy_settings_timeout'];
			
			//Save config and print the data
			if ($this->config->saveConfig ()) {
				echo '<reply action="ok">';
				echo $this->data->asXML ();
				echo '</reply>';
			} else {
				//The config file could not be written.
				throw new Exception ( "Error, could not save configuration file." );
			}
		}
		else{
			throw new Exception("There is invalid form input");
		}
	}
	
	/**
	 * Deletes a rule
	 * 
	 * Delete proxy rule specified in id parameter
	 * 
	 * @param Integer $id identifier of the rule to delete
	 * @throws Exception
	 * @access private
	 */
	private function delPort($id) {
		if (empty ( $id )) {
			throw new Exception('Error, could not find the rule');
		}
		
		$rule = $this->getRule ( $id );
		
		if (isset ( $rule )) {
			$this->config->deleteElement ( $rule );
			
			if ($this->config->saveConfig ()) {
				echo '<reply action="ok">';
				echo '<message>Rule removed.</message>';
				echo '</reply>';
				
			//Logger::getRootLogger ()->info ( "Restarting proxy" );
			//$this->runAtBoot ();
			} else {
				throw new Exception ( "Error, could not save configuration file." );
			}
		}
	}
	
	/**
	 * Save or add a rule
	 * 
	 * Saves or add a rule based on the setting of the $id param, null will create a new
	 * rule, having $id set will overwrite an existing rule, provided it exists
	 * 
	 * @param Integer|null $id ID to save as
	 * @throws Exception
	 * @access private
	 */
	private function savePort($id) {
		if (isset ( $id )) {
			$rule = $this->getRule( $id );
		}
		if (empty ( $rule ) || ($id != 0 && empty ( $id ))) {
			$rule = $this->data->addChild ( "rule" );
			$rule ['id'] = time ();
		}
		
		//Set config
		$rule ['protocol'] = $_POST ['services_proxy_port_protocol'];
		$rule ['port'] = $_POST ['services_proxy_port_port_custom'];
		
		//Save config and print the data
		if ($this->config->saveConfig ()) {
			echo '<reply action="ok"><proxy>';
			echo $rule->asXML ();
			echo '</proxy></reply>';
			
		//Logger::getRootLogger ()->info ( "Restarting IPsec" );
		//$this->runAtBoot ();
		} else {
			throw new Exception ( "Error, could not save configuration file." );
		}
	}
	
	/**
	 * Get a proxy rule
	 * 
	 * Find a specified proxy rule in the XML config
	 * 
	 * @param int $id ID of the rule
	 * @return SimpleXMLElement|NULL Returns the rule or null on not found.
	 * @access private
	 */
	private function getRule($id) {
		foreach ( $this->data->rule as $rule ) {
			if ($rule ['id'] == $id)
				return $rule;
		}
		return null;
	}
	
	/**
	 * Gets a list of dependend plugins
	 * 
	 * Proxy has no dependencies
	 * 
	 * @access public
	 */
	public function getDependency() {}
	
	/**
	 * Retrieves the plugin status
	 * 
	 * Checks if the proxy service is running and returns its status in string form
	 * 
	 * @return string  Started|Error|Stopped 
	 * @access public
	 */
	public function getStatus() {
		$pid = file_exists ( self::PID_PATH ) ? Functions::shellCommand ( "pgrep -F " . self::PID_PATH ) : 0;
		if ($pid > 0) {
			return 'Started';
		} else {
			if (( string ) $this->data ['enable'] == true)
				return "Error"; //Is enabled, but not running
			else
				return 'Stopped';
		}
	}
	
	/**
	 * Shutsdown the Plugin.
	 * 
	 * Called at program shutdown, stops itself so the system can reboot / shut down cleanly
	 * calling this is non-vital as the system is capable of a clean power-down without any
	 * shutdown procedures
	 * 
	 *  @access public
	 */
	public function shutdown() {
		$this->stop ();
	}
}
?>