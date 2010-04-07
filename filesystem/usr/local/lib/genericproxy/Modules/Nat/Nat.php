<?php
require_once(PluginFramework::FRAMEWORK_PATH.'/libs/GeneratesRules.php');
/**
 * Nat plugin
 * 
 * Generates / manages NAT options and rules
 * 
 * @version 0.0
 * @uses	Firewall
 */
class Nat implements Plugin, GeneratesRules {
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
	 *
	 * @param PluginFramework $framework Framework object, containing all information and plugins.
	 * @param Config $config Object with System Configuration
	 * @param int $runtype Running mode of the script. Can be PluginFramework::RUNTYPE_STARTUP or PluginFramework::RUNTYPE_BROWSER
	 */
	public function __construct($framework, $config, $options, $runtype) {
		$this->config = $config;
		$this->runtype = $runtype;
		$this->framework = $framework;
		
		//get NAT config
		$this->data = $this->config->getElement ( 'nat' );
	}
	
	/**
	 * 	configure has no use for NAT since it runs on daemons of its own	
	 * 
	 * 	@access public
	 */
	public function configure() {}
	
	/**
	 * 	Get dependencies
	 * 
	 * 	@access public
	 */
	public function getDependency() {
		return Array('Firewall');
	}
	
	/**
	 * 	Passthrough function to delegate AJAX page requests to proper functions
	 * 
	 * 	@access public
	 * 	@throws Exception
	 */
	public function getPage() {
		switch($_POST['page']){
			case 'getconfig':
				echo '<reply action="ok">'.$this->data->asXML ().'</reply>';
				break;
			case 'save_outbound':
				$this->saveSettings();
				break;
			case 'add_11nat_rule':
				$this->newOneToOneRule();
				break;
			case 'delete_11nat_rule':
				$this->removeRule('onetoone',$_POST['ruleid']);
				break;
			case 'edit_11nat_rule':
				$this->editOneToOneRule();
				break;
			case 'add_inbound_rule':
				$this->newInboundRule();
				break;
			case 'delete_inbound_rule':
				$this->removeRule('inbound',$_POST['ruleid']);
				break;
			case 'edit_inbound_rule':
				$this->editInboundRule();
				break;
			case 'add_outbound_rule':
				$this->newOutboundRule();
				break;
			case 'delete_outbound_rule':
				$this->removeRule('outbound',$_POST['ruleid']);
				break;
			case 'edit_outbound_rule':
				$this->editOutboundRule();
				break;
			default:
				Logger::getRootLogger()->error ( 'A page was requested without a valid page identifier' );
				throw new Exception('Invalid page request');
				break;
		}
	}
	

	
	/**
	 * Returns true if this is a service
	 * 
	 * @access public
	 * @return Bool
	 */
	public function isService() {
		return false;
	}
	
	/**
	 * runAtBoot serves no purpose in NAT since it runs no daemons
	 * 
	 * @access public
	 */
	public function runAtBoot() {}
	
	/**
	 * Dummy functions the interface enforces, but NAT doesn't need
	 * since it starts no services of its own
	 */
	public function start() {}
	public function getStatus() {}
	public function stop() {}

	/**
	 * Saves NAT settings
	 * 
	 * @access private
	 */
	private function saveSettings(){
		if($_POST['firewall_nat_outbound_adv_enable'] == 'true'){
			$this->data->advancedoutbound['enable'] = 'true';
		}
		else{
			$this->data->advancedoutbound['enable'] = 'false';
		}
		
		echo '<reply action="ok" />';
	}
	
	/**
	 * Input validation for Inbound NAT rule forms
	 * 
	 * @throws Exception	throws exception on invalid form input
	 * @return void
	 */
	private function checkInboundForm(){
		if(ucfirst($_POST['firewall_nat_inbound_interface']) != 'Lan' && ucfirst($_POST['firewall_nat_inbound_interface']) != 'Wan'){
			ErrorHandler::addError('formerror','firewall_nat_inbound_interface');
		}
		
		if($_POST['firewall_nat_inbound_ext_ip'] != 'any' && !Functions::is_ipAddr($_POST['firewall_nat_inbound_ext_ip'])){
			ErrorHandler::addError('formerror','firewall_nat_inbound_ext_ip');
		}
		
		if($_POST['firewall_nat_inbound_protocol'] != 'tcp' && $_POST['firewall_nat_inbound_protocol'] != 'udp' && $_POST['firewall_nat_inbound_protocol'] != 'tcp/udp'){
			ErrorHandler::addError('formerror','firewall_nat_inbound_protocol');
		}

		if(!empty($_POST['firewall_nat_inbound_portrange_to_custom']) && (!is_numeric($_POST['firewall_nat_inbound_portrange_to_custom']) || $_POST['firewall_nat_inbound_portrange_to_custom'] < 0 || $_POST['firewall_nat_inbound_portrange_to_custom'] > 65535)){
			ErrorHandler::addError('formerror','firewall_nat_inbound_portrange_to_custom');
		}
		
		if(!Functions::is_ipAddr($_POST['firewall_nat_inbound_nat_ip'])){
			ErrorHandler::addError('formerror','firewall_nat_inbound_nat_ip');
		}
		
		if(!is_numeric($_POST['firewall_nat_inbound_portrange_from_custom']) || $_POST['firewall_nat_inbound_portrange_from_custom'] < 0 || $_POST['firewall_nat_inbound_portrange_from_custom'] > 65535){
			ErrorHandler::addError('formerror','firewall_nat_inbound_portrange_from_custom');
		}
		
		if(!is_numeric($_POST['firewall_nat_inbound_localport_custom']) || $_POST['firewall_nat_inbound_localport_custom'] < 0 || $_POST['firewall_nat_inbound_localport_custom'] > 65535){
			ErrorHandler::addError('formerror','firewall_nat_inbound_localport_custom');
		}
		
		if(ErrorHandler::errorCount() > 0){
			throw new Exception('There is invalid form input');
		}
	}
	
	/**
	 * Insertion for new Inbound NAT rule
	 * 
	 * Does not automatically add the new rule to the firewall state
	 * 
	 * @access private
	 */
	private function newInboundRule(){
		$this->checkInboundForm();
			//	Input validated, construct XML element:			
			$rule = $this->data->inbound->addChild('rule');
			$rule->addAttribute('id',time());
			$rule->addChild('interface',ucfirst($_POST['firewall_nat_inbound_interface']));
			$rule->addChild('external-address',$_POST['firewall_nat_inbound_ext_ip']);
			if(!empty($_POST['firewall_nat_inbound_portrange_to_custom'])){
				$rule->addChild('external-port',$_POST['firewall_nat_inbound_portrange_from_custom'].':'.$_POST['firewall_nat_inbound_portrange_to_custom']);
			}
			else{
				$rule->addChild('external-port',$_POST['firewall_nat_inbound_portrange_from_custom']);
			}
			$rule->addChild('protocol',strtolower($_POST['firewall_nat_inbound_protocol']));
			$rule->addChild('target',$_POST['firewall_nat_inbound_nat_ip']);
			$rule->addChild('local-port',$_POST['firewall_nat_inbound_localport_custom']);
			$rule->addChild('description',$_POST['firewall_nat_inbound_descr']);
			
			if(isset($_POST['firewall_nat_inbound_add_firewallrule'])){
				$this->framework->getPlugin('Firewall')->addRule('true','pass','in','disabled',$_POST['firewall_nat_inbound_nat_interface'],$_POST['firewall_nat_inbound_protocol'],null,'any',$_POST['firewall_nat_inbound_nat_ip'],'enabled','Allow NAT traffic on this port',$this,null);
			}
			
			//		Save config XML so we don't lose changes upon reboot
			$this->config->saveConfig();
			
			echo '<reply action="ok"><nat><inbound>';
			echo $rule->asXML();
			echo '</inbound></nat></reply>';
	}

	/**
	 * Edit Inbound NAT rule
	 * 
	 * Edits data on an inbound NAT rule based on the values in $_POST
	 * 
	 * @throws Exception
	 * @access private
	 */
	private function editInboundRule(){
		if(!empty($_POST['firewall_nat_inbound_id'])){
			$this->checkInboundForm();
				foreach($this->data->inbound->rule as $rule){
					if($rule['id'] == $_POST['firewall_nat_inbound_id']){
						$rule->interface = ucfirst($_POST['firewall_nat_inbound_interface']);
						$rule->external_address = $_POST['firewall_nat_inbound_ext_ip'];
						if(!empty($_POST['firewall_nat_inbound_portrange_to_custom'])){
							$rule->{'external-port'} = $_POST['firewall_nat_inbound_portrange_from_custom'].'-'.$_POST['firewall_nat_inbound_portrange_to_custom'];
						}
						else{
							$rule->{'external-port'} = $_POST['firewall_nat_inbound_portrange_from_custom'];
						}
						
						$rule->protocol = strtolower($_POST['firewall_nat_inbound_protocol']);
						$rule->target = $_POST['firewall_nat_inbound_nat_ip'];
						$rule->{'local-port'} = $_POST['firewall_nat_inbound_localport_custom'];
						$rule->description = $_POST['firewall_nat_inbound_descr'];
						$this->config->saveConfig();
						
						echo '<reply action="ok"><nat><inbound>';
						echo $rule->asXML();
						echo '</inbound></nat></reply>';
						return 1;
					}
				}
				throw new Exception('The specified rule could not be found');

		}
		else{
			throw new Exception('No valid rule identifier was submitted');
		}
	}
	
	/**
	 * Form validation for Outbound NAT rules
	 * 
	 * @throws Exception
	 * @return String $error_buffer
	 */
	private function checkOutboundForm(){
		if($_POST['firewall_nat_outbound_src_type'] == 'interface'){
			$_POST['firewall_nat_outbound_src_interface'] = ucfirst($_POST['firewall_nat_outbound_src_interface']);
			if($_POST['firewall_nat_outbound_src_interface'] != 'Wan' && $_POST['firewall_nat_outbound_src_interface'] != 'Lan' && $_POST['firewall_nat_outbound_src_interface'] != 'Ext'){
				ErrorHandler::addError('formerror','firewall_nat_outbound_src_interface');
			}
		}
		
		if(!is_numeric($_POST['firewall_nat_outbound_srcport_custom']) || $_POST['firewall_nat_outbound_srcport_custom'] < 0 || $_POST['firewall_nat_outbound_srcport_custom'] >= 65535){
			ErrorHandler::addError('formerror','firewall_nat_outbound_srcport_custom');
		}
		
		$types = array('any','address','interface');
		if(!in_array($_POST['firewall_nat_outbound_src_type'],$types)){
			ErrorHandler::addError('formerror','firewall_nat_outbound_src_type');
		}
		
		if($_POST['firewall_nat_outbound_src_type'] == 'address'){
			if(!Functions::is_ipAddr($_POST['firewall_nat_outbound_src_ipaddr'])){
				ErrorHandler::addError('formerror','firewall_nat_outbound_src_ipaddr');
			}
		}
		
		if($_POST['firewall_nat_outbound_dest_type'] == 'interface'){
			if($_POST['firewall_nat_outbound_dest_interface'] != 'wan' && $_POST['firewall_nat_outbound_dest_interface'] != 'lan' && $_POST['firewall_nat_outbound_dest_interface'] != 'ext'){
				ErrorHandler::addError('formerror','firewall_nat_outbound_dest_interface');
			}
		}
		
		if(!is_numeric($_POST['firewall_nat_outbound_destport_custom']) || $_POST['firewall_nat_outbound_destport_custom'] < 0 || $_POST['firewall_nat_outbound_destport_custom'] >= 65535){
			ErrorHandler::addError('formerror','firewall_nat_outbound_destport_custom');
		}
		
		$types = array('any','address','interface');
		if(!in_array($_POST['firewall_nat_outbound_dest_type'],$types)){
			ErrorHandler::addError('formerror','firewall_nat_outbound_dest_type');
		}
		
		if($_POST['firewall_nat_outbound_dest_type'] == 'address'){
			if(!Functions::is_ipAddr($_POST['firewall_nat_outbound_dest_ipaddr'])){
				ErrorHandler::addError('formerror','firewall_nat_outbound_dest_ipaddr');
			}
		}
		
		if($_POST['firewall_nat_outbound_dest_type'] == 'interface'){
			if($_POST['firewall_nat_outbound_interface'] != 'wan' && $_POST['firewall_nat_outbound_interface'] != 'lan' && $_POST['firewall_nat_outbound_interface'] != 'ext'){
				ErrorHandler::addError('formerror','firewall_nat_outbound_interface');
			}
		}
		
		if(!empty($_POST['firewall_nat_outbound_target']) && !Functions::is_ipAddr($_POST['firewall_nat_outbound_target'])){
			ErrorHandler::addError('formerror','firewall_nat_outbound_target');
		}
		
		if(!is_numeric($_POST['firewall_nat_outbound_staticnatport_custom']) || $_POST['firewall_nat_outbound_staticnatport_custom'] < 0 || $_POST['firewall_nat_outbound_staticnatport_custom'] > 65535){
			ErrorHandler::addError('formerror','firewall_nat_outbound_staticnatport_custom');
		}
		
		if(!is_numeric($_POST['firewall_nat_outbound_natport_custom']) || $_POST['firewall_nat_outbound_natport_custom'] < 0 || $_POST['firewall_nat_outbound_natport_custom'] > 65535){
			ErrorHandler::addError('formerror','firewall_nat_outbound_natport_custom');
		}
		
		if(ErrorHandler::errorCount() > 0){
			throw new Exception('There is invalid form input');
		}
	}
	
	/**
	 * Insertion for new Outbound NAT rule
	 * 
	 * @access private
	 */
	private function newOutboundRule($edit = false){
		if(!$edit){
			$this->checkOutboundForm();
		}
				
		if(isset($_POST['firewall_nat_outbound_disable_portmapping'])){
			$nonat = 'true';
		}
		

		$rule = $this->data->advancedoutbound->addChild('rule');
		if(empty($_POST['firewall_nat_outbound_id'])){
			$rule->addAttribute('id',time());
		}
		else{
			$rule->addAttribute('id',$_POST['firewall_nat_outbound_id']);
		}
		$rule->addAttribute('nonat',$nonat);
		$rule->addChild('interface',$_POST['firewall_nat_outbound_interface']);
		$rule->addChild('natport',$_POST['firewall_nat_outbound_natport_custom']);
		$rule->addChild('staticnatport',$_POST['firewall_nat_outbound_staticnatport_custom']);
		
		
		$source = $rule->addChild('source');
		if($_POST['firewall_nat_outbound_src_type'] == 'address'){
			$source->addChild('address',$_POST['firewall_nat_outbound_src_ipaddr']);
			if(Functions::is_ipAddr($_POST['firewall_nat_outbound_src_ipaddr'])){
				$source->addChild('subnet',$_POST['firewall_nat_outbound_src_subnet']);
			}
		}
		elseif($_POST['firewall_nat_outbound_src_type'] == 'interface'){
			$source->addChild('interface',$_POST['firewall_nat_outbound_src_interface']);
		}
		elseif($_POST['firewall_nat_outbound_src_type'] == 'any'){
			$source->addChild('address','any');
		}
		$source->addChild('port',$_POST['firewall_nat_outbound_srcport_custom']);
		
		$dest = $rule->addChild('destination');
		if($_POST['firewall_nat_outbound_dest_type'] == 'address'){
			$dest->addChild('address',$_POST['firewall_nat_outbound_dest_ipaddr']);
			if(Functions::is_ipAddr($_POST['firewall_nat_outbound_dest_ipaddr'])){
				$dest->addChild('subnet',$_POST['firewall_nat_outbound_dest_subnet']);
			}
		}
		elseif($_POST['firewall_nat_outbound_src_type'] == 'interface'){
			$dest->addChild('interface',$_POST['firewall_nat_outbound_dest_subnet']);
		}
		elseif($_POST['firewall_nat_outbound_src_type'] == 'any'){
			$dest->addChild('address','any');
		}
		$dest->addChild('port',$_POST['firewall_nat_outbound_destport_custom']);
		
		$rule->addChild('description',$_POST['firewall_nat_outbound_descr']);
		if(!empty($_POST['firewall_nat_outbound_target'])){
			$rule->addChild('target',$_POST['firewall_nat_outbound_target']);
		}
		
		$this->config->saveConfig();
		
		echo '<reply action="ok"><nat><advancedoutbound>';
		echo $rule->asXML();
		echo '</advancedoutbound></nat></reply>';
	}	
	
	/**
	 * Edit Advanced Outbound NAT rule
	 * 
	 * @throws Exception
	 * @access private
	 */
	private function editOutboundRule(){
		if(!empty($_POST['firewall_nat_outbound_id'])){
			$this->checkOutboundForm();

			foreach($this->data->advancedoutbound-> rule as $rule){
				if($rule['id'] == $_POST['firewall_nat_outbound_id']){
					$this->newOutboundRule(true);
					return 1;
				}
			}
			throw new Exception('The specified rule could not be found');
		}
		else{
			throw new Exception('No valid rule identifier was submitted');
		}
	}
	
	/**
	 * Form validation for 1:1 NAT rules
	 * 
	 * @throws Exception
	 * @access private
	 */
	private function checkOneToOneForm(){	
		if($_POST['firewall_nat_11nat_interface'] != 'lan' && $_POST['firewall_nat_11nat_interface'] != 'wan' && $_POST['firewall_nat_11nat_interface'] != 'ext'){
			ErrorHandler::addError('formerror','firewall_nat_11nat_interface');
		}
		
		if(!Functions::is_ipAddr($_POST['firewall_nat_11nat_ext_address'])){
			ErrorHandler::addError('formerror','firewall_nat_11nat_ext_address');
		}
		
		if(!Functions::is_ipAddr($_POST['firewall_nat_11nat_int_address'])){
			ErrorHandler::addError('formerror','firewall_nat_11nat_int_address');
		}
		
		if(!Functions::is_subnet($_POST['firewall_nat_11nat_ext_subnet'])){
			ErrorHandler::addError('formerror','firewall_nat_11nat_ext_subnet');
		}
		
		if(ErrorHandler::errorCount() > 0){
			throw new Exception('There is invalid form input');
		}
	}
	
	/**
	 * Input validation and insertion for new One to One NAT rule
	 * 
	 * @access private
	 */
	private function newOneToOneRule(){
		$this->checkOneToOneForm();
		
		//		Create rule XML element
		$rule = $this->data->onetoone->addChild('rule');
		$rule->addAttribute('id',time());
		$rule->addChild('interface',$_POST['firewall_nat_11nat_interface']);
		$rule->addChild('external',$_POST['firewall_nat_11nat_ext_address']);
		$rule->addChild('internal',$_POST['firewall_nat_11nat_int_address']);
		$rule->addChild('description',$_POST['firewall_nat_11nat_descr']);
		$rule->addChild('subnet',$_POST['firewall_nat_11nat_ext_subnet']);
		//		Save XML file to disk
		echo '<reply action="ok"><nat><onetoone>';
		echo $rule->asXML();
		echo '</onetoone></nat></reply>';
		$this->config->saveConfig();
	}
	
	/**
	 * Edit one to one NAT rule
	 * 
	 * @throws Exception
	 * @access private
	 */
	private function editOneToOneRule(){
		if(!empty($_POST['firewall_nat_11nat_id'])){
			$this->checkOneToOneForm();
			
			foreach($this->data->onetoone->rule as $rule){
				if($rule['id'] == $_POST['firewall_nat_11nat_id']){
					$rule->interface = $_POST['firewall_nat_11nat_interface'];
					$rule->external = $_POST['firewall_nat_11nat_ext_address'];
					$rule->internal = $_POST['firewall_nat_11nat_int_address'];
					$rule->description = $_POST['firewall_nat_11nat_descr'];
					$rule->subnet = $_POST['firewall_nat_11nat_ext_subnet'];
					
					$this->config->saveConfig();
					echo '<reply action="ok"><nat><onetoone>';
					echo $rule->asXML();
					echo '</onetoone></nat></reply>';
					return 1;
				}					
			}
			throw new Exception('the specified rule could not be found');
		}
		else{
			throw new Exception('No valid rule identifier was submitted');
		}
	}
	/**
	 * Removes the $type rule with the identifier specified in $id
	 * 
	 * @access private
	 * @param String	$type	type of the rule to remove (inbound,outbound,onetoone)
	 * @param String	$id		identifier of the NAT rule to remove
	 * @throws Exception
	 */
	private function removeRule($type,$id){
		if(!empty($id)){
			foreach($this->data->{$type}->rule as $rule){
				if($rule['id'] == $id){
					$this->config->deleteElement($rule);
					echo '<reply action="ok" />';
					return 1;
				}
			}	
			throw new Exception('The specified NAT rule could not be found');
		}
		else{
			throw new Exception('No NAT rule identifier was submitted');
		}
	}
	
	/**
	 * Returns the XML of the rule specified in $_POST['id'] of type $type
	 * 
	 * @access private
	 * @param String $type onetoone|inbound|outbound
	 * @throws Exception
	 */
	private function returnRule($type){
		if(!empty($_POST['id'])){
			foreach($this->data->{$type}->rule as $rule){
				if($rule['id'] == $_POST['id']){
					echo '<reply action="ok">';
					echo $rule->asXML();
					echo '</reply>';
					return 1;
				}
			}
			throw new Exception('The specified NAT rule could not be found');
		}
	}
	
	/**
	 * Returns all NAT rules for the specified type
	 * 
	 * @param String $type onetoone|inbound|outbound
	 * @access private
	 */
	private function returnRules($type){
		$buffer .= '<reply action="ok"><nat>';
		$buffer .= $this->data->{$type}->asXML();
		$buffer .= '</nat></reply>';
		echo $buffer;
	}
	
	/**
	 * Generate new Outbound NAT rule
	 * 
	 * @param String $interface
	 * @param String $source
	 * @param Integer $source_port
	 * @param String $destination
	 * @param Integer $dest_port
	 * @param IPAddress $natip
	 * @param Integer $staticnatport
	 * @param Boolean $nonat
	 */
	private function generateOutboundRule($interface,$source = "any",$source_port = null,$destination = "any",$dest_port = null,$natip = null,$staticnatport = null,$nonat = false){
		$staticport = '';
		$tmp_network = null;
		
		if(!empty($source_port)){
			$tmp_network = " port ".$source_port;
		}
		
		if(!empty($dest_port)){
			$destination .= " port ".$dest_port;
		}

		if(!empty($natip)){
			$tgt = $natip."/32";
		}
		else{
			$wan =  $this->framework->getPlugin('Wan');
			if(!empty($wan)){
				$tgt = '('.$wan->getRealInterfaceName().')';
			}
		}
		
		if(!empty($staticnatport)){
			$staticport = " static-port";
			$tgt .= " port ".$staticnatport;
		}
		
		if($nonat === true){
			$nat = "no nat";
		}
		else{
			$nat = "nat";
			$target = "-> ".$tgt;
		}
		
		Logger::getRootLogger()->debug('NAT interface name: '.(string)$source->if);
		
		$network = ((string)$source->if).':network';
		$network .= $tmp_network;
		return $nat . " on " . $interface . " from " . $network . " to " . $destination . " " . $target . $staticport . "\n";
	}
	
	/**
	 * Returns a string with all the NAT firewall rules to be loaded into the firewall module
	 * 
	 * @access public
	 * @return String
	 */
	public function getFirewallRules() {
		Logger::getRootLogger ()->info ( 'Parsing NAT rules' );
		$rules = '';
		$rules .= "nat-anchor \"natrules/*\"\n";
		
		$rules .= $this->generateOutboundRules();
		$rules .= $this->generateOneOnOneRules();
		$rules .= $this->generateInboundRules();
		
		$return = null;
		$return[0]['category'] = 'nat';
		$return[0]['rules'] = $rules;
		return $return;
	}

	/**
	 * 	Generates Outbound NAT rules
	 * 
	 * 	Generates Outbound rules to pass back at the Firewall module
	 * 
	 * 	@access private
	 * 	@return String
	 */
	private function generateOutboundRules(){
		$rules = "\n# Outbound NAT rules \n";
		if (( string ) $this->data->advancedoutbound['enable'] == 'true') {
			Logger::getRootLogger ()->info ( 'Generating Advanced Outbound NAT rules' );
			//		Advanced outbound enabled, parse their rules
			foreach ( $this->data->advancedoutbound->rule as $rule_cfg ) {
				$interface = $this->framework->getPlugin(ucfirst((string) $rule_cfg->interface ))->getRealInterfaceName();
				
				if(!empty($rule_cfg->target)){
					$tmp_target .= ( string ) $rule_cfg->target."/32";
				}
				else{
					$tmp_target .= "(".$interface.")";
				}
				
				//		Check if we're using a static nat port mapping
				if (! empty ( $rule_cfg->staticnatport )) {
					$tmp_target .= "port " . ( string ) $rule_cfg->staticnatport;
					$staticport = " static-port";
				} else {
					$staticport = "";
				}
				
				//		parse source for NAT
				if (! empty ( $rule_cfg->source->address )) {
					//		Source is an IP range
					$source = ( string ) $rule_cfg->source->address . "/" . ( string ) $rule_cfg->source->subnet;
				} elseif (( string ) $rule_cfg->source == "any") {
					//		Source could be any
					$source = "any";
				} else {
					//		Source is an interface
					$src_interface = $this->framework->getPlugin ( ucfirst ((string) $rule_cfg->source ) )->getRealInterfaceName ();
					$source = $src_interface;
				}
				
				//		Check if source uses a specific port
				if (! empty ( $rule_cfg->source->port )) {
					$source .= " port " . ( string ) $rule_cfg->source->port;
				}
				
				//		Parse destination
				if (! empty ( $rule_cfg->destination->address )) {
					$destination = ( string ) $rule_cfg->destination->address . "/" . ( string ) $rule_cfg->destination->subnet;
				} elseif (( string ) $rule_cfg->destination->source == "any") {
					$destination = "any";
				} else {
					//		Source is an interface get interface name
					$dst_interface = $this->framework->getPlugin ( ucfirst ((string) $rule_cfg->destination ) )->getRealInterfaceName ();
					$destination = $dst_interface;
				}
				
				//		Check if destination uses a specific port
				if (! empty ( $rule_cfg->destination->port )) {
					$destination .= " port " . ( string ) $rule_cfg->destination->port;
				}
				
				if (( string ) $rule_cfg ['nonat'] == 'true') {
					//		No NAT rule
					$nat = "no nat";
					$target = "";
				} else {
					$nat = "nat";
					$target = " -> ".$tmp_target;
				}
				
				$rules .= $nat . " on " . $interface . " from " . $source . " to " . $destination . " " . $target . $staticport . "\n";
			}
		} else {
			//		Advanced outbound disabled, parse automatic ruleset
			Logger::getRootLogger ()->info ( 'Generating Automatic Outbound NAT rules' );
			
			//		Standard outbound rules for each interface
			
			$ifcfg = $this->config->getElement('interfaces');
			Logger::getRootLogger()->debug('Looping over all interfaces:');
			foreach($ifcfg->interface as $interface){
				if((string)$interface['type'] == 'Lan'){
					$lancfg = $interface;
				}
				elseif((string)$interface['type'] == 'Wan'){
					$wancfg = $interface;
				}
			}
			
			if(!empty($lancfg)){
				//		LAN rules
				$rules .= $this->generateOutboundRule((string)$wancfg->if,$lancfg,500,'any',500,null,null,false);
				$rules .= $this->generateOutboundRule((string)$wancfg->if,$lancfg,5060,'any',5060,null,null,false);
				$rules .= $this->generateOutboundRule((string)$wancfg->if,$lancfg,null,'any',null,null,null,false);
			}
			else{
				Logger::getRootLogger()->info('Could not find the lan configuration');
				throw new Exception('Lan interface could not be found');
			}
			
			//		NAT rules for EXT interfaces that have a gateway
			foreach($ifcfg->interface as $interface){
				if(stristr((string)$interface['type'],'Ext') && (string)$interface['enable'] == 'true'){
					Logger::getRootLogger()->info('.. Generating outbound rules for '.(string)$interface['type']);
					if((string)$interface->ipaddr == 'dhcp' || !empty($interface->gateway)){
						//		LAN mapping to EXT interfaces with gateway
						$rules .= $this->generateOutboundRule((string)$interface->if,$lancfg."/".Functions::mask2prefix((string)$lancfg->subnet),500,'any',500,null,null,false);
						$rules .= $this->generateOutboundRule((string)$interface->if,$lancfg."/".Functions::mask2prefix((string)$lancfg->subnet),5060,'any',5060,null,null,false);
						$rules .= $this->generateOutboundRule((string)$interface->if,$lancfg."/".Functions::mask2prefix((string)$lancfg->subnet),null,'any',null,null,null,false);			
					}
					else{
						//		Create EXT mappings to WAN iface
						$extnetwork = Functions::calculateNetwork((string)$interface->ipaddr,(string)$interface->subnet);
						
						$rules .= $this->generateOutboundRule((string)$wancfg->if,$extnetwork."/".(string)$interface->subnet,500,'any',500,null,null,false);
						$rules .= $this->generateOutboundRule((string)$wancfg->if,$extnetwork."/".(string)$interface->subnet,5060,'any',5060,null,null,false);
						$rules .= $this->generateOutboundRule((string)$wancfg->if,$extnetwork."/".(string)$interface->subnet,null,'any',null,null,null,true);
						
						//		Create EXT interface mappings to EXT interfaces with gateways
						/*
						 * 		FIXME: Check if this is absolutely required, seems messy and
						 * 		the second loop is NOT good for our clock cycles
						 * 		although I already merged two loops so there should be an increase in performance already
						 */
						foreach($ifcfg->interface as $iface){
							if(stristr((string)$interface['type'],'Ext')){
								if((string)$iface->ipaddr == 'dhcp' || !empty($iface->gateway)){
									$rules .= $this->generateOutboundRule((string)$iface->if,$extnetwork."/".(string)$iface->subnet,500,'any',500,null,null,false);
									$rules .= $this->generateOutboundRule((string)$iface->if,$extnetwork."/".(string)$iface->subnet,5060,'any',5060,null,null,false);
									$rules .= $this->generateOutboundRule((string)$iface->if,$extnetwork."/".(string)$iface->subnet,null,'any',null,null,null,true);
								}
							}
						}
					}
				}
			}
		}
		return $rules;
	}
	
	/**
	 * Generates Inbound NAT rules
	 * 
	 * @return String
	 * @access private
	 */
	private function generateInboundRules(){
		//		Get a list of all interfaces, we need this later in the loop.
		$ifcfg = $this->config->getElement('interfaces');
		
		$rules = "\n# NAT Inbound redirects \n";
		Logger::getRootLogger()->info('Generating Inbound NAT redirects');
		
		/*		Nat reflection starts from port 19000 upwards
		 * 		This port range is used to redirect packets to / from 127.0.0.1
		 */
		$localhost_port = 19000;
		if((string)$this->data->natreflection == 'enabled'){
			$inetd_fd = fopen('/var/etc/inetd.conf','w');
		}
		
		foreach($this->data->inbound->rule as $rule){
			$extport = explode('-',(string)$rule->{'external-port'});
			if(empty($extport[1])){
				//		Range is a single port, set range end to start port
				$extport[1] = $extport[0];
			}
			
			$localport = explode('-',(string)$rule->{'local-port'});
			if(empty($localport[1])){
				$localport = $localport[0];
			}
			$target = $rule->target;
			
			if(!empty($rule->{'external-address'})){
				if((string)$rule->{'external-address'} == 'any'){
					$extaddr = $rule->{'external-address'};
				}
				else{
					$extaddr = $rule->{'external-address'}."/32";
				}
			}
			else{
				$extaddr = $this->framework->getPlugin(ucfirst((string)$rule->interface))->getIpAddress();
			}
			
			if(empty($rule->interface)){
				$natif = $this->framework->getPlugin('Wan')->getRealInterfaceName();
			}
			else{
				$natif = $this->framework->getPlugin(ucfirst((string)$rule->interface))->getRealInterfaceName();
			}

			$lanif = $this->framework->getPlugin('Lan')->getRealInterfaceName();
			
				/* rule requires a port change? */
				if(empty($extport[1]) || ($extport[0] == $extport[1])){
					if(!empty($natif)){
						switch((string)$rule->protocol){
							case 'tcp/udp':
								if((string)$rule->{'external-port'} != (string)$rule->{'internal-port'}){
									//		Requires redirect on different port
									$rules .= "rdr on ".$natif." proto {tcp udp} from any to ".$extaddr." port {".$extport[0]."} -> ".$target.$localport."\n";
								}
								else{
									//		Ports are the same
									$rules .= "rdr on ".$natif." proto {tcp udp} from any to ".$extaddr." port {".$extport[0]."} -> ".$target."\n";
								}
								break;
							case 'udp':
							case 'tcp':
								if(!empty($extport[0])){
									if((string)$rule->{'external-port'} != (string)$rule->{'internal-port'}){
										//		Requires redirect on different port
										$rules .= "rdr on ".$natif." proto ".(string)$rule->protocol." from any to ".$extaddr." port {".$extport[0]."} -> ".$target.$localport."\n";
									}
									else{
										//		Ports are the same
										$rules .= "rdr on ".$natif." proto ".(string)$rule->protocol." from any to ".$extaddr." port {".$extport[0]."} -> ".$target."\n";
									}
								}
								else{
									$rules .= "rdr on ".$natif." proto ".(string)$rule->protocol." from any to ".$extaddr." -> ".$target."\n";
								}		
							break;
							default:
								Logger::getRootLogger()->error('Inbound NAT rule #'.$rule['id'].' has an invalid protocol setting '.(string)$rule->protocol);
							break;
						}
					}
					else{
						Logger::getRootLogger()->error('Inbound NAT rule #'.$rule['id'].' has no interface defined!');
					}
				}
				else{
					if(!empty($natif)){
						switch((string)$rule->protocol){
							case 'tcp/udp':
								$rules .= "rdr on ".$natif." protoc {tcp udp} from any to ".$extaddr." port ".$extport[0].":".$extport[1]." -> ".$target.$localport.":*\n";
								break;
							case 'udp':
							case 'tcp':
							default:
								$rules .= "rdr on ".$natif." protoc {".(string)$rule->protocol."} from any to ".$extaddr." port ".$extport[0].":".$extport[1]." -> ".$target.$localport.":*\n";
								break;
						}
					}
					else{
						Logger::getRootLogger()->error('Inbound NAT rule #'.$rule['id'].' has no interface defined!');
					}
				}
			
			/*
			 * 	Rule redirects to internal host?
			 * 
			 * 	TODO: I have no clue why we're doing this... ... pfsense
			 * 	does it so it could be required (but could just as easily not)
			 */
			if((string)$rule->{'external-address'} == 'any' && (string)$rule->interface == 'Lan'){
				$iface = $this->framework->getPlugin(ucfirst((string)$rule->interface));
				$ruleif = $iface->getRealInterfaceName();
				$rule_subnet = $iface->getSubnet();
				$rule_network = Functions::calculateNetwork($iface->getIpAddress,$rule_subnet);
				
				if(!empty($ruleif)){
					$rules .= "no nat on ".$ruleif." proto tcp from ".$ruleif." to ".$rule_network."/".$rule_subnet."\n";
					$rules .= "nat on ".$ruleif." proto tcp from ".$rule_network."/".$rule_subnet." to ".$target." port ".$extport[0]." -> ".$ruleif."\n";
				}
				else{
					Logger::getRootLogger()->error('Could not retrieve the real interface name of the interface '.(string)$rule->interface);
				}
			}
					
			if((string)$this->data->natreflection == 'enabled'){
				Logger::getRootLogger()->info('Setting up NAT reflection');
				$rules .= "\n# Reflection redirects \n";
				
				foreach($ifcfg as $interface){
					if(empty($interface->gateway) && (string)$interface->ipaddr != 'dhcp'){
						if( ($extport[1] - $extport[0]) + $localhost_port < 20000){
							//	Quit if 1000 reflection rules have been added
							if($localhost_port < 20000){
								for($x = $extport[0]; $x < $extport[1]; $x++){
									if((string)$rule->protocol == 'tcp/udp'){
										//		Firewall rules
										$rules .= "pass in quick on ".(string)$interface->if." inet proto tcp from any to 127.0.0.1 port ".$localhost_port." keep state label \"NAT Reflection\"";
										$rules .= "pass in quick on ".(string)$interface->if." inet proto udp from any to 127.0.0.1 port ".$localhost_port." keep state label \"NAT Reflection\"";
										$rules .= "rdr on ".(string)$interface->if." proto udp from any to ".$extaddr." port {".$x."} -> 127.0.0.1 port ".$localhost_port;
										$rules .= "rdr on ".(string)$interface->if." proto tcp from any to ".$extaddr." port {".$x."} -> 127.0.0.1 port ".$localhost_port;
										
										//		Write out Inetd entries for NC listeners
										fwrite($inetd_fd, $localhost_port."\tstream\ttcp/udp\tnowait/0\tnobody\t/usr/bin/nc nc -u -w ".$config->reflectiontimeout." ".$target." ".(string)$rule->localport."\n");
									}
									elseif((string)$rule->protocol == 'tcp' || (string)$rule->protocol == 'udp'){
										$rules .= "pass in quick on ".(string)$interface->if." inet proto ".((string)$rule->protocol)." from any to 127.0.0.1 port ".$localhost_port." keep state label \"NAT Reflection\"";
										
										//		Write out Inetd entries for NC listeners
										fwrite($inetd_fd, $localhost_port."\tstream\t".(string)$rule->protocol."\tnowait/0\tnobody\t/usr/bin/nc nc -u -w ".$config->reflectiontimeout." ".$target." ".(string)$rule->localport."\n");
									}
									$localhost_port++;
								}
							}
							else{
								Logger::getRootLogger()->error('Could not install nat rule #'.$rule['id'].', NAT reflection limit exceeded.');
							}
						}
						else{
							Logger::getRootLogger()->error('Activating this port range (#'.$rule['id'].') would exceed the maximum allowable port forwards');
							/*	Pfsense traditionally blocks all ranges > 500, this is senseless since 10 ranges of 50 ports could still be used.
							 *  In order to prevent only half of the port range being implemented we do a pre-check on whether or not
							 *  the range actually fits in the remaining port forward slots.
							 */
						}
					}
					else{
						Logger::getRootLogger()->debug('interface: '.((string)$interface->if).' has a gateway / dhcpclient');
					}
				}
			}
			else{
				Logger::getRootLogger()->info('NAT reflection is disabled - not adding reflection rules or nc listeners');
			}
		}
		return $rules;
	}
	
	/**
	 * 	Generates One on One NAT firewall rules
	 * 
	 * 	@return String
	 * 	@access private
	 */
	private function generateOneOnOneRules(){
		Logger::getRootLogger ()->info ( 'Generating One on one NAT rules' );
		$rules = "\n# One to One NAT rules \n";
		foreach ( $this->data->onetoone->rule as $rule_cfg ) {
			//		Translate interface to their real interface name counterpart
			if(!empty($rule_cfg->interface)){
				$interface = $this->framework->getPlugin ( ucfirst ((string) $rule_cfg->interface ) )->getRealInterfaceName ();
				$rules .= "binat on " . $interface . " from " . (( string ) $rule_cfg->internal) . "/" . (( string ) $rule_cfg->subnet) . " to any -> " . (( string ) $rule_cfg->external) . "/" . (( string ) $rule_cfg->subnet) . " #" . (( string ) $rule_cfg->description) . " \n";
			}
		}
		return $rules;
	}
	
	/**
	 * Shutsdown the Plugin.
	 * 
	 * Called at program shutdown. Useless since the system will shutdown cleanly even on power loss
	 * 
	 * @access public
	 */
	public function shutdown(){
		$this->stop();
	}
}
?>