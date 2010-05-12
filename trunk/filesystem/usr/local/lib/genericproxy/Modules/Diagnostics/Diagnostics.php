<?php
/**
 *	Contains diagnostic utilities 
 *
 *	Contains diagnistic utilities such as ping / traceroute hooks for
 *	the AJAX frontend. Expand this class with other diagnostic functions
 *	in favor of creating separate plugins when possible.
 */
class Diagnostics implements Plugin{
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
		return false;
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
		ignore_user_abort (false);
		$this->config = $config;
		$this->runtype = $runtype;
		$this->framework = $framework;
	}
	
	/**
	 * @access public
	 * @return null
	 */
	public function getDependency() {
		return null;
	}

	/**
	 * Passthrough function for Diagnostics submenu pages in the frontend
	 * 
	 * @access public
	 * @throws Exception
	 */
	public function getPage() {
		switch($_POST['page']){
			case 'ping':
				$this->doPing();
				break;
			case 'traceroute':
				$this->doTraceRoute();
				break;
			case 'getbootlog':
				$this->getBootLog();
				break;
			case 'gethttpdlog':
				$this->getLog('lighttpd.error.log','httpdlog');
				break;
			case 'getbrowserlog':
				$this->getBrowserLog();
				break;
			case 'nmap':
				$this->doNmap();
				break;
			default:
				throw new Exception('Invalid page request');
				break;
		}
	}
	
	/**
	 * 	return the log file specified to the browser
	 */
	private function getLog($logfile,$name = null){
		if(file_exists('/var/log/'.$logfile)){
			$log = file_get_contents('/var/log/'.$logfile);

			$buffer = '<reply action="ok">
					<'.$name.'>
					<![CDATA['.$log.']]>
					</'.$name.'>
			</reply>';
			
			echo $buffer;
		}
		else{
			throw new Exception('The specified log file does not exist');
		}
	}
	
	/**
	 * return the browser log to the AJAX frontend for viewing
	 * 
	 * the browser log contains the logs for the framework during browser mode
	 * 
	 * @throws Exception
	 */
	private function getBrowserLog(){
		if(file_exists('/var/log/www.browser.log')){
			$log = file_get_contents('/var/log/www.browser.log');

			$buffer = '<reply action="ok">
					<browserlog>
					<![CDATA['.$log.']]>
					</browserlog>
			</reply>';
			
			echo $buffer;
		}
		else{
			throw new Exception('The log file could not be found');
		}
	}
	
	/**
	 *	return the Boot log to the AJAX frontend for viewing
	 *
	 *	the boot log 
	 *
	 *	@throws Exception
	 */
	private function getBootLog(){
		if(file_exists('/var/log/www.boot.log')){
			$log = file_get_contents('/var/log/www.boot.log');

			$buffer = '<reply action="ok">
					<bootlog>
					<![CDATA['.$log.']]>
					</bootlog>
			</reply>';
			
			echo $buffer;
		}
		else{
			throw new Exception('The log file could not be found');
		}
	}
	
	/**
	 * Passthrough function to execute nmap from the frontend
	 * 
	 * @access private
	 * @throws Exception
	 */
	private function doNmap(){
		
		if(ErrorHandler::errorCount() == 0){
			$buffer .= '<reply action="ok"><nmap><result>';
			$buffer .= Functions::shellCommand('nmap '.$_POST['diagnostics_nmap_options']);
			$buffer .= '</result></nmap></reply>';
			
			echo $buffer;
		}
		else{
			throw new Exception('There is invalid form input');
		}
	}
	
	/**
	 * Executes a traceroute shellcommand based on input from the front-end
	 * 
	 * @access private
	 * @throws Exception
	 */
	private function doTraceRoute(){
		if(!Functions::is_ipAddr($_POST['diagnostics_tracert_host']) && !Functions::is_Url($_POST['diagnostics_ping_host'])){
			ErrorHandler::addError('formerror','diagnostics_tracert_host');
		}
		if(!is_numeric($_POST['diagnostics_tracert_maxhops'])){
			ErrorHandler::addError('formerror','diagnostics_tracert_maxhops');
		}
		
		if(ErrorHandler::errorCount() == 0){
			if($_POST['diagnostics_tracert_use_icmp'] == 'true'){
				$icmp = '-I ';
			}
			$buffer .= '<reply action="ok"><traceroute><result>';
			$buffer .= Functions::shellCommand('traceroute '.$icmp.'-m '.$_POST['diagnostics_tracert_maxhops'].' '.$_POST['diagnostics_tracert_host']);
			$buffer .= '</result></traceroute></reply>';
			
			echo $buffer;
		}
		else{
			throw new Exception('There is invalid form input');
		}
	}
	
	/**
	 * Executes a ping shellcommand based in input from the front-end
	 * 
	 * @access private
	 * @throws Exception
	 */
	private function doPing(){
		if(!Functions::is_ipAddr($_POST['diagnostics_ping_host']) && !Functions::is_Url($_POST['diagnostics_ping_host'])){
			ErrorHandler::addError('formerror','diagnostics_ping_host');
		}
		if(strlen($_POST['diagnostics_ping_interface']) && $_POST['diagnostics_ping_interface'] != 'wan'
				&& $_POST['diagnostics_ping_interface'] != 'lan' && $_POST['diagnostics_ping_interface'] != 'ext'){
			ErrorHandler::addError('formerror','diagnostics_ping_interface');
		}
		if(!is_numeric($_POST['diagnostics_ping_count'])){
			ErrorHandler::addError('formerror','diagnostics_ping_count');
		}
		
		if(ErrorHandler::errorCount() == 0){
			if($_POST['diagnostics_ping_count'] > 0 && $_POST['diagnostics_ping_count'] < 200){
				$count = $_POST['diagnostics_ping_count'];
			}
			else{
				$count = 10;
			}
			
			$interface = '';
			if (strlen($_POST['diagnostics_ping_interface'])) {
				$iface = $this->framework->getPlugin(ucfirst($_POST['diagnostics_ping_interface']));
				if($iface != null){
					$interface = ' -S '.$iface->getIpAddress().' ';
				}
			}
			
			$buffer .= '<reply action="ok"><ping><result>';
			$buffer .= Functions::shellCommand('ping -c '.$count.$interface.' '.$_POST['diagnostics_ping_host']);
			$buffer .= '</result></ping></reply>';
			
			echo $buffer;
		}
		else{
			throw new Exception('There is invalid form input');
		}
	}

	/*
	 *	Functions below remain empty as this plugin	manages no services and 
	 *	offers no back-end or boot-time functionality at this time. 
	 */	
	public function runAtBoot() {}
	public function shutdown() {}
	public function start() {}
	public function stop() {}
	public function getStatus(){}
	public function configure() {}

	
}
?>