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
 * Firewall plugin
 * 
 * Configures and manages the firewall (pf)

 * @version 1.0
 */

class Firewall implements Plugin {
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
		$this->logger = Logger::getRootLogger ();

		//      Get firewall XML configuration
		$this->data = $this->config->getElement ( 'firewall' );
	}

	/**
	 *	Get dependencies
	 *
	 *  Get the modules this module depends on
	 *
	 *  @access public
	 *  @return null
	 */
	public function getDependency() {
		return null;
	}

	/**
	 *	Get service status
	 *
	 *	Get status of the service this module loads (null if none)
	 *
	 *	@access public
	 *	@return String $status
	 */
	public function getStatus() {
		if ($this->data['enable'] == 'true') {
			return 'Started';
		} else {
			return 'Stopped';
		}
	}

	/**
	 * Commands to execute during system shutdown
	 * 
	 * @access public
	 */
	public function shutdown(){}
	
	/**
	 *	start / intialize the Plugin
	 *
	 *	@access public
	 *	@return void
	 */

	public function runAtBoot() {
		$this->logger->debug("Firewall is enabled? ".(string)$this->data['enable']);
		//  Only start if ipfw is set to enabled
		if ((string)$this->data['enable'] == "true") {
			$this->logger->info ( 'Init Firewall' );
			$this->configure ();
			$this->start ();
		}
	}

	/**
	 *	Returns XML for the requested page
	 *
	 *	page requested is deduced from the $_POST['page'] variable included by the AJAX frontend.
	 *	Unlike other modules with access restriction Firewall does not have an ACL since all parties
	 *	can access the page, but what rules they can see / alter depends on their user group.
	 *
	 *	@access public
	 *	@throws Exception
	 */
	public function getPage() {
		if (isset ( $_POST ['page'] )) {
			switch ($_POST ['page']) {
				case 'getconfig' :
					$this->echoRules ();
					break;
				case 'addrule' :
					$this->addUserRule ();
					break;
				case 'editrule' :
					$this->editRule ();
					break;
				case 'deleterule' :
					$this->removePostRule ();
					break;
				case 'togglerule' :
					$this->toggleRule();
					break;
				case 'swaprule':
					$this->swapRules();
					break;
				case 'reloadrules':
					$this->configure();
					echo '<reply action="ok" />';
					break;
				default:
					throw new Exception('Invalid page request');
			}
		} else {
			$this->logger->error ( 'A page was requested without a page identifier' );
			throw new Exception('Invalid page request');
		}
	}

	/**
	 * Swaps the order numbers of two rules
	 * 
	 * Called from AJAX GUI with $_POST['ruleid1'] $_POST['ruleid2']
	 * 
	 * @throws Exception
	 * @access private
	 */
	private function swapRules(){
		//TODO Check user privileges so rules on closed interfaces cannot be bumped / demoted
		if(is_numeric($_POST['ruleid1']) && is_numeric($_POST['ruleid2'])){
			$rule1 = false;
			$rule2 = false;
			foreach($this->data->rule as $rule){
				if($rule['order'] == $_POST['ruleid1']){
					$rule['order'] = $_POST['ruleid2'];
					$rule1 = true;
				}
				elseif($rule['order'] == $_POST['ruleid2']){
					$rule['order'] = $_POST['ruleid1'];
					$rule2 = true;
				}
				
				if($rule1 && $rule2){
					echo '<reply action="ok" />';
					$this->config->saveConfig();
					break;
				}
			}
		}
		else{
			throw new Exception('Invalid rule identifiers were submitted, the order could not be changed.');
		}
	}
	
	/**
	 * Checks form input for add / edit firewall rules
	 * 
	 * @access private
	 * @throws Exception
	 */
	private function checkFormInput(){
		if($_SESSION['group'] == 'OP'){
			
		}
		
		if(!is_numeric($_POST['firewall_rules_srcport_from_custom']) || $_POST['firewall_rules_srcport_from_custom'] < 0 || $_POST['firewall_rules_srcport_from_custom'] > 65535){
			ErrorHandler::addError('formerror','firewall_rules_srcport_from_custom');
		}
		if(!empty($_POST['firewall_rules_srcport_to_custom']) && (!is_numeric($_POST['firewall_rules_srcport_from_custom']) || $_POST['firewall_rules_srcport_to_custom'] < 0 || $_POST['firewall_rules_srcport_to_custom'] > 65535)){
			ErrorHandler::addError('formerror','firewall_rules_srcport_to_custom');
		}
		
		if(!is_numeric($_POST['firewall_rules_destport_from_custom']) || $_POST['firewall_rules_destport_from_custom'] < 0 || $_POST['firewall_rules_destport_from_custom'] > 65535){
			ErrorHandler::addError('formerror','firewall_rules_destport_from_custom');
		}
		if(!empty($_POST['firewall_rules_destport_to_custom']) && (!is_numeric($_POST['firewall_rules_destport_from_custom']) || $_POST['firewall_rules_destport_to_custom'] < 0 || $_POST['firewall_rules_destport_to_custom'] > 65535)){
			ErrorHandler::addError('formerror','firewall_rules_destport_to_custom');
		}
		
		$types = array('any','single','network','wan_address','lan_subnet','ext_subnet','lan','ext');
		
		if(!in_array($_POST['firewall_rules_src_type'],$types)){
			ErrorHandler::addError('formerror','firewall_rules_src_type');
		}
		if(!in_array($_POST['firewall_rules_dest_type'],$types)){
			ErrorHandler::addError('formerror','firewall_rules_dest_type');
		}
		
		if($_POST['firewall_rules_src_type'] == 'network'){
			if($_POST ['firewall_rules_src_subnet'] < 0 || $_POST ['firewall_rules_src_subnet'] > 32){
				ErrorHandler::addError('formerror','firewall_rules_src_subnet');
			}
		}
		if($_POST['firewall_rules_dest_type'] == 'network'){
			if($_POST ['firewall_rules_dest_subnet'] < 0 || $_POST ['firewall_rules_dest_subnet'] > 32){
				ErrorHandler::addError('formerror','firewall_rules_dest_subnet');
			}
		}
		
		if($_POST['firewall_rules_src_type'] == 'single' || $_POST['firewall_rules_src_type'] == 'network'){
			if(!Functions::is_ipAddr($_POST['firewall_rules_src_address'])){
				ErrorHandler::addError('formerror','firewall_rules_src_address');
			}
		}
		if($_POST['firewall_rules_dest_type'] == 'single' || $_POST['firewall_rules_dest_type'] == 'network'){
			if(!Functions::is_ipAddr($_POST['firewall_rules_dest_address'])){
				ErrorHandler::addError('formerror','firewall_rules_dest_address');
			}
		}
		
		$allowed_proto = array ("tcp", "udp", "tcp/udp", "icmp", "esp", "ah", "gre", "igmp", "any" );
		if(!in_array($_POST['firewall_rules_protocol'],$allowed_proto)){
			ErrorHandler::addError('formerror','firewall_rules_protocol');
		}
		
		$allowed_icmptype = array('','unreach','echo','echorep','squench','redir','timex','paramprob','timest','timestrep','inforeq','inforep','maskreq','maskrep');
		if($_POST['firewall_rules_protocol'] == 'icmp' && !in_array($_POST['firewall_rules_icmp_type'])){
			ErrorHandler::addError('formerror','firewall_rules_icmp_type');
		}
		
		$_POST['firewall_rules_interface'] = ucfirst($_POST['firewall_rules_interface']);
		if($_POST['firewall_rules_interface'] != 'Wan' && $_POST['firewall_rules_interface'] != 'Lan' && $_POST['firewall_rules_interface'] != 'Ext'){
			ErrorHandler::addError('formerror','firewall_rules_interface');
		}
		if($_POST['firewall_rules_action'] != 'pass' && $_POST['firewall_rules_action'] != 'block' && $_POST['firewall_rules_action'] != 'reject'){
			ErrorHandler::addError('formerror','firewall_rules_action');
		}
		
		if(ErrorHandler::errorCount() > 0){
			throw new Exception('There is invalid form input');
		}
	}
	
	/**
	 * 	Adds a user rule
	 *
	 * 	Called through the webGUI AJAX client, adds the user rule present in $_POST
	 * 
	 * 	@access private
	 */
	private function addUserRule(){
		//		check form input
		$this->checkFormInput();
			$source = null;
			/*
			 * Parse Source array
			 */
			switch($_POST['firewall_rules_src_type']){
				case 'network':
					$source['subnet'] = $_POST['firewall_rules_src_subnet'];
					$source['address'] = $_POST['firewal_rules_src_address'];
					$source['type'] = 'network';
					break;
				case 'single':
					$source['address'] = $_POST['firewal_rules_src_address'];
					$source['type'] = 'address';
					break;
				case 'lan':
				case 'ext':
				case 'wan':
					$source['type'] = ucfirst($_POST['firewall_rules_src_type']);
					break;
				case 'wan_address':
				case 'lan_subnet':
					$source['type'] = $_POST['firewall_rules_src_type'];
					break;
			}
			
			if(!empty($_POST['firewall_rules_srcport_to_custom'])){
				$source['port'] = $_POST['firewall_rules_srcport_from_custom'].':'.$_POST['firewall_rules_srcport_to_custom'];
			}
			else{
				$source['port'] = $_POST['firewall_rules_srcport_from_custom'];
			}
			
			if($_POST['firewall_rules_src_not'] == 'yes'){
				$source['invert'] = 'true';
			}
			else{
				$source['invert'] = 'false';
			}
			
			/*
			 * Parse destination array
			 */
			$destination = null;
			switch($_POST['firewall_rules_dest_type']){
				case 'network':
					$destination['subnet'] = $_POST['firewall_rules_dest_subnet'];
					$destination['address'] = $_POST['firewal_rules_dest_address'];
					$destination['type'] = 'network';
					break;
				case 'single':
					$destination['address'] = $_POST['firewal_rules_dest_address'];
					$destination['type'] = 'address';
					break;
				case 'lan':
				case 'ext':
				case 'wan':
					$destination['type'] = ucfirst($_POST['firewall_rules_dest_type']);
					break;
				case 'wan_address':
				case 'lan_subnet':
					$destination['type'] = $_POST['firewall_rules_dest_type'];
					break;
			}
			
			if(!empty($_POST['firewall_rules_destport_to_custom'])){
				$destination['port'] = $_POST['firewall_rules_destport_from_custom'].':'.$_POST['firewall_rules_destport_to_custom'];
			}
			else{
				$destination['port'] = $_POST['firewall_rules_destport_from_custom'];
			}
			
			if(isset($_POST['firewall_rules_icmp_type'])){
				$icmptype = $_POST['firewall_rules_icmp_type'];
			}
			
			if($_POST['firewall_rules_dest_not'] == 'yes'){
				$destination['invert'] = 'true';
			}
			else{
				$destination['invert'] = 'false';
			}
			
			if($_POST['firewall_rules_log'] == 'true'){
				$log = 'enabled';
			}
			else{
				$log = 'disabled';
			}
			
			if($_POST['firewall_rules_fragments'] == 'true'){
				$fragments = 'enabled';
			}
			else{
				$fragments = 'disabled';
			}
			
			Logger::getRootLogger()->debug('New firewall rule, pre-defined order="'.$_POST['firewall_rules_id'].'"');
			if(is_numeric($_POST['firewall_rules_id']) && $_POST['firewall_rules_id'] >= 0){
				Logger::getRootLogger()->debug('Substitute firewall rule ID with predetermined');
				$order = $_POST['firewall_rules_id'];
			}

			//		Call addRule to parse the rule XML, and off we go
			$return = $this->addRule('true',$_POST['firewall_rules_action'],'in',$log,$_POST['firewall_rules_interface'],$_POST['firewall_rules_protocol'],$icmptype,$source,$destination,$fragments, $_POST['firewall_rules_descr'],'User',$order);
			if($return !== false){
				$this->config->saveConfig();
				echo '<reply action="ok"><firewall>';
				echo $return->asXML();
				echo '</firewall></reply>';
			}
	}

	/**
	 * 	Edit a user rule
	 * 
	 * 	Called through WebGUI AJAX client, edits rule with the ID specified in $_POST
	 * 
	 * 	@access private
	 * 	@throws Exception
	 */
	private function editRule() {
		if (isset ( $_POST['firewall_rules_id'] ) && is_numeric ( $_POST['firewall_rules_id'] )) {
			$i = 0;
			foreach($this->data->rule as $rule){
				if($this->checkRights($rule)){
					if((string)$rule['order'] == $_POST['firewall_rules_id']){
						$this->config->deleteElement($rule);
					}
				}
				else{
					throw new Exception('You do not have sufficient rights to edit this rule');
				}
			}
			
			$_POST['order'] == $_POST['firewall_rules_id'];
			$this->addUserRule();
			$this->config->saveConfig();
		} else {
			throw new Exception('editRule() was called without a rule identifier');
		}
	}


	/**
	 * 	Return XML rules for a specific interface
	 *
	 * 	Echoes XML for rules that belong to a specific interface
	 * 
	 * 	@access private
	 */
	private function echoRules() {
		//	We haven't found anything yet
		$foundSomething = false;
		
		$buffer = '<reply action="ok"><firewall>';
		foreach ( $this->data->rule as $rule ) {
			if ($this->checkRights($rule)) {
				$buffer .= $rule->asXML ();
				//	We found a rule, set the flag
				$foundSomething = true;
			}
		}
		
		if ($foundSomething == false) {
			$buffer .= '<message type="notice">No firewall rules exist for this interface</message>';
		}
		$buffer .= '</firewall></reply>';
		echo $buffer;
	}
	
	/**
	 * 	Check if the user has sufficient rights to edit this rule
	 * 
	 * 	@param SimpleXMLElement $rule	xml of the rule to check rights on
	 * 	@return Bool	returns true if the user is allowed to change this rule, false if not
	 */
	private function checkRights($rule){
		if($_SESSION['group'] == 'ROOT' || ($_SESSION['group'] == 'OP' && $rule->interface == 'Ext') || ($_SESSION['group'] == 'USR' && $rule->addedby == 'user')){
			return true;			
		}
		else{
			return false;
		}
	}

	/**
	 * 	Toggle specified rule to enabled / disabled state
	 * 
	 * @access private
	 * @return bool
	 * @throws Exception
	 */
	private function toggleRule(){
		if(isset($_POST['ruleid'])){
			foreach($this->data->rule as $rule){
				if((string)$rule['order'] == $_POST['ruleid']){
					if($this->checkRights($rule)){
						if((string)$rule['enable'] == 'true'){
							$rule['enable'] = 'false';
							echo '<reply action="ok"></reply>';
						}
						elseif((string)$rule['enable'] == 'false'){
							$rule['enable'] = 'true';
							echo '<reply action="ok"></reply>';
						}
						else{
							throw new Exception('Rule state is not enabled or disabled, XML error');
						}
						return true;
					}
					else{
						throw new Exception('You do not have sufficient rights to edit this rule');
					}
				}
			}
			throw new Exception('The specified rule could not be found');
		}
		else{
			throw new Exception('removeRule() called without a rule identifier');
		}
	}
	
	/**
	 * 	Remove specified rule from the firewall
	 *
	 * 	Remove rule from the XML
	 * 
	 * 	@throws Exception
	 * 	@access private
	 */
	private function removePostRule() {
		$found = false;
		if (isset ( $_POST['ruleid'] ) && is_numeric ( $_POST['ruleid'] )) {
			foreach ( $this->data->rule as $rule ) {
				if ($this->checkRights($rule)) {
					//	Remove rule
					$this->config->deleteElement($rule);
					echo '<reply action="ok"></reply>';
					$found = true;
				}
				else{
					throw new Exception('You have insufficient rights to remove this rule');
				}
			}
			
			if(!$found){
				throw new Exception('The specified rule could not be found');
			}
			else{
				$this->config->saveConfig();
			}	
		} else {
			throw new Exception('removeRule() called without a rule identifier');
		}
	}
	
	/**
	 * Remove a rule from the rule stack
	 * 
	 * Called internally for plugins that require a specific rule to be active but
	 * do not want or need to manage their own firewall rules
	 *
	 * @param string $ruleID
	 * @access public
	 */
	public function removeRule($ruleID,$caller) {
		$found = false;
		if (isset ( $ruleID ) && is_numeric ( $ruleID )) {
			foreach ( $this->data->rule as $rule ) {
				if (( int ) $rule->order == $ruleID) {
					if($rule->addedBy == get_class($caller) || get_class($caller) == 'Firewall'){
						//	Remove rule
						$this->config->deleteElement($rule);
						$found = true;
					}
					else{
						return 0;
					}
				}
				elseif((int) $rule->order > $ruleID){
					$rule->order--;
				}
			}
			
			if(!$found){
				return 0;
			}
			else{
				return 1;
			}	
		} else {
			return 0;
		}
	}

	/**
	 *	Start Firewall service
	 *
	 *	@access public
	 */
	public function start() {
		$this->logger->info ( 'Enabling filter' );
		Functions::shellCommand ( '/sbin/pfctl -e' );
		Functions::shellCommand ('sysctl net.inet.ip.forwarding=1');
	}

	/**
	 *  Stop firewall service
	 *  
	 *	@access public
	 */
	public function stop() {
		$this->logger->info ( 'Disabling filter' );
		Functions::shellCommand ( '/sbin/pfctl -d' );
		Functions::shellCommand ('sysctl net.inet.ip.forwarding=0');
	}

	/**
	 *	Create the firewall rules file
	 *
	 *	@access public
	 */
	public function configure() {
		//	 		OPTIONS
		$buffer = '';
		if (( string ) $this->data->maximumstates != 0) {
			$buffer .= "# maximum number of states \n";
			$buffer .= 'set limit states ' . $this->data->maximumstates . "\n\n";
		}

		$buffer .= "# Optimization \n";
		if (( string ) $this->data->optimization != "") {
			$buffer .= 'set optimization ' . $this->data->optimization . " \n\n";
		} else {
			$buffer .= "set optimization normal \n\n";
		}


		$this->logger->info ( 'Getting rules from other modules' );
		
		/*	
		 * 	Get rules from modules that generate and maintain their own firewall ruleset
		 * 	These rules are temporarily buffered separately because pf requires them to be inserted
		 * 	in a specific order. (options, altq, nat, filter)
		 * 
		 * 	this is not the most elegant solution, but it should suffice	
		 */
		$filter = null; 
		$nat = null;
		$altq = null;
		
		foreach ( $this->data->module as $module ) {
			if (( string ) $module['call'] == 'enabled') {
				if(!empty($module['category'])){
					$this->logger->info ( 'Getting rules for: ' . ( string ) $module['name'] );
					$plugin = $this->framework->getPlugin ( ( string ) $module['name'] );
					if($plugin != null){
						$extrules = $plugin->getFirewallRules ();
						foreach($extrules as $extr){
							${$extr['category']} .= "# rules for module: " . ( string ) $module['name'] . " \n";
							${$extr['category']} .= $extr['rules'];
							${$extr['category']} .= "\n";
						}
					}
				}
				else{
					$this->logger->error(' Invalid category setting for external module '.$module['name']);
				}
			}
		}
		
		//			ALTQ rules (if defined)
		$buffer .= $altq."\n";
		
		//			NAT rules (if defined)
		$buffer .= $nat."\n";
		
		//		Antispoof rules
		$buffer .= "# Anti-spoof rules \n";
		$interfaces = $this->config->getElement ( 'interfaces' );
		foreach ( $interfaces as $interface ) {
			$buffer .= 'antispoof for ' . ( string ) $interface->if . " \n";
		}
		$buffer .= "\n";
		
		$buffer .= $filter."\n";
		
		//		Parse rules in the XML
		$this->logger->info ( 'Generating rules from XML' );
		$buffer .= $this->generateRules ();
		$buffer .= "\n";
		

		//		Rule parsing complete, write to file


		if (file_exists ( '/tmp/firewall.rules' )) {
			//		Keep backup of the old ruleset
			Functions::shellCommand ( 'cp /tmp/firewall.rules /tmp/firewall.old' );
		}

		$fp = fopen ( '/tmp/firewall.rules', 'w' );
		fwrite ( $fp, $buffer );
		fclose ( $fp );

		//	Load the rules
		$this->logger->info ( 'Loading rules into the filter' );
		$status = Functions::shellCommand ( "/sbin/pfctl -o basic -f /tmp/firewall.rules" );
		if ($status != 0) {
			//	An error occurred
			$error_line = split ( "\:", $status );
			$line_number = $error_line [1];
			
			$rules_split = split ( "\n", $buffer );
			if (is_array ( $rules_split )) {
				$line_error = 'An error occurred processin the firewall rule on line [' . $line_number . ']: ' . $rules_split [$line_number - 1];
				$this->logger->error ( $line_error );
					
			} else {
				$this->logger->fatal ( 'the firewall rules failed to load and we were unable to locate the error, [' . $status . ']' );
			}
		}
	}

	/**
	 * 	Generate a text file with all the firewall rules
	 *
	 * 	Firewall rules present in the Firewall rules XML will be parsed by this function
	 *
	 * 	@return String $rules_buffer
	 * 	@access private
	 */
	private function generateRules() {
		/*
		 *      Loop over all rules and create a text rule to enter
		 *      into the firewall.
		 */

		$rules_buffer = '';

		foreach ( $this->data->rule as $rule ) {
			if (( string ) $rule['enable'] == "true") {
				$source = '';
				$log = '';
				
				//      Rule example
				//      <action> <direction> <log> quick on <interface> proto <protocol> from <source> <sourceport> to <destination> <destinationport> keep state label "<added-by> : <description>"

				//		See if logging is enabled
				if (( string ) $rule->log == 'enabled') {
					$log = 'log';
				}

				//		Check protocol
				if (( string ) $rule->protocol == 'tcp/udp') {
					$protocol = '{ tcp, udp }';
				} elseif (( string ) $rule->protocol == 'tcp' || ( string ) $rule->protocol == 'udp') {
					$protocol = ( string ) $rule->protocol;
				} elseif (( string ) $rule->protocol == 'icmp') {
					$protocol = 'icmp-type '.(string) $rule->icmptype;
				}

				//		Parse source
				if (( string ) $rule->source['invert'] == "true" ) {
					$source .= 'not ';
				}
				
				if (( string ) $rule->source->type == 'any') {
					//		Source is any
					$source .= 'any';
				} elseif (( string ) $rule->source->type == 'address') {
					//		Source is an IP address
					$source .= ( string ) $rule->source->address;
				}
				elseif((string)$rule->source->type == 'network'){
					//		Source is a subnet / network
					$source .= ( string ) $rule->source->address.'/'.$rule->source->subnet;
				} elseif((string) $rule->source->type == 'lan_subnet'){
					//		Destination is the lan subnet, so fetch it
					$lanconfig = $this->config->getElement('interfaces');
					foreach($lanconfig as $interface){
						if((string)$interface->type == 'lan'){
							$subnet = Functions::prefix2mask((string)$interface->subnet);
							$ip = (string)$interface->ipaddr;
							
							//	Calculate network
							$network = Functions::calculateNetwork($ip,$subnet);
							$source .= $network.'/'.$subnet;
						}
					}
				}
				elseif((string) $rule->source->type == 'wan_address'){
					//		Destination is WAN address, fetch it
					$wan = $this->framework->getPlugin('Wan');
					$ipaddr = $wan->getIpAddress();
					$source .= $ipaddr;
				}
				elseif (( string ) $rule->source->type == 'Lan' || ( string ) $rule->source->type == 'Wan') {
					//		Source is an interface
					$module = $this->framework->getPlugin ( ( string ) $rule->source->type );
					$interface = $module->getRealInterfaceName ();
						
					$source .= $interface;

				} elseif (stristr ( 'Ext', ( string ) $rule->source->type )) {
					/*		Source is the EXT interface, this one's a bit special since there could be multiple ones
					 *		that are all handled by the same module
					 */
					$module = $this->framework->getPlugin ( 'Ext' );
					$interface = $module->getRealInterfaceName ( substr($rule->source->type,-1));
						
					$source .= $interface;
				}

				if (( string ) $rule->source->port != '') {
					//		Source includes a specific port
					if(is_numeric($rule->source->port)){
						$source .= ' port = ' . ( string ) $rule->source->port;
					}
					else{
						$source .= ' port '.(string)$rule->source->port;
					}
				}

				$destination = '';
				//		Parse destination
				if (( string ) $rule->destination['invert'] == "true") {
					$destination = 'not ';
				}
				if (( string ) $rule->destination->type == 'any') {
					//		Destination is any
					$destination .= 'any';
				} elseif (( string ) $rule->destination->type == 'address') {
					//		Destination is an IP address
					$destination .= ( string ) $rule->destination->address;
				}
				elseif((string)$rule->destination->type == 'network'){
					//		Destination is a subnet / network
					$destination .= ( string ) $rule->destination->address.'/'.$rule->destination->subnet;
				}
				elseif((string) $rule->destination->type == 'lan_subnet'){
					//		Destination is the lan subnet, so fetch it
					$lanconfig = $this->config->getElement('interfaces');
					foreach($lanconfig as $interface){
						if((string)$interface->type == 'lan'){
							$subnet = Functions::mask2prefix((string)$interface->subnet);
							$ip = (string)$interface->ipaddr;
							
							//	Calculate network
							$network = Functions::calculateNetwork($ip,$subnet);
							$destination .= $network.'/'.$subnet;
						}
					}
				}
				elseif((string) $rule->destination->type == 'wan_address'){
					//		Destination is WAN address, fetch it
					$wan = $this->framework->getPlugin('Wan');
					$ipaddr = $wan->getInterfaceIP();
					$destination .= $ipaddr;
				}
				elseif (( string ) $rule->destination->type == 'Lan' || ( string ) $rule->destination->type == 'Wan') {
					//		Destination is an interface
					$module = $this->framework->getPlugin ((string) $rule->destination->type );
					if(is_object($module)){
						$tmp_interface = $module->getRealInterfaceName ();
					}
					Logger::getRootLogger('Could not load the plugin '.((string) $rule->destination->type));
					$destination = $tmp_interface;

				} elseif (stristr ( 'Ext', ( string ) $rule->destination->type )) {
					/*		Source is the EXT interface, this one's a bit special since there could be multiple
					 *		that are all handled by the same module
					 */
					$module = $this->framework->getPlugin ( 'Ext' );
					
					if(is_object($module)){
						$tmp_interface = $module->getRealInterfaceName ( substr($rule->destination->type,-1) );
					}
					
					Logger::getRootLogger('Could not load the plugin '. (string) $rule->destination->type);
					$destination .= $tmp_interface;
				}
				
				if (( string ) $rule->destination->port != '') {
					//		Destination includes a specific port
					if(is_numeric($rule->destination->port)){
						$destination .= ' port = ' . ( string ) $rule->destination->port;
					}
					else{
						$destination .= ' port '.(string)$rule->destination->port;
					}
				}

				//		Parse interface
				if (stristr ( 'ext', $rule->interface )) {
					$module = $this->framework->getPlugin ( 'Ext' );
					if($module != null){
						$interface = $module->getRealInterfaceName ( substr($rule->interface,-1) );
					}
					
				} else {
					$module = $this->framework->getPlugin ((string) $rule->interface );
					if($module != null){
						$interface = $module->getRealInterfaceName ();
					}
					else{
						Logger::getRootLogger()->error('Could not get the '.$rule->source.' plugin');
					}
				}

				//		Store generated rule in the rule buffer
				$rules_buffer [( string ) $rule['order']] = ( string ) $rule->action . " " . ( string ) $rule->direction . " " . $log . " quick on " . $interface . " proto " . $protocol . " from " . $source . " to " . $destination . " keep state label \"" . ( string ) $rule['addedBy'] . " : " . ( string ) $rule->description . "\"";
			}
		}
		
		$output = '';
		if(is_array($rules_buffer)){
			foreach($rules_buffer as $rule){
				$output .= $rule."\n";
			}
		}
		else{
			Logger::getRootLogger()->debug('rules_buffer not an array: '.$rules_buffer);
			$output = $rules_buffer;
		}
		
		return $output;
	}

	/**
	 *  Add rule to the firewall XML configuration
	 *  does not add the rule to the active firewall state automatically!
	 *
	 *  @access Public
	 *  @return boolean $success
	 *
	 *  @param String 			$enabled			whether the rule is enabled or not
	 *	@param String 			$action				action to take (pass, block, reject)
	 *	@param String 			$direction 			direction of traffic (in / out)
	 *	@param String 			$log				whether to log traffic or not (enabled / disabled)
	 *	@param String 			$interface			what interface the rule applies to (lan / wan / ext(x))
	 *	@param String 			$protocol			what protocol the rule applies to
	 *	@param String|null		$icmptype			What ICMP type the rule applies to (only used when proto = icmp)
	 *  @param Array  			$source				Source of the traffic
	 *  @param Array  			$destination		Destination of the traffic
	 *  @param String 			$fragments			Whether to allow fragments
	 *  @param String 			$description		Decription of the rule
	 *  @param Object|String 	$addedBy			Reference to the object calling this function ($this)
	 */
	public function addRule($enabled, $action, $direction, $log, $interface, $protocol,$icmptype, $source, $destination, $fragments, $description, $addedBy,$order, $identifier = null) {
		$rule = $this->data->addChild ( 'rule' );
		$success = true;
		
		if(!empty($identifier)){
			$rule->addAttribute('id',$identifier);
		}
		
		if ($enabled == 'true' || $enabled == 'false') {
			$rule->addAttribute ( 'enable', $enabled );
		} else {
			$success = false;
		}

		if ($action == 'pass' || $action == 'reject' || $action == 'block') {
			$rule->addChild ( 'action', $action );
		} else {
			$success = false;
		}

		if ($direction == 'in' || $direction == 'out') {
			$rule->addChild ( 'direction', $direction );
		} else {
			$success = false;
		}

		if ($log == 'enabled' || $log == 'disabled') {
			$rule->addChild ( 'log', $log );
		} else {
			$success = false;
		}

		$rule->addChild ( 'interface', $interface );
		$rule->addChild ( 'protocol', $protocol );
		
		if($protocol == 'icmp'){
			$rule->addChild ( 'icmptype', $source ['icmptype'] );
		}

		$rule->addChild ( 'description', $description );

		if (is_object ( $addedBy )) {
			$rule->addAttribute ( 'addedBy', get_class ( $addedBy ) );
		} elseif(is_string($addedBy) && !empty($addedBy)) {
			$rule->addAttribute ( 'addedBy', $addedBy );
		}

		$rule->addChild ( 'fragments', $fragments );

		//	Parse source
		if (is_array ( $source )) {
			$sourceNode = $rule->addChild ( 'source' );
			$sourceNode->addChild ( 'type', $source ['type'] );
			if (isset($source['invert']) && $source['invert'] == 'true') {
				$sourceNode->addAttribute ( 'invert', $source ['invert'] );
			}
				
			if($source ['type'] == 'address') {
				if(isset($source['address'])){
					$sourceNode->addChild ( 'address', $source ['address'] );
				}
				else{
					$success = false;
				}
			}
			
			if(isset($source['subnet'])){
				$sourceNode->addChild('subnet',$source['subnet']);
			}
				
			if (isset ( $source ['port'] )) {
				$sourceNode->addChild ( 'port', $source ['port'] );
			}
				
		} else {
			$success = false;
		}

		//	parse Destination
		if (is_array ( $destination )) {
			$destinationNode = $rule->addChild ( 'destination' );
				
			$destinationNode->addChild ( 'type', $destination ['type'] );
			if (isset($destination ['invert']) && $destination ['invert'] == 'true') {
				$destinationNode->addAttribute ( 'invert', $destination ['invert'] );
			}
				
			if ($destination ['type'] == 'address') {
				$destinationNode->addChild ( 'address', $destination ['address'] );
			} 
			
			if (isset ( $destination ['port'] )) {
				$destinationNode->addChild ( 'port', $destination ['port'] );
			}
			
			if(isset($destination['subnet'])){
				$destinationNode->addChild('subnet',$destination['subnet']);
			}
		} else {
			$success = false;
		}

		if (! $success) {
			//	rule generatione failed from error, delete the XML node we just made
			$this->config->deleteElement($rule);
			return false;
		} else {
			if(is_numeric($order)){
				//		Insert rule at specified position and reOrder the other rules
				Logger::getRootLogger()->debug('Add new firewall rule: success, predefined order: "'.$order.'"');
				$rule->addAttribute('order',$order);
				//$this->reOrderRules(count($this->data->rules) + 1,$order,1);
			}
			else{
				//		Insert rule at the lowest position
				Logger::getRootLogger()->debug('Add new firewall rule: success, generated order: '.(count($this->data->rule) + 1));
				$rule->addAttribute('order', (count($this->data->rule)+ 1) );
			}
			return $rule;
		}
	}

	/**
	 * Recalculates the order of the XML firewall rules
	 * 
	 * Generally triggered by AddRule or EditRuleOrder where one (or more) rule(s) are being
	 * added or moved within the firewall rule array.
	 * 
	 * @param $originalPoint	Original position of the rules to move
	 * @param $insertPoint		Point to insert the rules at
	 * @param $offset			Number of rules we're moving
	 * @access public
	 */
	private function reOrderRules($originalPoint,$insertPoint, $offset){
		if($originalPoint > $insertPoint){
			$move = 0;
		}
		else{
			$move = 1;
		}
		
		if($move == 1){
			$insertPoint = $insertPoint - ($offset - 1);
		}
		
		foreach($this->data->rules as $rule){
		if($rule['order'] >= $originalPoint && $rule['order'] <= ($originalPoint + ($offset - 1))){
				//	One of the rules we're moving
				$this->logger->debug('RULE::MOVE - '.$rule['order'].' - '.($insertPoint + ($rule['order'] - $originalPoint)).'');
				$rule['order'] = $insertPoint + ($rule['order'] - $originalPoint);
			}
			else{
				//	Not the rules we want to move, but check if we need to change their order # to accomodate the shift.
				if($move == 0){
					echo '# '.$originalPoint.' > '.$rule['order'].' < '.($insertPoint).' <br />';
					if($rule['order'] <= $originalPoint && $rule['order'] >= $insertPoint){
						$this->logger->debug(' RULE::REORDERMIN - '.$rule['order'].' - '.($rule['order'] + $offset).'');
						$rule['order'] += $offset;
					}
				}
				else{
					//		If we're shifting up, we need to reduce the order # of rules with a higher number to prevent a gap from falling
					$this->logger->debug('# '.$originalPoint.' < '.$rule['order'].' > '.($insertPoint).'');
					if($rule['order'] > $originalPoint && $rule['order'] <= ($insertPoint + $offset - 1)){
						$this->logger->debug(' RULE::REORDERMIN - '.$rule['order'].' - '.($rule['order'] - $offset).'');
						$rule['order'] -= $offset;
					}
				}
			}
		}
	}
	
	/**
	 * Return a set of rules with $identifier or rules added by $module
	 * 
	 * @param Plugin $module
	 * @param String|null $identifier
	 * @return Array
	 * @access public
	 */
	public function returnRule($module,$identifier = null){
		$module = get_class($module);
		
		foreach($this->rule as $rule){
			if((string)$rule->addedBy == $module){
				if($identifier == null){
					$rules[] = &$rule;
				}
				elseif($identifier == (string)$rule->id){
					$rules[] = &$rule;
				}
			}
		}
		
		return $rules;
	}
	
	/**
	 * 	Add module call during rule generation
	 *
	 *	Modules MUST include the "generateFirewallRules" interface or this function will fail
	 *
	 *	@access public
	 *	@param 	String 	$module 	Module name
	 *	@return Boolean $success
	 */
	public function addModuleCall($module) {
		$child = $this->data->addChild ( 'module' );
		$child->addAttribute ( 'name', $module );
		$child->addAttribute ( 'call', 'enabled' );
		return true;
	}

	/**
	 * 	Set module call to enabled / disabled
	 *
	 * 	Call when modules are disabled temporarily. Alternatively the entire callback can be removed.
	 *
	 *	@access public
	 *	@param 	String 	$module 	Module name
	 *	@param 	String	$setting 	Whether to enable / disable the call. (enabled / disabled)
	 *	@return Boolean $success
	 */
	public function setModuleCall($module, $setting) {
		foreach ( $this->data->module as $modules ) {
			if (( string ) $modules->name == $module) {
				$modules->call = $setting;
				return true;
			}
		}
		return false;
	}

	/**
	 * 	Remove module call from the firewall configuration
	 *
	 * 	@access public
	 * 	@param 	String 	$module 	Module name
	 * 	@return Boolean $success
	 */
	public function removeModuleCall($module) {
		$i = 0;
		while ( $i < count ( $this->data->module ) ) {
			if (( string ) $this->data->module [$i]->name == $module) {
				unset ( $this->data->module [$i] );
				return true;
			}
			$i ++;
		}
		return false;
	}
}

?>