<?php
require_once (PluginFramework::FRAMEWORK_PATH . '/libs/GeneratesRules.php');
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
 * Module generates and maintains altq rules as well as the rules that
 * force packets into the correct queues
 * 
 * Will not function with firewall software other than pf
 *
 * @uses Firewall
 * @uses Cron
 * @version 1.0
 */
class Scheduler implements Plugin,GeneratesRules {
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
	private $shaper_data;
	private $scheduler_data;
	
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
		
		//      Get scheduler XML configuration
		$this->shaper_data = $this->config->getElement ( 'shaper' );
		$this->scheduler_data = $this->config->getElement('scheduler');
	}
	
	/**
	 * Return modules this module depends on
	 * 
	 * @access public
	 */
	public function getDependency() {
		return array ('Firewall' );
	}
	
	/**
	 * 	Getpage implementation for Scheduler front-end
	 * 
	 * 	@access public
	 * 	@throws Exception
	 */
	public function getPage() {
		switch($_POST['page']){
			case 'getconfig':
				$this->returnConfig();
				break;
			case 'save':
				$this->saveConfig();
				break;
			case 'addconfig':
				$this->saveUserConfig();
				break;
			case 'deleteconfig':
				$this->removeUserSchedule(htmlentities($_POST['name']),true);
				break;
			default:
				throw new Exception('Invalid page request');
				break;
		}
	}
	
	/**
	 * 	Empty functions unused by this plugin
	 * 	it has no services or configurations of it's own and only returns firewall rules
	 */
	public function getStatus() {}
	public function runAtBoot() {}
	public function shutdown() {}
	public function start() {}
	public function stop() {}
	public function configure() {}
	
	/**
	 * 	remove a user defined schedule preset
	 * 
	 * 	@param String	$name	name of the schedule to remove
	 * 	@param Bool		$return	whether or not to return an XML reply	
	 */
	private function removeUserSchedule($name,$return){
		foreach($this->scheduler_data->userdefined as $schedule){
			if($schedule['name'] == $name){
				//	We found a configuration with the same name, remove it
				$this->config->deleteElement($schedule);
				
				if($return){
					$this->config->saveConfig();
					echo '<reply action="ok" />';
					return 1;
				}
				else{
					return 1;
				}
			}
		}
		
		if($return){
			throw new Exception('The specified schedule could not be found');
		}
	}
	
	/**
	 * 	Saves user-added schedule presets
	 * 
	 * 	@access private
	 * 	@throws	Exception
	 */
	private function saveUserConfig(){
		$name = htmlentities($_POST['services_sharing_config_name']);
		
		$this->removeUserSchedule($name,false);
		
		//	Cleanup completed, add the new schedule
		$newschedule = $this->scheduler_data->addChild('userdefined');
		$newschedule->addAttribute('name',$name);
		
		$days = explode(':',$_POST['services_sharing_config_schedule']);
		
		$i = 0;
		foreach($days as $day){
			$dayschedule = $newschedule->addChild('day');
			$dayschedule->addAttribute('id',$i);

			$hours = explode(',',$day);
			
			$h = 0;
			foreach($hours as $hour){
				$dayschedule->addChild('h'.$h,$hour);
				$h++;
			}
			$i++;
		}
		
		echo '<reply action="ok"><sharing>';
		echo $newschedule->asXML();
		echo '</sharing></reply>';
		
		$this->config->saveConfig();
	}
	
	/**
	 * Saves schedule information to config XML
	 * 
	 * @access private
	 */
	private function saveConfig(){
		$days = explode(':',$_POST['services_sharing_schedule']); 
		$day = 0;

		$this->config->deleteElement($this->scheduler_data->schedule);
		
		//		Reply XML
		$return = new SimpleXMLElement('<reply></reply>');
		$return->addAttribute('action','ok');
		$return->addChild('sharing');
		$return->sharing->addChild('schedule');
		$return->sharing->addChild('standard');
		$return->sharing->standard->addChild('downspeed',$_POST['services_sharing_standard_download_speed']);
		$return->sharing->standard->addChild('upspeed',$_POST['services_sharing_standard_upload_speed']);
		$return->sharing->addChild('optional');
		$return->sharing->optional->addChild('downspeed',$_POST['services_sharing_optional_download_speed']);
		$return->sharing->optional->addChild('upspeed',$_POST['services_sharing_optional_upload_speed']);
		
		$return->sharing->addChild('maxupspeed',$_POST['services_sharing_upload']);
		$return->sharing->addChild('maxdownspeed',$_POST['services_sharing_download']);
		
		$this->scheduler_data->addChild('maxupspeed',$_POST['services_sharing_upload']);
		$this->scheduler_data->addChild('maxdownspeed',$_POST['services_sharing_download']);
		
		$this->scheduler_data->addChild('schedule');
		$this->scheduler_data->schedule->addChild('standard');
		$this->scheduler_data->schedule->standard->addChild('downspeed',$_POST['services_sharing_standard_download_speed']);
		$this->scheduler_data->schedule->standard->addChild('upspeed',$_POST['services_sharing_standard_upload_speed']);
		$this->scheduler_data->schedule->addChild('optional');
		$this->scheduler_data->schedule->optional->addChild('downspeed',$_POST['services_sharing_optional_download_speed']);
		$this->scheduler_data->schedule->optional->addChild('upspeed',$_POST['services_sharing_optional_upspeed_speed']);
		
		$this->scheduler_data->schedule->addChild('days');
		
		$i = 0;
		foreach($days as $day){
			//		Return XML
			$return_day = $return->sharing->schedule->addChild('day');
			$return_day->addAttribute('id',$i);
			
			$xml_day = $this->scheduler_data->schedule->days->addChild('day');
			$xml_day->addAttribute('day_id',$i);
			
			$buffer = explode(',',$day);
			$hour = 0;
			while($hour < 24){
				$return_day->addChild('h'.$hour,$buffer[$hour]);
				
				if($hour == 0 || $buffer[$hour - 1] != $buffer[$hour]){
					$block = $xml_day->addChild('block');	
					$block->addAttribute('start',$hour);
					$block->addAttribute('config',$buffer[$hour]);
				}
				$hour++;
			}
			$i++;
		}
		
		$this->config->saveConfig();
		echo $return->asXML();
	}
	
	/**
	 * transforms scheduler config XML to front-end acceptable format
	 * 
	 * Transformation is required because the saved configuration is optimal for cron-based
	 * switching of rulesets, but sub-optimal for filling the scheduling table in the frontend
	 * 
	 * @access private
	 */
	private function returnConfig(){		
		$nbuffer = '<maxupspeed>'.(string)$this->scheduler_data->maxupspeed.'</maxupspeed>';
		$nbuffer .= '<maxdownspeed>'.(string)$this->scheduler_data->maxdownspeed.'</maxdownspeed>';
		
		$nbuffer .= '<standard>';
		$nbuffer .= '<upspeed>'.(string)$this->scheduler_data->schedule->standard->upspeed.'</upspeed>';
		$nbuffer .= '<downspeed>'.(string)$this->scheduler_data->schedule->standard->downspeed.'</downspeed>';
		$nbuffer .= '</standard>';

		$nbuffer .= '<optional>';
		$nbuffer .= '<downspeed>'.(string)$this->scheduler_data->schedule->optional->downspeed.'</downspeed>';
		$nbuffer .= '<upspeed>'.(string)$this->scheduler_data->schedule->optional->upspeed.'</upspeed>';
		$nbuffer .= '</optional>';
		
		$nbuffer .= '<schedule>';

		foreach($this->scheduler_data->schedule->days->day as $day){
			$nbuffer .= '<day id="'.$day['day_id'].'">';
			
			$hour = 0;
			$i = 0;
			while($i < count($day->block)){
				while($hour <= (string)$day->block[$i + 1]['start'] && $hour < 24){
					$nbuffer .= '<h'.$hour.'>'.$day->block[$i]['config'].'</h'.$hour.'>';
					$hour++;
				}
				$i++;
			}
			while($hour < 24)
			{
				$nbuffer .= '<h'.$hour.'>'.$day->block[$i -1]['config'].'</h'.$hour.'>';
				$hour++;
			}
			
			$nbuffer .= '</day>';
		}
		
		$nbuffer .= '</schedule>';

		echo '<reply action="ok">';
		echo '<sharing>';
		echo $nbuffer;
		foreach($this->scheduler_data->predefined as $predefined){
			echo $predefined->asXML();
		}
		foreach($this->scheduler_data->userdefined as $userdefined){
			echo $userdefined->asXML();
		}
		echo '</sharing>';
		echo '</reply>';
	}
	
	/**
	 * Generates firewall rules to route packets into queues
	 * 
	 * @return Array
	 */
	public function getFirewallRules() {
		$return = null;
		
		//		Get altq rules
		$return [0] ['category'] = 'altq';
		$return [0] ['rules'] = $this->generateQueues ();
		
		//		Get filter rules
		$return [1] ['category'] = 'filter';
		$return [1] ['rules'] = $this->generateFilterRules();
		
		//		return all the crud
		return $return;
	}
	
	/**
	 * Generates altq rules 
	 * 
	 * @access public
	 * @return String
	 */
	public function generateQueues() {
		$subs = null;
		
		foreach ( $this->data->rootqueue as $queue ) {
			$subqueues = implode ( ',', $subs [$queue->name] );
			//	altq on {interface} bandwidth {bandwidth} queue {subqueue,subqueue}
			foreach ( $queue->subqueue as $pipes ) {
				// queue {name} bandwidth (bandwidth) priority (priority) cbq(borrow)
				$pipes .= "queue " . $pipes->name . " bandwidth " . $pipes->bandwidth . " priority " . $pipes->priority . " ".$pipes->queuetype."(borrow)\n";
				$subqueues .= $pipes->name;
			}
			$queues .= "altq on " . $queue->interface . " bandwidth " . $queue->bandwidth . " queue {" . $subqueues . "}\n";
		}
		
		return $queues . $pipes;
	}
	
	/**
	 * Generates filter rules for pf, rules route packets into the correct queue
	 * 
	 * @access public
	 * @return String
	 */
	private function generateFilterRules() {
		foreach ( $this->data->rule as $rule ) {
			//		Parse source
			if (( string ) $rule->source ['invert'] == "true") {
				$source .= 'not ';
			}
			
			if (( string ) $rule->source->type == 'any') {
				//		Source is any
				$source .= 'any';
			} elseif (( string ) $rule->source->type == 'address') {
				//		Source is an IP address
				if (empty ( $rule->source->subnet )) {
					$source .= ( string ) $rule->source->address;
				} else {
					$source .= ( string ) $rule->source->address . '/' . $rule->source->subnet;
				}
			} elseif (( string ) $rule->source->type == 'Lansubnet') {
				//		Destination is the lan subnet, so fetch it
				$lanconfig = $this->config->getElement ( 'interfaces' );
				foreach ( $lanconfig as $interface ) {
					if (( string ) $interface->type == 'lan') {
						$subnet = ( string ) $interface->subnet;
						$ip = ( string ) $interface->ipaddr;
						
						//	Calculate network
						$network = Functions::calculateNetwork ( $ip, $subnet );
						$source .= $network . '/' . $subnet;
					}
				}
			} elseif (( string ) $rule->source->type == 'Wanaddress') {
				//		Destination is WAN address, fetch it
				$wan = $this->framework->getPlugin ( 'Wan' );
				$ipaddr = $wan->getInterfaceIP ();
				$source .= $ipaddr;
			} elseif (( string ) $rule->source->type == 'Lan' || ( string ) $rule->source->type == 'Wan') {
				//		Source is an interface
				$module = $this->framework->getPlugin ( ( string ) $rule->source->type );
				$interface = $module->getRealInterfaceName ();
				
				$source .= $interface;
			
			} elseif (stristr ( 'Ext', ( string ) $rule->source->type )) {
				/*	Source is the EXT interface, this one's a bit special since there could be multiple ones
				 *		that are all handled by the same module
				 */
				$module = $this->framework->getPlugin ( 'Ext' );
				$interface = $module->getRealInterfaceName ( $rule->source->type );
				
				$source .= $interface;
			}
			
			if (! empty ( $rule->source->port )) {
				//		Source includes a specific port
				$source .= ' port = ' . ( string ) $rule->source->port;
			}
			
			//		Parse destination
			if (( string ) $rule->destination ['invert'] == "true") {
				$destination .= 'not ';
			}
			if (( string ) $rule->destination->type == 'any') {
				//		Destination is any
				$destination .= 'any';
			} elseif (( string ) $rule->destination->type == 'address') {
				//		Destination is an IP address
				if (empty ( $rule->destination->address )) {
					$destination .= ( string ) $rule->destination->address;
				} else {
					$destination .= ( string ) $rule->destination->address . '/' . $rule->destination->subnet;
				}
			} elseif (( string ) $rule->destination->type == 'Lansubnet') {
				//		Destination is the lan subnet, so fetch it
				$lanconfig = $this->config->getElement ( 'interfaces' );
				foreach ( $lanconfig as $interface ) {
					if (( string ) $interface->type == 'lan') {
						$subnet = ( string ) $interface->subnet;
						$ip = ( string ) $interface->ipaddr;
						
						//	Calculate network
						$network = Functions::calculateNetwork ( $ip, $subnet );
						$destination .= $network . '/' . $subnet;
					}
				}
			} elseif (( string ) $rule->destination->type == 'Wanaddress') {
				//		Destination is WAN address, fetch it
				$wan = $this->framework->getPlugin ( 'Wan' );
				$ipaddr = $wan->getInterfaceIP ();
				$destination .= $ipaddr;
			} elseif (( string ) $rule->destination->type == 'Lan' || ( string ) $rule->destination->type == 'Wan') {
				//		Destination is an interface
				$module = $this->framework->getPlugin ( $rule->source );
				$tmp_interface = $module->getRealInterfaceName ();
				
				$destination .= $tmp_interface;
			
			} elseif (stristr ( 'ext', ( string ) $rule->destination->type )) {
					/*		Source is the EXT interface, this one's a bit special since there could be multiple
					 *		that are all handled by the same module
					 */
				$module = $this->framework->getPlugin ( 'Ext' );
				$tmp_interface = $module->getRealInterfaceName ( $rule->destination );
				
				$destination .= $tmp_interface;
			}
			
			if (( string ) $rule->destination->port != '') {
				//		Destination includes a specific port
				$destination .= ' port = ' . ( string ) $rule->destination->port;
			}
			
			//		Parse interface
			if (stristr ( 'ext', $rule->interface )) {
				$module = $this->framework->getPlugin ( 'Ext' );
				$interface = $module->getRealInterfaceName ( $rule->interface );
			} else {
				$module = $this->framework->getPlugin ( $rule->source );
				$interface = $module->getRealInterfaceName ();
			
			}
			
			$buffer .= "pass " . $rule->direction . " on " . $interface . " inet proto " . $rule->protocol . " from " . $source . " to " . $destination . " \ queue" . $rule->queue . "\n";
		}
	}

}
?>