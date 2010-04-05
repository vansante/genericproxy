<?php
/**
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
 * IPsec server plugin
 * //TODO: Check XML return messages.
 * //TODO: Check if all logging is placed everywhere.
 * @author Sebastiaan Gibbon
 * @version 0.0
 */

class Ipsec implements Plugin {
	/**
	 * Contains a reference to the configuration object
	 * @var Config
	 * @access private
	 */
	private $config;
	
	/**
	 * Contains the runtype, either boot or webgui
	 * @access private
	 * @var Integer
	 */
	private $runtype;
	
	/**
	 * Contains reference to the plugin framework
	 * @access private
	 * @var PluginFramework
	 */
	private $framework;
	
	/**
	 * Contains configuration data retrieved from $this->config
	 * @access private
	 * @var SimpleXMLElement
	 */
	private $data;
	
	/**
	 * path and filename to the IPSec config file
	 * @var string
	 */
	const CONFIG_PATH = '/var/etc/racoon.conf';
	
	/**
	 * Path and filename to the lighttpd PID file
	 * @var string
	 */
	const PID_PATH = '/var/run/racoon.pid';
	
	/**
	 * Path to the pre shared key file
	 * @var string
	 */
	const PKS_PATH = '/var/etc/pks.txt';
	
	/**
	 * Path to where certificates are stored
	 * //TODO: find a location for CERT_PATH, not in /var
	 * @var string Path with trailing slash
	 */
	const CERT_PATH = '/bla/bla/';
	
	/**
	 * Path to the setkey file
	 * @var string
	 */
	const SETKEY_PATH = '/var/etc/setkey.conf';
	
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
		
		//get IPSec config
		$this->data = $this->config->getElement ( 'ipsec' );
	}
	
	/**
	 * Is the Plugin a service?
	 * @returne bool
	 */
	public function isService() {
		return true;
	}
	
	/**
	 * Start the service. If it is already running, the config will be reloaded.
	 * @return bool false when service failed to start
	 */
	public function start() {
		//Check if the config file exists.
		if (! file_exists ( self::CONFIG_PATH )) {
			Logger::getRootLogger ()->error ( 'Config file not found. Aborting IPSec startup.' );
			return false;
		}
		
		$pid = file_exists ( self::PID_PATH ) ? Functions::shellCommand ( "pgrep -F " . self::PID_PATH ) : 0;
		if ($pid > 0) {
			Logger::getRootLogger ()->info ( 'Reloading IPSec conifg' );
			Functions::shellCommand ( "/usr/local/sbin/racoonctl reload-config" );
			return true;
		}
		
		Logger::getRootLogger ()->info ( "Starting IPSec" );
		Functions::shellCommand ( "/usr/local/sbin/racoon -f " . self::CONFIG_PATH );
		return true;
	}
	
	/**
	 * Stop the service
	 * @return bool false when service failed to stop
	 */
	public function stop() {
		Logger::getRootLogger ()->info ( "Stopping IPsec" );
		$pid = file_exists ( self::PID_PATH ) ? Functions::shellCommand ( "pgrep -F " . self::PID_PATH ) : 0;
		if ($pid > 0) {
			Functions::shellCommand ( "/bin/kill {$pid}" );
		}
	}
	
	/**
	 * Write configuration to the system
	 * @return int result. Anything higher then 0 means error.1 = No tunnels. 2= error.
	 */
	public function configure() {
		Logger::getRootLogger ()->info ( "Configuring IPSec" );
		Functions::mountFilesystem ( 'rw' );
		
		$setkey = "flush;\n";
		$setkey .= "spdflush;\n";
		
		//remove all gif devices
		foreach ( glob ( "/dev/net/gif*" ) as $interface ) {
			Functions::shellCommand ( "/sbin/ifconfig " . basename ( $interface ) . " destroy" );
			Logger::getRootLogger ()->info ( "Removing interface {$interface}" );
		}
		
		//TODO: Get this setting from XML somewhere
		$nat_traversal = "off";
		
		//Pre shared key file
		$pksKeys = "";
		
		//Array with IPs raccoon should listen on
		$listen = array ();
		
		//IPsec config file
		$ipsec = "path pre_shared_key \"" . self::PKS_PATH . "\";\n";
		$ipsec .= "path pidfile \"" . self::PID_PATH . "\";\n";
		$ipsec .= "path certificate \"" . self::CERT_PATH . "\";\n";
		$ipsec .= <<<EOD
log     debug;  #log verbosity setting: set to 'notify' when testing and debugging is complete

padding # options are not to be changed
{
        maximum_length  20;
        randomize       off;
        strict_check    off;
        exclusive_tail  off;
}

timer   # timing options. change as needed
{
        counter         5;
        interval        20 sec;
        persend         1;
        
EOD;
		if ($nat_traversal == 'on') {
			$ipsec .= "        natt_keepalive  15 sec;\n";
		}
		$ipsec .= <<<EOD
        phase1          30 sec;
        phase2          15 sec;
}

EOD;
		
		//Create a IPsec tunnel for each tunnel in the config.
		$gif_counter = 0;
		$active_tunnel_counter = 0;
		foreach ( $this->data->tunnel as $tunnel ) {
			if (( string ) $tunnel ['enable'] != 'true') {
				continue; //not enabled, go to next tunnel.
			}
			
			//TODO: Not all options work/are available as are defined in the mockup.
			//Currently this bit only does network to network tunnels.

			$local = $this->getlocal ( $tunnel );
			$remote = $this->getremote ( $tunnel );
			//TODO: Get type.
			//$local[public-ip] and $remote[public-ip] should always be set.
			//for P2P we only need the two public-ips
			//for N2N we need public-ips and private-ips
			//What about a tunnel that goes from local[private-ip] to remote[public-ip]  
			$type = "N2N";
			
			if (empty ( $local ) || empty ( $remote )) {
				Logger::getRootLogger ()->info ( "Aborting tunnel {$tunnel ['id']} creation due invalid IP." );
				continue;
			}
			
			if ($type == 'N2N') { // network to network
				$gif = "gif" . $gif_counter;
				Logger::getRootLogger ()->info ( "Creating interface {$gif} for tunnel ID {$tunnel['id']}" );
				Functions::shellCommand ( "/sbin/ifconfig {$gif} create" );
				Functions::shellCommand ( "/sbin/ifconfig {$gif} {$local['private-ip']} {$remote['private-ip']}" );
				Functions::shellCommand ( "/sbin/ifconfig {$gif} tunnel {$local['public-ip']} {$remote['public-ip']}" );
				$gif_counter ++;
				Logger::getRootLogger ()->info ( "Adding Routing for tunnel ID {$tunnel['id']}" );
				//Functions::shellCommand ( "/sbin/route {$remote['private-subnetid']} {$remote['private-ip']} {$remote['private-subnet']}" );
				Functions::shellCommand ( "/sbin/route add -net {$remote['private-subnetid']} {$remote['private-ip']} {$remote['private-subnet']}" );
				
				Logger::getRootLogger ()->info ( "Adding spd for tunnel ID {$tunnel['id']}" );
				$setkey .= "spdadd {$local['private-subnetid']}/{$local['private-subnetnr']} {$remote['private-subnetid']}/{$remote['private-subnetnr']} any -P out ipsec esp/tunnel/{$local['public-ip']}-{$remote['public-ip']}/use;\n";
				$setkey .= "spdadd {$remote['private-subnetid']}/{$remote['private-subnetnr']} {$local['private-subnetid']}/{$local['private-subnetnr']} any -P in ipsec esp/tunnel/{$remote['public-ip']}-{$local['public-ip']}/use;\n";
			} elseif ($type == 'P2P') { //Point to point
				$setkey .= "spdadd {$local['public-ip']}/32 {$remote['public-ip']}/32 any -P out ipsec esp/tunnel/{$local['public-ip']}-{$remote['public-ip']}/use;\n";
				$setkey .= "spdadd {$remote['public-ip']}/32 {$local['public-ip']}/32 any -P in ipsec esp/tunnel/{$remote['public-ip']}-{$local['public-ip']}/use;\n";
				//TODO;Route needed?
				//TODO: From pfsense:
				//do once: $spdconf .= "spdadd {$lansa}/{$lansn} {$lanip}/32 any -P in none;\n";
				//do once: $spdconf .= "spdadd {$lanip}/32 {$lansa}/{$lansn} any -P out none;\n";
				///var/db/ipsecpinghosts
				//mwexec("/sbin/ifconfig gif" . $number_of_gifs . " tunnel" . $curwanip . " " . $tunnel['remote-gateway']);
				//mwexec ( "/sbin/ifconfig gif" . $number_of_gifs . " {$lansa}/{$lansn} {$lanip}/32" );
				//$spdconf .= "spdadd {$sa}/{$sn} " . "{$tunnel['remote-subnet']} any -P out ipsec " . "{$tunnel['p2']['protocol']}/tunnel/{$ep}-" . "{$tunnel['remote-gateway']}/unique;\n";			
				//$spdconf .= "spdadd {$tunnel['remote-subnet']} " . "{$sa}/{$sn} any -P in ipsec " . "{$tunnel['p2']['protocol']}/tunnel/{$tunnel['remote-gateway']}-" . "{$ep}/unique;\n";
				//there 's more relating to 'mobile clients' on vpn.inc line 478 using 'remote anonymous' and 'sainfo anonymous'
			}
			
			//Get key or certificate, depending on authentication-method
			if ($tunnel->phase1->{'authentication-method'} ['type'] == 'pre_shared_key') {
				$pksKeys .= "{$remote['public-ip']}\t{$tunnel->phase1->{'authentication-method'}}\n";
				//$pksKeys .= "{$remote['private-ip']}\t{$tunnel->phase1->psk}\n";
			} elseif ($tunnel->phase1->{'authentication-method'} ['type'] == 'rsasig') {
				$certificate = $this->getCertificate ( ( string ) $tunnel->phase1->{'authentication-method'} );
			}
			
			$ipsec .= <<<EOD
remote  {$remote['private-ip']} [500]
{
        exchange_mode   {$tunnel->phase1->{'exchange-mode'}};
        doi             ipsec_doi;
        situation       identity_only;
        my_identifier   address {$local['public-ip']};
        peers_identifier	address {$remote['private-ip']};
        lifetime        time 8 hour;
        passive         off;
        proposal_check  obey;
        nat_traversal   {$nat_traversal};
        generate_policy off;
        
        proposal {
                encryption_algorithm    {$tunnel->phase1->{'encryption-algorithm'}};
                hash_algorithm          {$tunnel->phase1->{'hash-algorithm'}};
                authentication_method   {$tunnel->phase1->{'authentication-method'}['type']};
                lifetime time           {$tunnel->phase1->lifetime};
                dh_group                {$tunnel->phase1->dhgroup};

EOD;
			//Add certificate information to the proposal
			if ($tunnel->phase1->{'authentication-method'} ['type'] == 'rsasig') {
				//TODO: Add x509 certificate type
				$ipsec .= "                certificate_type plain_rsa \"{$certificate->private}\"\n";
				$ipsec .= "                peers_certfile plain_rsa \"{$certificate->public}\"\n";
			}
			
			//Close proposal and remote
			$ipsec .= "\t}\n}\n";
			
			//Create sainfo
			//TODO:add sainfo for P2P?
			$ipsec .= "sainfo  (address {$local['private-subnetid']}/{$local['private-subnetnr']} any address {$remote['private-subnetid']}/{$local['private-subnetnr']} any)";
			$ipsec .= <<<EOD
{
        pfs_group                {$tunnel->phase2->pfsgroup};
        lifetime time            {$tunnel->phase2->lifetime};
        encryption_algorithm     {$tunnel->phase2->{'encryption-algorithm'}};
        authentication_algorithm {$tunnel->phase2->{'authentication-algorithm'}};
        compression_algorithm   deflate;
}

EOD;
			
			if (! in_array ( $local ['public-ip'], $listen )) {
				$listen [] = $local ['public-ip'];
			}
			
			//TODO add firewall rules
			$plugin = $this->framework->getPlugin ( "Firewall" );
			if (isset ( $plugin )) {
				//$plugin->addRule();			
			}
			
			$active_tunnel_counter ++;
		}
		
		//Add listens
		if (count ( $listen > 0 )) {
			$ipsec .= "listen  # address [port] that racoon will listening on\n{\n";
			foreach ( $listen as $ip ) {
				$ipsec .= "isakmp          $ip [500];\n";
				if ($nat_traversal == 'on') {
					$ipsec .= "isakmp_natt     $ip [4500];\n";
				}
			}
			$ipsec .= "}\n";
		}
		
		if ($active_tunnel_counter < 1) {
			Logger::getRootLogger ()->info ( "No tunnels in IPsec defined." );
			return 1;
		}
		
		//Save and run setkey
		$fd = fopen ( self::SETKEY_PATH, "w" );
		if (! $fd) {
			Logger::getRootLogger ()->error ( "Error: Could not write setkey conifg to " . self::SETKEY_PATH );
			return 2;
		}
		fwrite ( $fd, $setkey );
		fclose ( $fd );
		Logger::getRootLogger ()->info ( "Running setkey." );
		Functions::shellCommand ( "/sbin/setkey -f " . self::SETKEY_PATH );
		
		//fastforwarding is not compatible with ipsec tunnels
		Functions::shellCommand ( "/sbin/sysctl net.inet.ip.fastforwarding=0" );
		
		//Save IPsec config
		$fd = fopen ( self::CONFIG_PATH, "w" );
		if (! $fd) {
			Logger::getRootLogger ()->error ( "Error: Could not write IPsec conifg to " . self::CONFIG_PATH );
			return 2;
		}
		fwrite ( $fd, $ipsec );
		fclose ( $fd );
		
		//Save pre shared key file
		$fd = fopen ( self::PKS_PATH, "w" );
		if (! $fd) {
			Logger::getRootLogger ()->error ( "Error: Could not write IPsec PKS to " . self::PKS_PATH );
			return 2;
		}
		fwrite ( $fd, $pksKeys );
		fclose ( $fd );
		chmod ( self::PKS_PATH, 0600 );
		
		Functions::mountFilesystem ( 'ro' );
		
		return 0;
	}
	
	/**
	 * Gets the local IP tunnel information from settings. private-ip may be empty?
	 * @param SimpleXMLElement $tunnel Tunnel information
	 * @return array array with IP adresses and submasks. Returns null on error.
	 */
	private function getlocal($tunnel) {
		//getipfrominterface gets the IP and subnet from interface or settings. 
		// If it retuns null, no IP could be gotten from the interface
		$local = array ();
		$result = $this->getipfrominterface ( $local, "public-", ( string ) $tunnel->{'local-public-ip'}, null );
		if ($result == null) {
			Logger::getRootLogger ()->error ( "Could not fetch local public ip from " . ( string ) $tunnel->{'local-public-ip'} );
			return null;
		}
		
		$result = $this->getipfrominterface ( $local, "private-", ( string ) $tunnel->{'local-private-ip'}, ( string ) $tunnel->{'local-private-subnet'} );
		
		return $local;
	}
	
	/**
	 * Gets the remote IP tunnel information from settings. private-ip may be empty.
	 * @param SimpleXMLElement $tunnel Tunnel information
	 * @return array array with IP adresses and submasks. Returns null on error.
	 */
	private function getremote($tunnel) {
		//Remote box
		$remote ['public-ip'] = ( string ) $tunnel->{'remote-public-ip'}; //public/internet IP
		$remote ['private-ip'] = ( string ) $tunnel->{'remote-private-ip'}; //private/LAN
		if (strlen ( $remote ['private-ip'] ) > 0) {
			$remote ['private-subnet'] = ( string ) $tunnel->{'remote-private-subnet'}; //Internal subnet
			$remote ['private-subnetnr'] = 32 - log ( (ip2long ( $remote ['private-subnet'] ) ^ ip2long ( "255.255.255.255" )) + 1, 2 );
			$remote ['private-subnetid'] = long2ip ( ip2long ( $remote ['private-ip'] ) & ip2long ( $remote ['private-subnet'] ) );
		}
		return $remote;
	}
	
	/**
	 * 
	 * @param null|array $array Array to fill with IP information
	 * @param string $prefix Prefix to set keys with
	 * @param string $ip IP or interface. Possible values: wan|lan|ext[#]|xxx.xxx.xxx.xxx
	 * @param null|string $subnet If the $ip is not a interface name, this subnet will be used. May be null. 
	 * @return NULL|array
	 */
	private function getipfrominterface(&$array = array(), $prefix, $ip, $subnet) {
		try {
			
			if (empty ( $ip )) {
				return null;
			} elseif ($ip == 'wan' || $ip == 'lan') {
				//Get IP from wan or lan
				$interface = $this->framework->getPlugin ( $ip );
				if (empty ( $interface )) {
					return null; //error
				}
				$array [$prefix . 'ip'] = $interface->getIpAddress ();
				$array [$prefix . 'subnet'] = $interface->getSubnet ();
			} elseif (substr ( $ip, 0, 3 ) == 'ext') {
				//Get IP from Ext[#]
				$interface = $this->framework->getPlugin ( "ext" );
				if (empty ( $interface )) {
					return null; //error
				}
				
				//get the number from ext[#]
				$start = strpos ( $ip, "[" ) + 1;
				$length = strpos ( $ip, "]" ) - $start;
				$nr = substr ( $ip, $start, $length );
				$array [$prefix . 'ip'] = $interface->getIpAddress ( $nr );
				$array [$prefix . 'subnet'] = $interface->getSubnet ( $nr );
			} else { //elseif xxx.xxx.xxx.xxx
				//Setting is an IP adress
				$array [$prefix . 'ip'] = $ip;
				$array [$prefix . 'subnet'] = $subnet;
			} //else if adress -> lookup IP from address
			//else fail
						
			//check if a IP was found
			if (empty ( $array [$prefix . 'ip'] )) {
				return null;
			}
		} catch ( Expression $e ) {
			return null;
		}
		
		//Add additional subnet information if possible
		if (isset ( $array [$prefix . 'subnet'] )) {
			$array [$prefix . 'subnetnr'] = 32 - log ( (ip2long ( $array [$prefix . 'subnet'] ) ^ ip2long ( "255.255.255.255" )) + 1, 2 );
			$array [$prefix . 'subnetid'] = long2ip ( ip2long ( $array [$prefix . 'ip'] ) & ip2long ( $array [$prefix . 'subnet'] ) );
		}
		
		return $array;
	}
	
	/**
	 * Starts the plugin. If it is already running, the config will be created and reloaded in racoon.
	 */
	public function runAtBoot() {
		Logger::getRootLogger ()->info ( "Init IPSec" );
		
		if (( string ) $this->data ['enable'] == "true") {
			$result = $this->configure ();
			//Don't start if config fails.
			if ($result < 1) {
				$this->start ();
			} elseif ($result == 2) {
				Logger::getRootLogger ()->info ( "IPsec not starting due to IPsec configuration errors." );
			} //result 1 already reports that there are no tunnels in the logger.
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
				case 'addTunnel' :
					$this->saveTunnel ( null );
					break;
				case 'editTunnel' :
					$this->saveTunnel ( $_POST ['id'] );
					break;
				case 'delTunnel' :
					$this->delTunnel ( $_POST ['id'] );
					break;
				case 'save' :
					$this->save ();
					break;
				case 'addCertificate' :
					$this->addCertificate ();
					break;
				case 'delCertificate' :
					$this->delCertificate ();
					break;
				default :
					throw new Exception ( "page request not valid" );
			}
		} else {
			throw new Exception ( "page request not valid" );
		}
	}
	
	private function addCertificate($id) {
		//TODO: Add certificate. Do this todo as last. 
	

	}
	
	private function delCertificate($id) {
		$certificate = $this->getCertificate ( $id );
		
		if (isset ( $certificate )) {
			$this->config->deleteElement ( $certificate );
			
			if ($this->config->saveConfig ()) {
				//restart httpd.
				echo '<reply action="ok">';
				echo '<message>The system needs to reboot for settings to take effect.</message>';
				echo $this->data->asXML ();
				echo '</reply>';
				
				Logger::getRootLogger ()->info ( "Restarting IPsec" );
				$this->runAtBoot ();
			} else {
				throw new Exception ( "Error, could not save configuration file." );
			}
		}
	}
	
	/**
	 * Returns the tunnel with the same ID
	 * @param SimpleXMLElement $id
	 */
	private function getCertificate($id) {
		foreach ( $this->data->certificates->tunnel->certificate as $certificate ) {
			if ($certificate ['id'] == $id)
				return $certificate;
		}
		return null;
	}
	
	/**
	 * Remove IPSec tunnel 
	 * After saving the config, the HTTPD server will be restarted. 
	 */
	private function delTunnel($id) {
		if (empty ( $id )) {
			return;
		}
		
		$tunnel = $this->getTunnel ( $id );
		
		if (isset ( $tunnel )) {
			$this->config->deleteElement ( $tunnel );
			
			if ($this->config->saveConfig ()) {
				echo '<reply action="ok">';
				echo '<message>Tunnel removed.</message>';
				echo '</reply>';
				
				Logger::getRootLogger ()->info ( "Restarting IPsec" );
				$this->runAtBoot ();
			} else {
				throw new Exception ( "Error, could not save configuration file." );
			}
		}
	}
	
	/**
	 * Save tunnel IPSec settings 
	 * After saving the config, the HTTPD server will be restarted. 
	 */
	private function saveTunnel($id) {
		if (isset ( $id )) {
			$tunnel = $this->getTunnel ( $id );
		}
		if (empty ( $tunnel ) || empty ( $id )) {
			$tunnel = $this->data->addChild ( "tunnel" );
			$tunnel ['$id'] = time ();
		}
		
		//TODO: save tunnel info. Do this todo as last.
		//TODO: Check if the POST values are correct *headdesk, do this first smartass*
		

		//Set config
		$tunnel ['enable'] = $_POST ['enable'];
		$tunnel->{'local-private-ip'} = $_POST ['local-private-ip'];
		$tunnel->{'local-public-ip'} = $_POST ['local-public-ip'];
		$tunnel->{'local-private-subnet'} = $_POST ['local-private-subnet'];
		$tunnel->{'remote-private-ip'} = $_POST ['remote-private-ip'];
		$tunnel->{'remote-public-ip'} = $_POST ['remote-public-ip'];
		$tunnel->{'remote-private-subnet'} = $_POST ['remote-private-subnet'];
		$tunnel->phase1->{'exchange-mode'} = $_POST ['phase1-mode'];
		$tunnel->phase1->{'encryption-algorithm'} = $_POST ['phase1-encryption-algorithm'];
		$tunnel->phase1->{'hash-algorithm'} = $_POST ['phase1-hash-algorithm'];
		$tunnel->phase1->{'authentication-method'} ['type'] = $_POST ['phase1-authentication-method'];
		$tunnel->phase1->lifetime = $_POST ['phase1-lifetime'];
		$tunnel->phase1->dhgroup = $_POST ['phase1-dhgroup'];
		$tunnel->phase2->pfsgroup = $_POST ['phase2-pfsgroup'];
		$tunnel->phase2->lifetime = $_POST ['phase2-lifetime'];
		$tunnel->phase2->{'encryption-algorithm'} = $_POST ['phase2-encryption-algorithm'];
		$tunnel->phase2->{'authentication-algorithm'} = $_POST ['phase2-authentication-algorithm'];
		
		//Save config and print the data
		if ($this->config->saveConfig ()) {
			echo '<reply action="ok">';
			echo '<message>The system needs to reboot for settings to take effect.</message>';
			echo $this->data->asXML ();
			echo '</reply>';
			
			Logger::getRootLogger ()->info ( "Restarting IPsec" );
			$this->runAtBoot ();
		} else {
			throw new Exception ( "Error, could not save configuration file." );
		}
	}
	
	/**
	 * Returns the tunnel config with the same ID
	 * @param SimpleXMLElement $id
	 */
	private function getTunnel($id) {
		foreach ( $this->data->tunnel as $tunnel ) {
			if ($tunnel ['id'] == $id)
				return $tunnel;
		}
		return null;
	}
	/**
	 * Save IPSec global settings 
	 * After saving the config, the HTTPD server will be restarted. 
	 */
	private function save() {
		//Check if the POST values are correct
		

		//Set config
		if (strcasecmp ( $_POST ['enable'], "true" )) {
			$this->data ['enable'] = 'true';
		} elseif (strcasecmp ( $_POST ['enable'], "false" )) {
			$this->data ['enable'] = 'false';
		}
		
		//Save config and print the data
		if ($this->config->saveConfig ()) {
			//restart httpd.
			echo '<reply action="ok">';
			echo '<message>The system needs to reboot for settings to take effect.</message>';
			echo $this->data->asXML ();
			echo '</reply>';
			
			Logger::getRootLogger ()->info ( "Restarting IPsec" );
			$this->runAtBoot ();
		} else {
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
	 * @return string Status of the service/plugin
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
	 * Called at program shutdown. 
	 */
	public function shutdown() {
		$this->stop ();
	}
}
?>