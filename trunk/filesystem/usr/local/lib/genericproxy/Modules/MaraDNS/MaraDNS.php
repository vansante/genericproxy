<?php
class MaraDNS implements Plugin{
	
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
		if(!is_dir('/var/maradns')){
			mkdir('/var/maradns');
		}
		
		//	Check if the db.wleiden.net file exists, if not we need to fetch it
		if(!file_exists('/var/maradns/db.wleiden.nl')){
			$this->fetchZone();
		}
		
		/*
		 *	MaraDNS config file
		 *	This config file is a 1:1 copy of all the settings (sans comments) from the 
		 *	wleiden config file
		 *
		 * 	TODO make these settings changeable in the configuration xml
		 */
		$config = $cert = <<<EOD
# Wleiden mararc file (abridged version)
# The various zones we support

# We must initialize the csv2 hash, or MaraDNS will be unable to
# load any csv2 zone files
csv2 = {}

# This is just to show the format of the file
csv2["wleiden.net."] = "db.wleiden.net"
ipv4_bind_addresses = "<internalif>, 127.0.0.1"
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
ipv4_alias["localhost"] = "127.0.0.0/8"
recursive_acl = "localhost, wleiden"

upstream_servers = {}

upstream_servers["."] = "8.8.8.8, 8.8.4.4"

ipv4_alias["hiddenonline"] = "65.107.225.0/24"
ipv4_alias["azmalink"] = "12.164.194.0/24"
spammers = "azmalink,hiddenonline"

EOD;
		$fd = fopen(self::CONFIG_PATH,'w');
		if($fd !== false){
			fwrite ( $fd, $config );
			fclose ( $fd );
			return true;
		}
		else{
			Logger::getRootLogger()->error('Could not open '.self::CONFIG_PATH.' for writing');
			return false;
		}
		
	}
	
	/**
	 * Start the service
	 * 
	 * @return bool false when service failed to start
	 */
	public function start() {
		$dnsmasq = $this->config->getElement('dnsmasq');
		if($dnsmasq['enable'] == 'true'){
			Logger::getRootLogger()->error('dnsmasq module is also enabled, prevented loading of maradns');
			return false;
		}
		
		$dns_pid = Functions::shellCommand("ps ax | egrep '/usr/sbin/maradns' | awk '{print $1}'");
		if($dns_pid == "") {
			$status = Functions::shellCommand('maradns -f '.self::CONFIG_PATH);
			if($status != 0){
				Logger::getRootLogger()->error('MaraDNS failed to start');
				return false;
			}
			return true;
		}
		else{
			$this->logger->info('MaraDNS was already running');
			return false;
		}
		
	}
	
	/**
	 * Stop the service
	 * 
	 * @return bool false when service failed to stop
	 */
	public function stop() {
		$dns_pid = Functions::shellCommand("ps ax | egrep '/usr/sbin/maradns' | awk '{print $1}'");
		if($dns_pid <> "") {
			$this->logger->info('Stopping MaraDNS');
			Functions::shellCommand("kill $dns_pid");
			return true;
		}
		else{
			$this->logger->info('MaraDNS was terminated without it running');
			return false;
		}
	}
	
	/**
	 * Fetches the wleiden zone through the shell script they made
	 * 
	 * @return Boolean	true on success, false on error
	 */
	private function fetchZone($return = false){
		//	Load the zone file through wleiden's script 
		Logger::getRootLogger()->info('We need to fetch a zone here, checking if wleiden caused the infinite loop');
		#$status = Functions::shellCommand('sh /usr/local/lib/genericproxy/Modules/MaraDNS/fetchzone.sh');
		if(stristr($status,'[ERROR]')){
			if($return){
				throw new Exception('The zone file could not be retrieved');
			}
			else{
				Logger::getRootLogger()->error('The zone file could not be retrieved');
			}
			return false;
		}
		
		//	Restart MaraDNS if it's running
		if($this->getStatus == 'Running'){
			$this->stop();
			$this->start();
		}
		
		if($return){
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
		$this->configure();
		$this->start();
	}

	/**
	 * Return the status of maraDNS
	 * 
	 * @return String Started|Error|Stopped
	 */
	public function getStatus() {
		$dns_pid = Functions::shellCommand("ps ax | egrep '/usr/sbin/maradns' | awk '{print $1}'");
		if($dns_pid <> "") {
			return 'Started';
		}
		else{
			if($this->data['enabled'] == 'true'){
				return 'Error';
			}
			else{
				return 'Stopped';
			}
		}
	}

	/**
	 * Echo the configuration for the AJAX frontend
	 */
	private function echoConfig(){
		echo '<reply action="ok">';
		echo $this->data->asXML();
		echo '</reply>';
	}
	
	/**
	 * Update configuration with data from the AJAX frontend
	 */
	private function saveConfig(){
		if(Functions::is_ipAddr($_POST['services_dnsserv_server'])){
			ErrorHandler::addError('form-error','services_dnsserv_server');
		}
		if(empty($_POST['services_dnsserv_zone'])){
			ErrorHandler::addError('form-error','services_dnsserv_zone');
		}
		
		if(ErrorHandler::errorCount() == 0){
			$this->data->zone = $_POST['services_dnsserv_zone'];
			$this->data->server = $_POST['services_dnsserv_server'];
			$this->saveConfig();

			echo '<reply action="ok">';
			echo $this->data->asXML();
			echo '</reply>';
		}
		else{
			throw new Exception('There is invalid form input');
		}
	}
	
	/**
	 * 
	 */
	public function getPage() {
		switch($_POST['page']){
			case 'getconfig':
				$this->echoConfig();
				break;
			case 'saveconfig':
				$this->saveConfig();
				break;
			case 'fetchzone':
				$this->fetchZone(true);
				break;
			default:
				throw new Exception('Invalid page request');
				break;
		}
	}

	/**
	 * 
	 */
	public function getDependency() {}
}