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
		
	}
	
	public function debuglan(){
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
	}
	
	public function debugps(){
		echo Functions::shellCommand('ps -ax');
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
	public function configure() {}
	public function start() {}
	public function stop() {}
	public function shutdown() {}
	public function runAtBoot() {}
	public function getStatus() {}
	private function echoConfig() {}
	private function saveConfig() {}
	public function getPage() {}
	public function getDependency() {}
}
?>