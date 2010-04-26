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
 * DHCP server plugin
 *
 *
 * @author Sebastiaan Gibbon
 * @version 0.0
 */

class Dhcpd implements Plugin {
	
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
	 * path and filename to the dhcpd config file
	 * 
	 * @var string
	 */
	const CONFIG_PATH = '/var/etc/dhcpd.conf';
	
	/**
	 * Path and filename to the dhcpd PID file
	 * 
	 * @var string
	 */
	const PID_PATH = '/var/run/dhcpd.pid';
	
	/**
	 * Path to the leases file.
	 * 
	 * @var string
	 */
	const LEASES_PATH = '/var/db/dhcpd.leases';
	
	/**
	 * Path to store the next-server file.
	 * 
	 * @var string
	 */
	const NEXT_PATH = '/var/etc/';
	
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
		
		$this->interface = null; //interface to use DHCP with
		

		//get dhcpd config
		$this->data = $this->config->getElement ( 'dhcpd' );
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
		if ($this->getStatus() == 'Started') {
			Logger::getRootLogger ()->info ( 'DHCPD was already running' );
			return true;
		}
		
		Logger::getRootLogger ()->info ( "Starting DHCPD" );
		
		//Check if the config file exists.
		if (! file_exists ( self::CONFIG_PATH )) {
			Logger::getRootLogger ()->error ( 'Config file not found. Aborting DHCPD startup.' );
			return false;
		}
		
		//Which intererface(s) to listen too for DHCP request. default port is UDP 67
		$interface = $this->framework->getPlugin ( "Lan" );
		if (empty ( $interface )) {
			Logger::getRootLogger ()->error ( 'Could not get LAN plugin.' );
			return false;
		}
		
		try {
			$interfaces = $interface->getRealInterfaceName ();
		} catch ( Exception $e ) {
			Logger::getRootLogger ()->error ( "Could not get interface name." . $e->getMessage () );
			return false;
		}
		
		//create needed paths and files to run Dhcpd in chroot (jailed?)
		if (! file_exists ( $this->data->chroot_path )) {
			//create needed directories
			mkdir ( $this->data->chroot_path, 0777, true );
			mkdir ( $this->data->chroot_path . "/dev", 0777, true );
			mkdir ( $this->data->chroot_path . "/etc", 0777, true );
			mkdir ( $this->data->chroot_path . "/usr/local/sbin", 0777, true );
			mkdir ( $this->data->chroot_path . "/var/db", 0777, true );
			mkdir ( $this->data->chroot_path . "/var/run", 0777, true);
			mkdir ( $this->data->chroot_path . "/lib", 0777, true );
			mkdir ( $this->data->chroot_path . "/run", 0777, true );
			
			//change ownership of the chroot files (php's chown() has trouble finding the user and does not do -R)
			Functions::shellCommand ( "chown -R dhcpd:dhcpd {$this->data->chroot_path}/*" );
			
			//copy needed files. (copy() does not copy recursively)
			Functions::shellCommand ( "cp /lib/libc.so.* {$this->data->chroot_path}/lib/" );
			Functions::shellCommand ( "cp /usr/local/sbin/dhcpd {$this->data->chroot_path}/usr/local/sbin/" );
			chmod ( $this->data->chroot_path . "/usr/local/sbin/dhcpd", 0555 ); //-r-xr-xr-x
			

			//The lease file needs to be present before starting DHCPD
			touch ( $this->data->chroot_path . self::LEASES_PATH );
			touch ($this->data->chroot_path.'/var/run/dhcpd.pid');
		}
		
		//Mount /dev in the chroot
		if (strlen ( Functions::shellCommand ( "/sbin/mount | grep \"{$this->data->chroot_path}/dev\"" ) ) < 1) {
			Functions::shellCommand ( "/sbin/mount -t devfs devfs {$this->data->chroot_path}/dev" );
		}
		
		//Start DHCPD (-q will not print the copyright message)
		Functions::shellCommand ( "/usr/local/sbin/dhcpd -q -user dhcpd -group dhcpd -chroot {$this->data->chroot_path} -cf " . self::CONFIG_PATH . " -pf " . self::PID_PATH . " -lf " . self::LEASES_PATH . " {$interfaces}" );
		return true;
	}
	
	/**
	 * Stop the service
	 * 
	 * @return bool false when service failed to stop
	 */
	public function stop() {
		Logger::getRootLogger ()->info ( "Stopping DHCPD" );
		$pid = Functions::shellCommand('pgrep dhcpd');
		if ($this->getStatus() == 'Started') {
			Functions::shellCommand ( "/bin/kill {$pid}" );
		}
	}
	
	/**
	 * Write configuration to the system
	 * 
	 * @todo: add ntp options, nameserver options
	 * @return int Returns status of configuration. 0 is ok, 1 or higher means an error occurd.
	 */
	public function configure() {
		Logger::getRootLogger ()->info ( "Configuring DHCPD" );
		
		//	make /var/db directory if it does not exist
		if(!is_dir('/var/db')){
			Functions::shellCommand('mkdir /var/db');
		}
		
		//Write away the DHCPD config.
		$fd = fopen ( self::CONFIG_PATH, "w" );
		if (! $fd) {
			Logger::getRootLogger ()->error ( "Error: Could not write DHCPD configuration to " . self::CONFIG_PATH );
			return 1;
		}
		
		if((string)$this->data->deny_unknown == 'true'){
			$deny_unknown = 'deny unknown-clients;';
		}
		else{
			$deny_unknown = 'allow unknown-clients;';
		}
		
		$systemConfig = $this->config->getElement ( "system" );
		
		$dhcpdconf = <<<EOD
#Global config for every subnet and pool
ddns-update-style none;
option domain-name "{$systemConfig->domain}";
default-lease-time {$this->data->defaultleasetime};
max-lease-time {$this->data->maxleasetime};
authoritative;
log-facility local7;
#ddns-update-style none;
#one-lease-per-client true;
#deny duplicates;
{$deny_unknown}

EOD;
		
		//Config the subnet
		$subnet = long2ip ( ip2long ( $this->data->range->from ) & ip2long ( $this->data->netmask ) );
		$dhcpdconf .= "subnet {$subnet} netmask {$this->data->netmask} {\n";
		$dhcpdconf .= "   range {$this->data->range->from} {$this->data->range->to};\n";
		
		//	GET own IP address and advertise it as the router
		$lan = $this->framework->getPlugin('Lan');
		if($lan !== false){
			$dhcpdconf .= "	  option routers ".($lan->getIpAddress()).";\n";

			// Get dns servers		
			$dhcpdconf .= "   option domain-name-servers ".($lan->getIpAddress()).";\n";
		}

		//Set WINS
		if (isset ( $this->data->winsserver )) {
			//Get all the servers, and create a sortable list, so the servers are listed corectly.
			foreach ( $this->data->winsservers->winsserver as $node ) {
				$wins [] = ( string ) $node['ip'];
			}
			ksort ( $wins ); //Sorts the servers by key (id), lowest first.
			$dhcpdconf .= "   option netbios-name-servers " . implode ( ", ", $wins ) . ";\n";
			$dhcpdconf .= "   option netbios-node-type 8;\n";
		}
		
		//$dhcpdconf .= "   option ntp-servers (if ntp)\n";				

		//$dhcpdconf .= "   ddns-domainname \n";
		//$dhcpdconf .= "   ddns-update-style interim;\n";				
		

		//Config the bootp server.
		if (isset ( $this->data->{'next-server'} ) && strlen ( ( string ) $this->data->{'next-server'} ) > 0 && strlen ( ( string ) $this->data->{'next-server'} ['filename'] ) > 0) {
			$dhcpdconf .= "   next-server {$this->data->{'next-server'}};\n";
			$dhcpdconf .= "   filename {$this->data->{'next-server'}['filename']};\n";
		}
		
		$dhcpdconf .= "}\n\n";
		
		//config static mapping
		$i = 1;
		foreach ( $this->data->staticmaps->map as $map ) {
			$dhcpdconf .= <<<EOD
host staticmap_{$i} {
   hardware ethernet {$map['mac']};
   fixed-address {$map['ipaddr']};
}

EOD;
			$i ++;
		}
		
		//Add firewall rules to allow clients to ask for leases. DHCP uses UDP port 67 
		if (empty ( $this->data->firewallid )) {
			$firewall = $this->framework->getPlugin ( "Firewall" );
			
			if (isset ( $firewall )) {
				//TODO: Check if firewall rule creation is correct.
				$id = time();
				$source ['type'] = 'any';
				$source ['port'] = '67';
				$destination ['type'] = 'any';
				$destination ['port'] = '67';
				$firewall->addRule ( 'true', 'pass', 'in', 'disabled', 'Lan', 'udp', null, $source, $destination, 'disabled', 'Generic Proxy DHCP deamon', 'Dhcpd', 0, $id );
				$this->data->addChild('firewallid', $id);
			}
		}
		
		fwrite ( $fd, $dhcpdconf );
		fclose ( $fd );
		return 0;
	}
	
	/**
	 * Starts the plugin
	 */
	public function runAtBoot() {
		Logger::getRootLogger ()->info ( "Init DHCPD" );
		if (( string ) $this->data ['enable'] == 'true') {
			$result = $this->configure ();
			//Don't start if config fails.
			if ($result < 1) {
				$this->start ();
			} else {
				Logger::getRootLogger ()->info ( "Dhcpd not starting due to configuration errors." );
			}
		}
	}
	
	/**
	 * Get info for a front-end page
	 */
	public function getPage() {
		if (isset ( $_POST ['page'] )) {
			if(in_array($_SESSION['group'],$this->acl)){
				switch ($_POST ['page']) {
					case 'getconfig' :
						echo '<reply action="ok">';
						echo $this->data->asXML ();
						echo '</reply>';
						break;
					case 'save' :
						$this->save ();
						break;
					case 'editrule' :
						$this->saveStaticMap ( $_POST ['services_dhcpd_rule_id'] );
						break;
					case 'addrule' :
						$this->saveStaticMap (null);
						break;
					case 'deleterule' :
						$this->delStaticMap ( $_POST ['ruleid'] );
						break;
					case 'getstatus' :
						$this->echoStatus();
						break;
					default :
						throw new Exception ( "page request not valid" );
				}
			}
			else{
				throw new Exception('You do not have permission to do this');
			}
		} else {
			throw new Exception ( "page request not valid" );
		}
	}
	
	/**
	 * 	Check DHCPD form input for anomalies
	 */
	private function checkForm(){
		//Check if the IPs are correct
		if (! Functions::is_ipAddr ( $_POST ['services_dhcpd_netmask'] )) {
			ErrorHandler::addError('formerror','services_dhcpd_netmask');
		}

		if (! Functions::is_ipAddr ( $_POST ['services_dhcpd_range_from'] )) {
			ErrorHandler::addError('formerror','services_dhcpd_range_from');
		}
			
		if (! Functions::is_ipAddr ( $_POST ['services_dhcpd_range_to'] )) {
			ErrorHandler::addError('formerror','services_dhcpd_range_to');
		}
		
		//check if the range and subnet is valid.
		if (ErrorHandler::errorCount() == 0) { //Don't do range check if one of them is not a valid IP
			$subnetfrom = ip2long ( $_POST ['services_dhcpd_range_from'] ) & ip2long ( $_POST ['services_dhcpd_netmask'] );
			$subnetto = ip2long ( $_POST ['services_dhcpd_range_to'] ) & ip2long ( $_POST ['services_dhcpd_netmask'] );
			if ($subnetfrom != $subnetto) {
				ErrorHandler::addError('formerror','services_dhcpd_range_to');
				ErrorHandler::addError('formerror','services_dhcpd_range_from');
			}
		}
		else{
			throw new Exception('There is invalid form input');
		}
	}
	
	/**
	 * Save DHCPD settings 
	 * After saving the config, the DHCPD server will be restarted. 
	 */
	private function save() {
		$this->checkForm();
		
		//Set the config.
		$this->data->range->from = $_POST ['services_dhcpd_range_from'];
		$this->data->range->to = $_POST ['services_dhcpd_range_to'];
		$this->data->range->netmask = $_POST ['services_dhcpd_netmask'];
		$this->data->defaultleasetime = $_POST ['services_dhcpd_deflease'];
		$this->data->maxleasetime = $_POST ['services_dhcpd_maxlease'];
		
		if($_POST['services_dhcpd_deny_unknown'] == 'true'){
			$this->data->deny_unknown = 'true';
		}
		else{
			$this->data->deny_unknown = 'false';
		}
		
		$this->data->winsservers->winsserver[0]['ip'] = $_POST ['services_dhcpd_wins1'];
		$this->data->winsservers->winsserver[1]['ip'] = $_POST ['services_dhcpd_wins2'];
		
		//$this->data->{'next-server'} = $_POST ['services_dhcpd_next_server'];
		if (isset ( $_FILES ['next-server'] )) {
			$file = self::NEXT_PATH . basename ( $_FILES ['userfile'] ['name'] );
			if (move_uploaded_file ( $_FILES ['userfile'] ['tmp_name'], $file )) {
				$this->data->{'next-server'} ['filename'] = $file;
			} else {
				throw new Exception('File upload for next-server did not succeed');
			}
		}
		
		//Save config and print the data
		if ($this->config->saveConfig ()) {
			//restart httpd.
			echo '<reply action="ok">';
			echo $this->data->asXML ();
			echo '</reply>';
			
			Logger::getRootLogger ()->info ( "Restarting DHCPD" );
			$this->stop ();
			$this->runAtBoot ();
		} else{
			//The config file could not be written.
			throw new Exception ( "Error, could not save configuration file." );
		}
	}
	
	/**
	 * Returns the mapping with the same ID
	 * 
	 * @param SimpleXMLElement $id
	 */
	private function getStaticMap($id) {
		foreach ( $this->data->staticmaps->map as $map ) {
			if ($map ['id'] == $id)
				return $map;
		}
		return null;
	}
	
	/**
	 * Validate static map form data
	 * 
	 * @throws Exception
	 */
	private function checkMapForm(){
		if(!Functions::isMacAddress($_POST['services_dhcpd_rule_macaddr'])){
			ErrorHandler::addError('formerror','services_dhcpd_rule_macaddr');
		}
		
		if(!Functions::is_ipAddr($_POST['services_dhcpd_rule_ipaddr'])){
			ErrorHandler::addError('formerror','services_dhcpd_rule_ipaddr');
		}
		
		if(ErrorHandler::errorCount() > 0){
			throw new Exception('There is invalid form input');
		}
	}
	
	/**
	 * Adds/updates a static map in the config
	 * 
	 * @param int|null $id ID of the static map to be updated pass null to add a map.
	 */
	private function saveStaticMap($id) {
		$this->checkMapForm();
		if (isset ( $id )) {
			$map = $this->getStaticMap ( $id );
		}
		if (empty ( $map ) || empty ( $id )) {
			$map = $this->data->staticmaps->addChild ( "map" );
			$map->addChild('description');
			$map ['id'] = time ();
		}
		
		$map->description = $_POST ['services_dhcpd_rule_descr'];
		$map ['hostname'] = $_POST['services_dhcpd_rule_hostname'];
		$map ['mac'] = $_POST ['services_dhcpd_rule_macaddr'];
		$map ['ipaddr'] = $_POST ['services_dhcpd_rule_ipaddr'];
		
		//Save config and print the data
		if ($this->config->saveConfig ()) {
			echo '<reply action="ok"><staticmaps>';
			echo $map->asXML ();
			echo '</staticmaps></reply>';
			
			Logger::getRootLogger ()->info ( "Restarting Dhcpd" );
			$this->stop ();
			$this->runAtBoot ();
		} else {
			throw new Exception ( "Error, could not save configuration file." );
		}
	}
	
	/**
	 * Remove a static map from the config
	 * 
	 * @param int $id ID of the static map tp be removed.
	 */
	private function delStaticMap($id) {
		if (empty ( $id )) {
			throw new Exception('No map id was specified');
		}
		
		$map = $this->getStaticMap( $id );
		if (isset ( $map )) {
			$this->config->deleteElement ( $map );
			
			if ($this->config->saveConfig ()) {
				echo '<reply action="ok" />';
				
				Logger::getRootLogger ()->info ( "Restarting IPsec" );
				$this->stop ();
				$this->runAtBoot ();
			} else {
				throw new Exception ( "Error, could not save configuration file." );
			}
		}
		else{
			throw new Exception('The static map could not be found');
		}
	}
	
	/**
	 * Gets a list of dependend plugins
	 */
	public function getDependency() {
	
	}
	
	/**
	 * Echo DHCPD status to AJAX frontend, current process status and current leases
	 */
	private function echoStatus(){
		$file_handle = fopen($this->data->chroot_path.self::LEASES_PATH, "r");
		$leases = array();
		while (!feof($file_handle)) {
		   $line = fgets($file_handle);
		   if ($line[0] != '#') {
				if(stristr($line,'lease')){
		   			//start a new lease
					$lease = array();
		   			$ip = str_replace('lease ', '', $line);
		   			$ip = str_replace(" {\n", '', $ip);
		   			$lease['ip'] = $ip;
		   		} elseif (stristr($line, 'starts')) {
		   			//start of lease found
		   			$lease['start'] = substr($line, 11, 19);
		   		} else if(stristr($line, 'ends')) {
		   			//end of lease found
		   			$lease['end'] = substr($line, 9, 19);
		   		} else if (stristr($line, 'ethernet')) {
		   			//mac address found
		   			$lease['mac'] = substr($line, 20, 17);
		   		} else if (stristr($line,'hostname')) {
		   			//hostname found
		   			$host_start = strpos($line,'"');
		   			$host_end = strrpos($line,'"');
		   			$lease['hostname'] = substr($line,$host_start + 1, ($host_end - $host_start - 1));
		   		} else if (stristr($line,'}')) {
		   			//End of lease
		   			$timestamp = strtotime($lease['end']);
					if ($timestamp > time()) {
						$leases[$lease['mac']] = $lease;
					}
		   		}
		   }
		}
		fclose($file_handle);
		$buffer = '<reply action="ok"><dhcp_status>';
		foreach ($leases as $lease) {
			$buffer .= '<lease>';
			$buffer .= '<ip>'.$lease['ip'].'</ip>';
			$buffer .= '<start>'.$lease['start'].'</start>';
			$buffer .= '<end>'.$lease['end'].'</end>';
			$buffer .= '<mac>'.$lease['mac'].'</mac>';
			$buffer .= '<hostname>'.$lease['hostname'].'</hostname>';
			$buffer .= '</lease>';
		}
		$buffer .= '</dhcp_status></reply>';
		echo $buffer;
	}
	
	/**
	 * Starts the plugin
	 * 
	 * @return string Status of the service/plugin
	 */
	public function getStatus() {
		$pid = Functions::shellCommand('pgrep dhcpd');
		if ($pid > 0) {
			return 'Started';
		} else {
			if (( string ) $this->data ['enable'] == 'true'){
				return "Error"; //Is enabled, but not running
			}else{
				return 'Stopped';
			}
		}
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