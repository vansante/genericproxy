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
 * System plugin
 *
 * Encompasses system-wide functionality such as reboot, reset configuration, backup / restore
 * update firmware and general settings.
 *
 * @author Sebastiaan Gibbon
 * @version 0.0
 */

class System implements Plugin {
	
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
		$this->data = $this->config->getElement ( 'system' );
	}
	
	/**
	 * Returns whether or not the plugin is a service
	 * 
	 * @access public
	 * @return bool
	 */
	public function isService() {
		return false;
	}
	
	/**
	 * Start the service
	 * 
	 * @return bool false when service failed to start
	 */
	public function start() {
		//$this->start_ntp_client (); //Disabled as updating the time needs a internet connection. Wan interface is not configured yet.
	}
	
	/**
	 * Stop the service
	 * 
	 * @return bool false when service failed to stop
	 */
	public function stop() {}
	
	/**
	 * Write configuration to the system
	 * 
	 * @return int Returns status of configuration. 0 is ok, 1 or higher means an error occurd.
	 */
	public function configure() {
		$this->configure_ntp_client ();
	}
	
	/**
	 * Starts the plugin
	 */
	public function runAtBoot() {
		Logger::getRootLogger ()->info ( "Init System" );
		$this->configure ();
		$this->start ();
		
		//TODO: Check if the following is changed in the config, and if so then update the system: <hostname>, <domain>, <timezone>(also update php using date_default_timezone_set), <harddiskstandby>, etc. See config->system
		
	}
	
	/**
	 * Get info for a front-end page
	 * 
	 * @access public
	 * @throws Exception
	 */
	public function getPage() {
		if($_POST['page'] == 'getconfig'){
			$this->echoConfig();
		}
		elseif($_POST['page'] == 'savegeneralsettings'){
			$this->saveConfig();
		}
		elseif($_POST['page'] == 'reboot'){
			$this->reboot();
		}
		elseif($_POST['page'] == 'getconfigxml'){
			$this->backupConfig();
		}
		elseif($_POST['page'] == 'saveconfigxml'){
			$this->restoreConfig();
		}
		elseif($_POST['page'] == 'reset'){
			$this->resetToDefaults();
		}
		elseif($_POST['page'] == 'getservicesstatus'){
			$this->getServiceStatus();
		}
		elseif($_POST['page'] == 'getstatus'){
			$this->getSystemStatus();
		}
		else{
			throw new Exception('Invalid page request');
		}
	}
	
	/**
	 * 	Offers the config.xml file as a download
	 * 
	 *	@access private
	 */
	private function backupConfig(){
		$path = '/etc/genericproxy/config.xml';
		
		$size = filesize($path);
		header('Content-Type: application/octet-stream');
		header('Content-Length: '.$size);
		header('Content-Disposition: attachment; filename=config.xml');
		header('Content-Transfer-Encoding: binary');
		
		// open the file in binary read-only mode
		// display the error messages if the file can´t be opened
		$file = @ fopen($path, ‘rb’);
		
		if ($file) {
			// stream the file and exit the script when complete
			fpassthru($file);
		}
	}
	
	/**
	 * Overwrites config.xml with uploaded config.xml
	 * 
	 * Validates the uploaded config.xml for XML syntax errors but does not
	 * validate all configuration options for every module.
	 * 
	 * @access private
	 * @throws Exception
	 */
	private function restoreConfig(){
		if(!empty($_FILES['system_backrest_restorexml']['name'])){
			try{
				$newconfig = new Config($_FILES['system_backrest_restorexml']['tmp_name']);
				//	We're still here so the config loaded without any issues, copy it over
				Functions::mountFilesystem('mount');
				//	TODO keep a copy of the old config.xml for safety reasons?
				if(move_uploaded_file($_FILES['system_backrest_restorexml']['tmp_name'], '/cfg/GenericProxy/config.xml')){
					echo '<reply action="ok"><message>Your configuration has been loaded, you will need to reboot for the changes to take place. Alternatively you can now review the new configuration in the GUI.</message></reply>';
				}
				else{
					throw new Exception('There was an error uploading the file');
				}
				Functions::mountFilesystem('unmount');
			}
			catch(Exception $e){
				throw new Exception('The config file you uploaded contains XML errors');
			}
		}
		else{
			throw new Exception('No configuration file was uploaded');
		}
	}
	
	/**
	 * echoes XML containing system status
	 * 
	 * @access private
	 */
	private function getSystemStatus(){
		$buffer .= '<reply action="ok"><system>';
		
		//	Get the boot time
		$data = Functions::shellCommand('uptime');
		$data = explode(',',$data);
		
		$buffer .= '<uptime>'.$data[0].''.$data[1].'</uptime>';

		//	Get name
		$buffer .= '<name>'.(string)$this->data->hostname.'</name>';
		$buffer .= '<version>'.PluginFramework::VERSION.'</version>';

		//	Get processor 
		$cpu = str_replace(' Load averages: ',$data[3]);
		$cpu = explode(',',$cpu);
		
		$buffer .= '<cpu>';
		$buffer .= '<avg15>'.round($cpu[2] * 100).'</avg15>';
		$buffer .= '<avg5>'.round($cpu[1] * 100).'</avg5>';
		$buffer .= '<avg1>'.round($cpu[0] * 100).'</avg1>';
		$buffer .= '</cpu>';
		
		//	Get memory usage
		$totalram = str_replace('hw.physmem: ','',Functions::shellCommand('sysctl hw.physmem'));
		$totalram = ($totalram / (1024 * 1024));
		$usedram = ($totalram - Functions::getFreeMemory());
		$buffer .= '<memory><total>'.$totalram.'</total><used>'.$usedram.'</used></memory>';
		
		$buffer .= '</system></reply>';
		echo $buffer;
	}
	
	/**
	 * echoes XML containing the status of all running services
	 * 
	 * @access private
	 */
	private function getServiceStatus(){
		//	Load all plugins so we can get their service status
		$this->framework->startAllPlugins(false);
		
		$buffer = '<reply action="ok"><services>';
		foreach($this->framework->plugins as $plugin){
			if($plugin->isService() == true){
				$buffer .= '<service status="'.$plugin->getStatus().'">';
				$buffer .= '<name>'.get_class($plugin).'</name>';
				$buffer .= '</service>';
			}
		}
		$buffer .= '</services></reply>';
		echo $buffer;
	}
	
	/**
	 * Reboot the system
	 * 
	 * @access private
	 */
	private function reboot(){
		echo '<reply action="ok" />';
		Functions::shellCommand('shutdown -r now');
	}
	
	/**
	 * Resets the system config to defaults
	 * 
	 * Resets the system configuration by copying /etc/Genericproxy/default.config.xml to /cfg/GenericProxy/config.xml
	 * and automatically reboots the system afterwards
	 * 
	 * @access private
	 * @throws Exception
	 */
	private function resetToDefaults(){
		if(file_exists('/etc/GenericProxy/default.config.xml')){
			Functions::mountFilesystem('mount');
			Functions::shellCommand('cp /etc/GenericProxy/default.config.xml /cfg/GenericProxy/config.xml');
			Functions::mountFilesystem('unmount');
			echo '<reply action="ok" />';
			$this->reboot();
		}
		else{
			throw new Exception('The file containing the default configuration could not be loaded');
		}
	}
	
	/**
	 * echo XML configuration
	 */
	private function echoConfig(){
		echo '<reply action="ok"><system>';
		echo $this->data->hostname->asXML();
		echo $this->data->domain->asXML();
		echo $this->data->dnsservers->asXML();
		echo $this->data->dnsoverride->asXML();
		echo '</system></reply>';
	}
	
	/**
	 * Save XML configuration based on POST data from webGUI
	 * 
	 * Saves XML configuration for general settings front-end page
	 * 
	 * @throws Exception
	 */
	private function saveConfig(){
		$i = 1;
		while(!empty($_POST['system_genset_dns'.$i])){
			if(Functions::is_ipAddr($_POST['system_genset_dns'.$i])){
				$this->data->dnsservers->dnsserver[$i - 1]['ip'] = $_POST['system_genset_dns'.$i];
			}
			else{
				ErrorHandler::addError('formerror','system_genset_dns'.$i);
			}
			$i++;
		}
		
		$this->data->domain = $_POST['system_genset_domain'];
		if(Functions::is_hostname($_POST['system_genset_hostname'])){
			$this->data->hostname = $_POST['system_genset_hostname'];
		}
		else{
			ErrorHandler::addError('formerror','system_genset_hostname');
		}
		
		if($_POST['system_genset_dnsoverride'] == 'true'){
			$this->data->dnsoverride = 'allow';	
		}
		else{
			$this->data->dnsoverride = 'deny';
		}
		
		//			Edit username
		if(!empty($_POST['system_genset_username'])){
			if(!empty($_POST['system_genset_password1']) && ($_POST['system_genset_password1'] == $_POST['system_genset_password2'])){
				//		Check if this is an existing user
				foreach($this->data->users->user as $user){
					if(strtolower($user['name']) == strtolower($_POST['system_genset_username']) ){
						//		Check if we have the rights to edit this user (ROOT can overwrite passwords for recovery purposes)
						if(strtolower($_POST['system_genset_username']) == ($this->framework->user->name) || $this->framework->user->group == 'ROOT'){
							$user['password'] = crypt($_POST['system_genset_password1']);
							break;
						}
						else{
							ErrorHandler::addError('formerror','system_genset_username');
							throw new Exception('You do not have the rights to alter the password for the user '.$_POST['system_genset_username']);
						}
					}
				}			
			}
			else{
				ErrorHandler::addError('formerror','system_genset_password1');
				ErrorHandler::addError('formerror','system_genset_password2');
			}
		}
		
		if(ErrorHandler::errorCount() == 0){
			if($this->config->saveConfig()){
				echo '<reply action="ok">';
				echo $this->data->asXML();
				echo '</reply>';
			}
			else{
				throw new Exception('Configuration file could not be saved');
			}
		}
		else{
			throw new Exception('There is invalid form input');	
		}
	}
	
	/**
	 * Gets a list of plugin dependencies
	 */
	public function getDependency() {}
	
	/**
	 * Starts the plugin
	 * 
	 * @return string Status of the service/plugin
	 */
	public function getStatus() {}
	
	/**
	 * Shutsdown the Plugin.
	 * Called at program shutdown. 
	 */
	public function shutdown() {}
	
	/**
	 * Configure ntp client to update on regular intervals
	 * 
	 * @return int Returns 0 on success, >0 on error.
	 */
	private function configure_ntp_client() {
		Logger::getRootLogger ()->info ( "Setting/updating Ntp in Cron" );
		
		$cron = $this->framework->getPlugin ( "Cron" );
		if (empty ( $cron )) {
			Logger::getRootLogger ()->error ( 'Could not get Cron plugin.' );
			return 1;
		}
		
		//Only update time when time-update-interval is bigger then 0.
		if (( int ) $this->data->{'time-update-interval'} < 1) {
			if (isset ( $this->plugin->cronid )) {
				//Remove cron settings
				$job = $cron->getJob ( ( string ) $this->plugin->cronid );
				$this->config->deleteElement ( $job );
				$this->config->deleteElement ( $this->plugin->cronid );
			}
		} else {
			//TODO: Set correct time interval using $this->data->{'time-update-interval'} to cron time
			$minute = "0";
			$hour = "*";
			$mday = "*";
			$month = "*";
			$command = "ntpdate {$this->data->timeservers[0]}";
			
			if (empty ( $this->plugin->cronid )) {
				//create job
				$job = $cron->addJob ( $minute, $hour, $mday, $month, '*', 'root', $command );
				$this->plugin->cronid = ( string ) $job ['id'];
			} else {
				//Update job if changed.
				$job = $cron->getJob ( ( string ) $this->plugin->cronid );
				//if ($hour != ( string ) $job->hour || $mday != ( string ) $job->mday || $month != ( string ) $job->month || $command != ( string ) $job->command) {
				$job->minute = $minute;
				$job->hour = $hour;
				$job->mday = $mday;
				$job->month = $month;
				$job->command = $command;
				//$cron->configure (); //Starting Ntp plugin before Cron plugin, so not needed.
			//}
			}
		}
		return 0;
	}
	/**
	 * Update time during boot 
	 */
	private function start_ntp_client() {
		//Update time at boot if time-update-interval is bigger or equal to 0
		if (( int ) $this->data->{'time-update-interval'} >= 0) {
			Logger::getRootLogger ()->info ( "Updating time." );
			Functions::shellCommand ( "ntpdate {$this->data->timeservers[0]}" );
		}
	}
}
?>