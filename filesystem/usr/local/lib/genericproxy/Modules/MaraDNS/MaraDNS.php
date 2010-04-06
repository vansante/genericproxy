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
		$this->data = $this->config->getElement ( 'dns' );
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
		/*
		 *	MaraDNS config file
		 *	This config file is a 1:1 copy of all the settings (sans comments) from the 
		 *	wleiden config file
		 *
		 * 	TODO make these settings changeable in the configuration xml
		 */
		$config = $cert = <<<EOD
# Example mararc file (unabridged version)
# The various zones we support

# We must initialize the csv2 hash, or MaraDNS will be unable to
# load any csv2 zone files
csv2 = {}

# This is just to show the format of the file
csv2["wleiden.net."] = "db.wleiden.net"
ipv4_bind_addresses = "<internalif>, 127.0.0.1"
chroot_dir = "/usr/local/etc/maradns"
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

ipv4_alias["azmalink"] = "12.164.194.0/24"
spammers = "azmalink,hiddenonline"

EOD;
	}
	
	/**
	 * Start the service
	 * 
	 * @return bool false when service failed to start
	 */
	public function start() {
		
	}
	
	/**
	 * Stop the service
	 * 
	 * @return bool false when service failed to stop
	 */
	public function stop() {}
	
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
	 * 
	 */
	public function getStatus() {}

	/**
	 * 
	 */
	public function getPage() {}

	/**
	 * 
	 */
	public function getDependency() {}
}