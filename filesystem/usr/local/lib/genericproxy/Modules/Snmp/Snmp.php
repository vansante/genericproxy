<?php
class Snmp implements Plugin{
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
	 * Returns true if module launches / manages a service
	 * 
	 * @access public
	 * @return Boolean
	 */
	public function isService() {
		return true;
	}
	
	/**
	 * Contains configuration data retrieved from $this->config
	 * 
	 * @access private
	 * @var SimpleXMLElement
	 */
	private $data;
	
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
		
		//      Get snmp XML configuration
		$this->data = $this->config->getElement ( 'snmp' );
	}
	
	/**
	 * 	Configure the SNMP daemon
	 * 
	 * 	Writes out /usr/local/share/snmp/snmpd.conf
	 * 
	 * 	@access public
	 */
	public function configure() {
		$conf = "syslocation ".$this->data->location."\n
				 syscontact ".$this->data->contact."\n
				\n
				rocommmunity ".$this->data->rocommunity."\n
				rwcommunity ".$this->data->rwcommunity."\n
				\n
				master ".$this->data->master."\n";
	}

	/**
	 * 	Get dependencies
	 * 
	 * 	@access public
	 */
	public function getDependency() {}

	/**
	 * 	Passthrough function to delegate AJAX front-end functionality
	 * 
	 * 	This function is empty because SNMP has no front-end modules
	 * 
	 * 	@access public
	 */
	public function getPage() {}

	/**
	 * 	Get SNMP status
	 * 
	 * 	@access public
	 * 	@returns Error|Started|Stopped
	 * 	@todo	Implement
	 */
	public function getStatus() {

	}

	/**
	 * 	Commands to run during device boot
	 * 
	 * 	Called by PluginFramework during device boot
	 * 
	 * 	@access public
	 * 	@todo	implement
	 */
	public function runAtBoot() {
		Logger::getRootLogger()->info('Init SNMP');
	}

	/**
	 * 	Commands to run during shutdown
	 */
	public function shutdown() {}

	/**
	 * 	Start the SNMP daemon
	 * 
	 * 	@access public
	 * 	@todo	implement
	 */
	public function start() {}

	/**
	 * 	Stop the SNMP daemon
	 * 
	 * 	@access public
	 * 	@todo implement
	 */
	public function stop() {}

	
}
?>