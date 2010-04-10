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
 * DNS forwarding / Host override plugin
 * 
 * Plugin manages hosts file and dnsmasq service 
 *
 * @version 1.0
 */
class DnsForward implements Plugin{
/**
	 * Contains a reference to the configuration object
	 * 
	 * @var Config
	 * @access private
	 */
	private $config;
	
	/**
	 * 	Contains reference to the Logger object
	 * 
	 * 	@var Logger
	 * 	@access private
	 */
	private $logger;
	
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
	 * Path and filename to the lighttpd PID file
	 * 
	 * @var string
	 */
	const PID_PATH = '/var/run/dnsmasq.pid';
	
	/**
	 * Path and filename to the hosts file
	 * 
	 * @var string
	 */
	const HOST_PATH = '/var/etc/hosts';
	
	/**
	 * Return true if module launches / manages a service
	 * 
	 * @access public
	 * @return Boolean
	 */
	public function isService(){
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
	 * 
	 * @param Framework $framework
	 * @param Config $config
	 * @param Integer $runtype
	 */
	public function __construct($framework, $config, $options, $runtype){
		$this->config = $config;
		$this->runtype = $runtype;
		$this->framework = $framework;

		//      Get firewall XML configuration
		$this->data = $this->config->getElement ( 'dnsmasq' );
	}
	
	/**
	 * 	Configure DNS forwarding Daemon
	 * 
	 * 	@access public
	 * 	@return void
	 */
	public function configure() {
		Logger::getRootLogger()->info('Configuring DNS forwarding');
		$this->configureDnsMasq();
		$this->configureHosts();
	}
	
	/**
	 * writes out hosts to the HOST file
	 * 
	 * @access private
	 * @return void
	 */
	private function configureHosts(){
		Logger::getRootLogger()->info('Writing out host file');
		if((string)$this->data->dnsmasq['enable'] == 'true'){
			$fd = fopen(self::HOST_PATH,'w');
			if($fd !== false){
				foreach ($this->data->hosts as $host) {
					if ($host['host'])
						$hosts .= "{$host->ip}	{$host->host}.{$host->domain} {$host->host}\n";
					else
						$hosts .= "{$host->ip}	{$host->domain}\n";
				}
				
				$dhcpd = $this->config->getElement('Dhcpd');
				$system = $this->config->getElement('System');
				
				
				foreach($dhcpd->staticmaps->map as $map){
					$hosts .= "{$map['ipaddr']}	{$map['hostname']}.{$system->domain} {$host['hostname']}\n";
				}
				
				fwrite($fd,$hosts);
				fclose($fd);
			}
			else{
				Logger::getRootLogger()->error('Could not open hosts file');
			}
		}
	}
	
	/**
	 * Configures dnsmasq
	 * 
	 * @access private
	 * @return void
	 */
	private function configureDnsMasq(){
		Logger::getRootLogger()->info('Configuring dnsmasq');
		/* kill any running dnsmasq */
		$pid = file_exists ( self::PID_PATH ) ? Functions::shellCommand ( "pgrep -F " . self::PID_PATH ) : 0;
		if(file_exists ( self::PID_PATH )){
			Logger::getRootLogger()->info('Killing existing dnsmasq process');
			Functions::shellCommand("/bin/kill -s TERM ".$pid);
			sleep( 1 );
		}
		
		$args = null;
		if ((string)$this->data['enable'] == 'true'){
			Logger::getRootLogger()->info('Configuring DNS masking');
	
			if($this->data['regdhcpd'] == 'true'){
				$system = $this->config->getElement('system');
				if(file_exists(Dhcpd::LEASES_PATH)){
					$args = ' -l '.Dhcpd::LEASES_PATH.' -s '.$system->domain;
				}
				else{
					Logger::getRootLogger()->info('DHCP leases could not be registered into dnsmasq (missing lease file)');
				}
			}
	
        	foreach($this->data->overrides->override as $override) {
            	$args .= ' --server=/' . (string)$override->domain . '/' . (string)$override->ip;
			}
	
			/* run dnsmasq */
			$return = Functions::shellCommand("/usr/local/sbin/dnsmasq --local-ttl 1 --all-servers --dns-forward-max=5000 --cache-size=10000 {$args}");
		}
		else{
			Logger::getRootLogger()->info('DnsForward disabled');
		}
	}

	/**
	 * 	Passthrough function to delegate AJAX page requests to the proper function
	 * 
	 * @access public
	 * @throws Exception
	 */
	public function getPage(){
		if($_POST['page'] == 'save'){
			$this->saveSettings();
		}
		elseif($_POST['page'] == 'getconfig'){
			$this->getSettings();
		}
		elseif($_POST['page'] == 'deletemask'){
			$this->deleteMask();
		}
		elseif($_POST['page'] == 'addmask'){
			$this->addMask();
		}
		elseif($_POST['page'] == 'editmask'){
			$this->editMask();
		}
		elseif($_POST['page'] == 'deleteoverride'){
			$this->deleteOverride();
		}
		elseif($_POST['page'] == 'addoverride'){
			$this->addOverride();
		}
		elseif($_POST['page'] == 'editoverride'){
			$this->editOverride();
		}
		else{
			throw new Exception('Invalid page request');
		}
	}
	
	/**
	 * Check host input form
	 * 
	 * @return null
	 * @access private
	 * @throws Exception
	 */
	private function checkOverrideForm(){
		//todo: check form input
		if(!Functions::is_ipAddr($_POST['services_dnsf_override_ipaddr'])){
			ErrorHandler::addError('formerrror','services_dnsf_override_ipaddr');
			throw new Exception('There is invalid form input');
		}
	}
	
	/**
	 * Add host config to config.xml
	 * 
	 * @access private
	 */
	private function addOverride(){
		$error_buffer = $this->checkOverrideForm();
		$rule = $this->data->addChild('override');
		$rule->addAttribute('id',time());
		$rule->addChild('domain',$_POST['services_dnsmasq_domain']);
		$rule->addChild('ip',$_POST['services_dnsmasq_ip']);
		$rule->addChild('description',$_POST['descr']);
		
		echo '<reply action="ok"><dnsmasq><overrides>';
		echo $rule->asXML();
		echo '</overrides></dnsmasq></reply>';
	}
	
	/**
	 * Edit host specified in $_POST
	 * 
	 * @throws Exception
	 * @access private
	 */
	private function editOverride(){
		$this->checkMaskForm();
		if (isset ( $_POST ['services_dnsf_override_id'] ) && is_numeric ( $_POST ['services_dnsf_override_id'] )) {
			foreach ( $this->data->overrides->override as $rule ) {
				if (( string ) $rule['id'] == $_POST ['services_dnsf_override_id']) {
					//	Edit mask
					$rule->domain = $_POST['domain'];
					$rule->ip = $_POST['ip'];
					$rule->description = $_POST['descr'];
					
					echo '<reply action="ok"><dnsmasq><overrides>';
					echo $rule->asXML();
					echo '</overrides></dnsmasq></reply>';
					return 1;
				}
			}
			throw new Exception('The specified rule could not be found');
		} else {
			throw new Exception('getMask() called without a rule identifier');
		}
	}

	/**
	 * Delete host specified in $_POST
	 * 
	 * @throws Exception
	 * @access private
	 */
	private function deleteOverride(){
	if (isset ( $_POST ['overrideid'] ) && is_numeric ( $_POST ['overrideid'] )) {
			foreach ( $this->data->overrides->override as $rule ) {
				if (( string ) $rule['id'] == $_POST ['overrideid']) {
					//	Remove rule
					$this->config->deleteElement($rule);
					echo '<reply action="ok"></reply>';
					return 1;
				}
			}
			throw new Exception('The specified rule could not be found');
		} else {
			throw new Exception('deleteMask() called without a rule identifier');
		}
	}
	
	/**
	 * return XML settings
	 * 
	 * @access public
	 */
	public function getSettings(){
		echo '<reply action="ok">';
		echo $this->data->asXML();
		echo '</reply>';
	}
	
	/**
	 * 	Save the edited DNS forwarding
	 * 
	 * @access public
	 * @todo	finish
	 */
	public function saveSettings(){

	}
	
	/**
	 * Check Mask input form
	 * 
	 * @access private
	 * @throws Exception
	 */
	private function checkMaskForm(){
		
		if(Functions::is_ipAddr($_POST['services_dnsf_mask_ip'])){
			ErrorHandler::addError('formerror','services_dnsf_mask_ip');
		}

		if(!empty($_POST['services_dnsf_mask_domain'])){
			ErrorHandler::addError('formerror','services_dnsf_mask_domain');
		}
		
		if(!empty($_POST['services_dnsf_mask_host'])){
			ErrorHandler::addError('formerror','services_dnsf_mask_host');
		}
		
		if(ErrorHandler::errorCount() > 0){
			throw new Exception('There is invalid form input');
		}
	}
	
	/**
	 * Add new mask to config XML
	 * 
	 * @access private
	 */
	private function addMask(){
		$this->checkMaskForm();
		
		$rule = $this->data->addChild('host');
		$rule->addAttribute('id',time());
		$rule->addChild('domain',$_POST['services_dnsf_mask_domain']);
		$rule->addAttribute('name',$_POST['services_dnsf_mask_host']);
		$rule->addChild('ip',$_POST['services_dnsf_mask_ipaddr']);
		$rule->addChild('description',$_POST['services_dnsf_mask_descr']);
		
		echo '<reply action="ok"><dnsmasq><hosts>';
		echo $rule->asXML();
		echo '</hosts></dnsmasq></reply>';
		$this->config->saveConfig();
	}
	
	/**
	 * Edit mask specified in $_POST['maskID']
	 * 
	 * @throws Exception
	 * @access private
	 */
	private function editMask(){
		$this->checkMaskForm();
		if (isset ( $_POST ['services_dnsf_mask_id'] ) && is_numeric ( $_POST ['services_dnsf_mask_id'] )) {
			foreach ( $this->data->hosts->host as $rule ) {
				if (( string ) $rule['id'] == $_POST ['services_dnsf_mask_id']) {
					//	Edit mask
					$rule->domain = $_POST['services_dnsf_mask_domain'];
					$rule['name'] = $_POST['services_dnsf_mask_host'];
					$rule->ip = $_POST['services_dnsf_mask_ipaddr'];
					$rule->description = $_POST['services_dnsf_mask_descr'];
					
					echo '<reply action="ok"><dnsmasq><hosts>';
					echo $rule->asXML();
					echo '</hosts></dnsmasq></reply>';
					return 1;
				}
			}
			throw new Exception('The specified rule could not be found');
		} else {
			throw new Exception('getMask() called without a rule identifier');
		}
	}
	
	/**
	 * Delete the mask specified in $_POST['maskID']
	 * 
	 * @throws Exception
	 * @access private
	 */
	private function deleteMask(){
		if (isset ( $_POST ['maskid'] ) && is_numeric ( $_POST ['maskid'] )) {
			foreach ( $this->data->hosts->host as $rule ) {
				if (( string ) $rule['id'] == $_POST ['maskid']) {
					//	Remove rule
					$this->config->deleteElement($rule);
					echo '<reply action="ok"></reply>';
					return 1;
				}
			}
			throw new Exception('The specified rule could not be found');
		} else {
			throw new Exception('deleteMask() called without a rule identifier');
		}
	}
	
	/**
	 * 	Return dependencies
	 * 
	 * 	@access Public
	 * 	@return Array
	 */
	public function getDependency() {
		return null;
	}
	
	/**
	 * 	Return the status of the DNS forwarding daemon
	 * 
	 * 	@access public
	 * 	@return String
	 */
	public function getStatus() {
		$pid = file_exists ( self::PID_PATH ) ? Functions::shellCommand ( "pgrep -F " . self::PID_PATH ) : 0;
		if($pid > 0){
			return 'Started';
		}
		else{
			if($this->data['enable'] == 'true'){
				return 'Error';
			}
			else{
				return 'Stopped';
			}
		}
	}
	
	/**
	 * 	Start the DNS forwarding Daemon
	 * 
	 * 	@access public
	 * 	@return Boolean
	 */
	public function start() {
		$this->configureDnsMasq();
	}
	
	/**
	 * 	Initialize Plugin
	 * 
	 * 	@access public
	 * 	@return void
	 */
	public function runAtBoot() {
		$this->configure();
	}
	
	/**
	 * things to execute during system shutdown
	 * 
	 * @access public
	 * @return void
	 */
	public function shutdown(){}
	
	/**
	 * 	Stop DNS forwarding Daemon
	 * 
	 * 	@access public
	 * 	@return Boolean
	 */
	public function stop() {
		$pid = file_exists ( self::PID_PATH ) ? Functions::shellCommand ( "pgrep -F " . self::PID_PATH ) : 0;
		if($pid > 0){
			Logger::getRootLogger()->info('Killing existing dnsmasq process');
			Functions::shellCommand("/bin/kill -s TERM ".$pid);
			return true;
		}
		else{
			return false;
		}
	}
	
}