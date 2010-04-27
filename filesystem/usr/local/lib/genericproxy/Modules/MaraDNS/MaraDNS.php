<?php
class MaraDNS implements Plugin {
	
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
	private $acl = array('ROOT','OP');
	
	/**
	 * Plugin configuration retrieved from $this->config->module
	 * 
	 * @var SimpleXMLElement
	 */
	private $plugin;
	
	/**
	 * Path where the MaraDNS configuration file will be saved to
	 * 
	 * @var String
	 */
	const CONFIG_PATH = '/etc/mararc';
	
	/**
	 * Path where MaraDNS will put the zone files
	 */
	const ZONEFILE_PATH = '/var/maradns/';
	
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
		$this->data = $this->config->getElement ( 'maradns' );
	}
	
	/**
	 * Returns whether or not the plugin is a service
	 * 
	 * @access public
	 * @return bool
	 */
	public function isService() {
		return true;
	}
	
	/**
	 * Configure the MaraDNS service
	 * 
	 */
	public function configure() {
		//	Check if the chroot dir exists, things turn ugly if it doesn't
		if (! is_dir ( '/var/maradns' )) {
			mkdir ( '/var/maradns' );
		}
		if(!is_dir(self::ZONEFILE_PATH)){
			mkdir(self::ZONEFILE_PATH);
		}
		
		//	Check if the db.wleiden.net file exists, if not we need to fetch the zone
		if (! file_exists ( self::ZONEFILE_PATH . 'db.' . $this->data->zone )) {
			$this->fetchZone ();
		}
		
		// 	If there is no cron job configured add one to reload the zone file every hour 
		if((string)$this->data->cron_id == ''){
			$cron = $this->framework->getPlugin('Cron');
			$job = $cron->addJob('*','*/1','*','*','*','root','/usr/local/bin/genericproxy MaraDNS fetchzone');
			
			$this->data->cron_id = (string)$job['id'];
			$this->config->saveConfig();
		}
		
		$listen ['localhost'] = '127.0.0.1';
		
		$lan = $this->framework->getPlugin ( 'Lan' );
		if ($lan != null) {
			$lanaddress = $lan->getIpAddress ();
			if (Functions::is_ipAddr ( $lanaddress )) {
				$listen ['lan'] = $lanaddress;
				
				$subnet = $lan->getSubnet();
				$subnet = Functions::prefix2mask($subnet);
				$lansubnet = Functions::calculateNetwork($lanaddress,$subnet);
			}
		}
		
		$ext = $this->framework->getPlugin ( 'Ext' );
		if ($ext != null) {
			$extaddress = $ext->getIpAddress ();
			if (Functions::is_ipAddr ( $extaddress )) {
				$listen ['ext'] = $extaddress;
			}
		}
		
		/*
		 *	MaraDNS config file
		 *	This config file is a 1:1 copy of all the settings (sans comments) from the 
		 *	wleiden config file
		 *
		 */
		$config = '
# Wleiden mararc file (abridged version)
# The various zones we support

# We must initialize the csv2 hash, or MaraDNS will be unable to
# load any csv2 zone files
csv2 = {}

# This is just to show the format of the file
csv2["wleiden.net."] = "db.wleiden.net"
ipv4_bind_addresses = "';
		$config .= implode ( ',', $listen );
		$config .= '"
chroot_dir = "/var/maradns"
maradns_uid = 53
maradns_gid = 53

maxprocs = 96

no_fingerprint = 0

default_rrany_set = 3

max_chain = 8
max_ar_chain = 1
max_total = 20

verbose_level = 1

ipv4_alias = {}

ipv4_alias["icann"]  = "198.41.0.4, 192.228.79.201, 192.33.4.12, 128.8.10.90,"
ipv4_alias["icann"] += "192.203.230.10, 192.5.5.241, 192.112.36.4,"
ipv4_alias["icann"] += "128.63.2.53, 192.36.148.17, 192.58.128.30,"
ipv4_alias["icann"] += "193.0.14.129, 199.7.83.42, 202.12.27.33"

ipv4_alias["opennic"]  = "157.238.46.24, 209.104.33.250, 209.104.63.249,"
ipv4_alias["opennic"] += "130.94.168.216, 209.21.75.53, 64.114.34.119,"
ipv4_alias["opennic"] += "207.6.128.246, 167.216.255.199, 62.208.181.95,"
ipv4_alias["opennic"] += "216.87.153.98, 216.178.136.116"

ipv4_alias["wleiden"] = "172.16.0.0/12"
ipv4_alias["localhost"] = "127.0.0.0/8"'."\n";
		
$recursive_acl[0] = 'localhost';
$recursive_acl[1] = 'wleiden';
$recursive_acl[2] = 'local';

if(!is_null($lansubnet)){
	$config .= 'ipv4_alias["local"] = "'.$lansubnet.'"'."\n";
}

$config .= 'recursive_acl = "'.implode(',',$recursive_acl).'"'."\n";

$config .= 'upstream_servers = {}

upstream_servers["."] = "8.8.8.8, 8.8.4.4"

ipv4_alias["hiddenonline"] = "65.107.225.0/24"
ipv4_alias["azmalink"] = "12.164.194.0/24"
spammers = "azmalink,hiddenonline"
';
		
		$fd = fopen ( self::CONFIG_PATH, 'w' );
		if ($fd !== false) {
			fwrite ( $fd, $config );
			fclose ( $fd );
			return true;
		} else {
			Logger::getRootLogger ()->error ( 'Could not open ' . self::CONFIG_PATH . ' for writing' );
			return false;
		}
	
	}
	
	/**
	 * Start the service
	 * 
	 * @return bool false when service failed to start
	 */
	public function start() {
		$dnsmasq = $this->config->getElement ( 'dnsmasq' );
		if ($dnsmasq ['enable'] == 'true') {
			Logger::getRootLogger ()->error ( 'dnsmasq module is also enabled, prevented loading of maradns' );
			return false;
		}
		
		if ($this->data ['enable'] == 'true') {
			Logger::getRootLogger()->info('Starting MaraDNS');
			if ($this->getStatus() == 'Stopped') {
				if(!file_exists('/var/log/maradns.log')){
					Functions::shellCommand('touch /var/log/maradns.log');
				}
				$status = Functions::shellCommand ( 'nohup /usr/local/bin/duende /usr/local/sbin/maradns -f ' . self::CONFIG_PATH .' > /var/log/maradns.log 2>&1 &');
				if ($status != 0) {
					Logger::getRootLogger ()->error ( 'MaraDNS failed to start' );
					return false;
				}
				return true;
			} else {
				Logger::getRootLogger ()->info ( 'MaraDNS was already running' );
				return false;
			}
		} else {
			Logger::getRootLogger ()->info ( 'MaraDNS disabled' );
		}
	}
	
	/**
	 * Stop the service
	 * 
	 * @return bool false when service failed to stop
	 */
	public function stop() {
		$dns_pid = Functions::shellCommand ( "ps ax | pgrep 'maradns'" );
		if (!empty ( $dns_pid )) {
			$this->logger->info ( 'Stopping MaraDNS' );
			Functions::shellCommand ( "kill $dns_pid" );
			return true;
		} else {
			$this->logger->info ( 'MaraDNS was terminated without it running' );
			return false;
		}
	}
	
	/**
	 * Fetches the wleiden zone through the shell script they made
	 * 
	 * @return Boolean	true on success, false on error
	 */
	public function fetchZone($return = false) {
		$status = $this->getStatus ();
		if ($status == 'Running') {
			$this->stop ();
		}
		//	Load the zone file
		$status = Functions::shellCommand ( 'fetchzone ' . $this->data->zone . ' ' . $this->data->server . ' > ' . self::ZONEFILE_PATH . 'db.' . $this->data->zone.' 2>&1 &');
		
		//	Restart MaraDNS if it was running when we started running
		if ($status == 'Running') {
			$this->start ();
		}
		
		if ($return) {
			echo '<reply action="ok" />';
		}
		return true;
	}
	
	/**
	 * Run during system shutdown
	 */
	public function shutdown() {
	
	}
	
	/**
	 * Run during system boot
	 */
	public function runAtBoot() {
		if ($this->data ['enable'] == 'true') {
			$this->configure ();
			$this->start ();
		}
	}
	
	/**
	 * Return the status of maraDNS
	 * 
	 * @return String Started|Error|Stopped
	 */
	public function getStatus() {
		$dns_pid = Functions::shellCommand ( "ps ax | pgrep 'maradns'" );
		if (! empty ( $dns_pid )) {
			return 'Started';
		} else {
			if ($this->data ['enabled'] == 'true') {
				return 'Error';
			} else {
				return 'Stopped';
			}
		}
	}
	
	/**
	 * Echo the configuration for the AJAX frontend
	 */
	private function echoConfig() {
		echo '<reply action="ok">';
		echo $this->data->asXML ();
		echo '</reply>';
	}
	
	/**
	 * Update configuration with data from the AJAX frontend
	 */
	private function saveConfig() {
		if (Functions::is_ipAddr ( $_POST ['services_dnsserv_server'] )) {
			ErrorHandler::addError ( 'form-error', 'services_dnsserv_server' );
		}
		if (empty ( $_POST ['services_dnsserv_zone'] )) {
			ErrorHandler::addError ( 'form-error', 'services_dnsserv_zone' );
		}
		
		if (ErrorHandler::errorCount () == 0) {
			$this->data->zone = $_POST ['services_dnsserv_zone'];
			$this->data->server = $_POST ['services_dnsserv_server'];
			if (isset ( $_POST ['services_dnsserv_enabled'] )) {
				if ($_POST ['services_dnsserv_enabled'] == 'true') {
					$this->data ['enable'] == 'true';
				} else {
					$this->data ['enable'] == 'false';
				}
			}
			
			$this->config->saveConfig ();
			
			echo '<reply action="ok">';
			echo $this->data->asXML ();
			echo '</reply>';
		} else {
			throw new Exception ( 'There is invalid form input' );
		}
	}
	
	/**
	 * 
	 */
	public function getPage() {
		if(in_array($_SESSION['group'],$this->acl)){
			switch ($_POST ['page']) {
				case 'getconfig' :
					$this->echoConfig ();
					break;
				case 'save' :
					$this->saveConfig ();
					break;
				case 'fetchzone' :
					$this->fetchZone ( true );
					break;
				default :
					throw new Exception ( 'Invalid page request' );
					break;
			}
		}
		else{
			throw new Exception('You do not have permission to do this');
		}
	}
	
	/**
	 * 
	 */
	public function getDependency() {
	}
}