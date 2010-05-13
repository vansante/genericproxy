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
 *	Ipsec Plugin
 *
 * 	Manages Racoon / Setkey configurations and Ipsec Front-end functionality
 */
class Ipsec implements Plugin{
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
	 * 	Webinterface access control list
	 * 
	 * 	@access private
	 * 	@var 	Array
	 */
	private $acl = array('ROOT','OP');
	
	/**
	 * path and filename to the IPSec config file
	 * 
	 * @var string
	 */
	const CONFIG_PATH = '/var/etc/racoon.conf';
	
	/**
	 * Path and filename to the lighttpd PID file
	 * 
	 * @var string
	 */
	const PID_PATH = '/var/run/racoon.pid';
	
	/**
	 * Path to the pre shared key file
	 * 
	 * @var string
	 */
	const PKS_PATH = '/var/etc/psk';
	
	/**
	 * Path to where certificates are stored
	 * 
	 * @var string Path with trailing slash
	 */
	const CERT_PATH = '/var/etc/cert';
	
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
	 * 	Stop the racoon daemon
	 * 
	 * 	@access public
	 * 	@return boolean
	 */
	public function stop() {
		Logger::getRootLogger ()->info ( "Stopping IPsec" );
		$pid = file_exists ( self::PID_PATH ) ? Functions::shellCommand ( "pgrep -F " . self::PID_PATH ) : 0;
		if ($pid > 0) {
			Functions::shellCommand ( "/bin/kill {$pid}" );
		}
	}

	/**
	 * Start the racoon daemon
	 * 
	 * @access public
	 * @return boolean
	 */
	public function start() {
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
	 * 	Commands to run at shutdown
	 */
	public function shutdown() {
		$this->stop();
	}

	/**
	 * 	Commands to run at boot time
	 */
	public function runAtBoot() {
		Logger::getRootLogger()->info('Init IPSec');
		if((string)$this->data['enable'] == 'true'){
			$configured = $this->configure();
			if($configured){
				$this->start();
			}
			else{
				Logger::getRootLogger()->info('IPSec not started');
			}
		}
	}

	/**
	 * Returns whether or not this plugin maintains a daemon / process
	 * 
	 * @return boolean
	 */
	public function isService() {
		return true;
	}

	/**
	 * Get Racoon's status
	 * 
	 * @return String Started|Stopped
	 */
	public function getStatus() {
		$pid = Functions::shellCommand('pgrep racoon');
		if($pid != null){
			return 'Started';
		}
		else{
			if((string)$this->data['enabled'] == 'true'){
				return 'Error';
			}
			else{
				return 'Stopped';
			}
		}
	}

	/**
	 *	Passthrough function for AJAX webGUI 
	 */
	public function getPage() {
		switch($_POST['page']){
			case 'getconfig':
				$this->returnConfig();
			case 'addkey':
				$this->addPresharedkey();
			case 'editkey':
				$this->editPreSharedkey();
		}
	}
	
	/**
	 * Return the XML configuration
	 */
	private function returnConfig(){
		echo '<reply action="ok">';
		echo $this->data->asXML();
		echo '</reply>';
	}
	
	/**
	 * Save global IPSEC settings
	 */
	private function save(){
		if(!isset($_POST['services_ipsec_settings_enabled'])){
			$this->data['enable'] = 'false';
		}
		elseif($_POST['services_ipsec_settings_enabled'] == 'true'){
			$this->data['enable'] = 'true';
		}
		
		$this->config->saveConfig();
		$this->returnConfig();
	}
	
	private function checkPresharedkeyForm(){
		if(!isset($_POST['services_ipsec_key_pskey'])){
			ErrorHandler::addError('formerror','services_ipsec_key_pskey');
		}
		
		if(ErrorHandler::errorCount() > 0){
			throw new Exception('There is invalid form input');
		}
	}
	
	private function removePresharedkey(){
		if(isset($_POST['services_ipsec_key_id']) && is_numeric($_POST['services_ipsec_key_id'])){
			//	Find and remove the key in question
			$removed = false;
			foreach($this->data->keys->key as $key){
				if((string)$key['id'] == $_POST['services_ipsec_key_id']){
					$this->config->deleteElement($key);
					$removed = true;
					break;
				}
			}
			
			if($removed){
				//	Check if it was in use by any tunnels
				foreach($this->data->tunnels->tunnel as $tunnel){
					if((string)$tunnel->{'authentication-method'}['type'] == 'psk' && (string)$tunnel->{'authentication-method'} == $_POST['services_ipsec_key_id']){
						$warning = true;
						$tunnel->{'authentication-method'} = '';
					}
				}
								
				echo '<reply action="ok">';
				if($warning){
					echo '<message>The key you just removed was still in use by one or more tunnels. It has been removed from their configuration.</message>';
				}
				echo '</reply>';
				
				$this->config->saveConfig();
			}
			else{
				throw new Error('The specified key does not exist');
			}
		}
		else{
			throw new Error('Invalid key identifier specified');
		}
	}
	
	/**
	 * Edit a pre-shared key in the configuration
	 */
	private function editPresharedkey(){
		$this->checkPresharedkeyForm();
		
		if(isset($_POST['services_ipsec_key_id']) && is_numeric($_POST['services_ipsec_key_id'])){
			foreach($this->data->keys->key as $key){
				if((string)$key['id'] == $_POST['services_ipsec_key_id']){
					$key['descr'] = htmlentities($_POST['services_ipsec_key_descr']);
					$key->content = '<[!CDATA['.$_POST['services_ipsec_key_pskey'].']]>';
					
					$this->saveConfig();
					echo '<reply action="ok"><ipsec><keys>';
					echo $key->asXML();
					echo '</keys></ipsec></reply>';
					break;
				}
			}
		}
		else{
			throw new Error('Invalid key identifier specified');
		}
	}
	
	/**
	 * 	Add a pre-shared key to the configuration
	 */
	private function addPresharedkey(){
		$this->checkPresharedkeyForm();
		$newkey = $this->data->keys->addChild('key');
		$newkey->addAttribute('id',time());
		$newkey->addAttribute('description', htmlentities($_POST['services_ipsec_key_descr']));
		$newkey->addChild('content','<[!CDATA['.$_POST['services_ipsec_key_pskey'].']]>');
		
		echo '<reply action="ok"><ipsec><keys>';
		$newkey->asXML();
		echo '</keys></ipsec></reply>';
		$this->returnConfig();
	}

	/**
	 * Get the plugin dependencies
	 */
	public function getDependency() {}

	/**
	 * 
	 * TODO: Finish
	 */
	public function configure() {
		Logger::getRootLogger()->info('Configuring IPSec');
		//	Check nat traversal setting
		if((string)$this->data['nat_traversal'] == 'true'){
			$nat_t = 'on';
		}
		else{
			$nat_t = 'off';
		}
		
		//	PSK file
		$psk = '';
		
		//	Setkey configuration
		$setkey = "flush;\n";
		$setkey .= "spdflush;\n";
		
		//	Racoon.conf
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
		if ($nat_t == 'on') {
			$ipsec .= "        natt_keepalive  15 sec;\n";
		}
		$ipsec .= <<<EOD
        phase1          30 sec;
        phase2          15 sec;
}

EOD;

		//	Generate configuration for each tunnel
		foreach($this->data->tunnels->tunnel as $tunnel){
			if((string)$tunnel['enable'] == 'false'){
				continue;
			}

			if((string)$this->data['nat_traversal'] == 'true'){
				$nat_traversal = 'on';
			}
			else{
				$nat_traversal = 'off';
			}
			
			//	Parse my identifier
			if((string)$tunnel->phase1->identifier['type'] == 'ipaddr'){
				$my_identifier = 'address '.(string)$tunnel->phase1->identifier;
			}
			elseif((string)$this->phase1->identifier['type'] == 'my_ip'){
				$wan = $this->framework->getPlugin('Wan');
				if($wan != null){
					$my_identifier = 'address '.$wan->getIpAddress();
				}
			}
			elseif((string)$tunnel->phase1->identifier['type'] == 'domainname'){
				//	TODO: Find out what we enter here
			}
			elseif((string)$tunnel->phase1->identifier['type'] == 'fqdn'){
				//	TODO: Find out what we enter here
			}
			elseif((string)$tunnel->phase1->identifier['type'] == 'dyndns'){
				//	TODO: Find out what we enter here
			}
			
			//	Parse local part
			if((string)$tunnel->local->type == 'lan_subnet'){
				$lan = $this->framework->getPlugin('Lan');
				if($lan != null){
					$subnet = Functions::prefix2mask($lan->getSubnet());
					$ip = $lan->getIpAddress();
								
					//	Calculate network
					$network = Functions::calculateNetwork($ip,$subnet);
					$local['subnet'] = $lan->getSubnet();
					$local['network'] = $network;
				}
				else{
					Logger::getRootLogger()->error('Error during tunnel configuration, could not load Lan plugin');
				}
			}
			elseif((string)$tunnel->local->type == 'ipaddr'){
				$local['network'] = (string)$tunnel->local->{'private-ip'};
				$local['subnet'] = '32';
			}
			elseif((string)$tunnel->local->type == 'network'){
				$local['network'] = (string)$tunnel->local->{'private-ip'};
				$local['subnet'] = (string)$tunnel->local->{'private_subnet'};
			}
			
			$ipsec .= <<<EOD
remote  {$tunnel->remote->{'public-ip'}} [500]
{
        exchange_mode   {$tunnel->phase1->{'exchange-mode'}};
        doi             ipsec_doi;
        situation       identity_only;
        my_identifier   {$my_identifier};
        peers_identifier	address {$tunnel->remote{'public-ip'}};
        lifetime        time 8 hour;
        passive         off;
        proposal_check  obey;
        nat_traversal   {$nat_traversal};
        generate_policy off;
        
        proposal {
EOD;
        	$ipsec .= "                encryption_algorithm    ".str_replace('|',', ',(string)$tunnel->phase1->{'encryption-algorithm'}).";";
        	$ipsec .= "                hash_algorithm    ".str_replace('|',', ',(string)$tunnel->phase1->{'hash-algorithm'}).";";
        	
        	if($tunnel->phase1->{'authentication-method'}['type'] == 'psk'){
        		$authentication_method = 'pre_shared_key';
        	}
        	elseif($tunnel->phase1->{'authentication-method'}['type'] == 'rsasig'){
        		$authentication_method = 'rsasig';
        	}
        	
        	$ipsec .= "                authentication_method    ".$authentication_method.";\n";
        	$ipsec .= "                lifetime time    ".(string)$tunnel->phase1->lifetime.";\n";
        	$ipsec .= "                dh_group    ".(string)$tunnel->phase1->dhgroup.";\n";

			//Add certificate information to the proposal
			if ($tunnel->phase1->{'authentication-method'}['type'] == 'rsasig') {
				//		TODO: Add x509 certificate type
				$ipsec .= "                certificate_type plain_rsa \"{$certificate->private}\"\n";
				$ipsec .= "                peers_certfile plain_rsa \"{$certificate->public}\"\n";
			}
			
			//Close proposal and remote
			$ipsec .= "\t}\n}\n";
			
			//Create sainfo
			$ipsec .= "sainfo  (address {$local['network']}/{$local['subnet']} any address ".$tunnel->remote->{'private-ip'}."/".$tunnel->remote->{'private_subnet'}." any)";
			$ipsec .= <<<EOD
{
        pfs_group                {$tunnel->phase2->pfsgroup};
        lifetime time            {$tunnel->phase2->lifetime};
EOD;
			$ipsec .= "        encryption_algorithm     ".str_replace('|',', ',$tunnel->phase2->{'encryption-algorithm'}).";\n";
			$ipsec .= "        authentication_algorithm ".$tunnel->phase2->{'authentication-algorithm'}.";\n";
			$ipsec .= <<<EOD
        compression_algorithm   deflate;
}

EOD;
			
			if((string)$tunnel->phase1->{'authentication-method'} == 'psk'){
				
			}
			elseif((string)$tunnel->phase1->{'authentication-method'} == 'rsasig'){
				
			}
		}
	
	}

	
	
}
?>