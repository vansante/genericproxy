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
 * AssignInterfaces plugin
 * Assigns an interface type (lan, wan, ext) to a real system interface(em0, em1, lo0, etc)
 *
 * @author Sebastiaan Gibbon
 * @version 0.0
 */

class AssignInterfaces implements Plugin {
	
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
		
		$this->data = $this->config->getElement ( 'interfaces' );
	}
	
	/**
	 * Is the Plugin a service?
	 * 
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
	
	}
	
	/**
	 * Stop the service
	 * 
	 * @return bool false when service failed to stop
	 */
	public function stop() {
	
	}
	
	/**
	 * Write configuration to the system
	 * 
	 * @return int Returns status of configuration. 0 is ok, 1 or higher means an error occurd.
	 */
	public function configure() {}
	
	/**
	 * Starts the plugin
	 */
	public function runAtBoot() {}
	
	/**
	 * Get info for a front-end page
	 * 
	 * @access public
	 * @throws Exception
	 */
	public function getPage() {
		if (!empty( $_POST ['page'] )) {
			switch ($_POST ['page']) {
				case 'getconfig' :
					$this->getSettings ();
					break;
				case 'save' :
					$this->save ();
					break;
				case 'getinterfaces' :
					$this->getSettings ();
					break;
				case 'getstatus':
					$this->getInterfaceStatus();
					break;
				default :
					throw new Exception ( "Invalid page identifier" );
			}
		} else {
			throw new Exception ( "page request not valid" );
		}
	}
	
	/**
	 * Saves the interface type settings 
	 * 
	 * After saving the config, the DHCPD server will be restarted. 
	 * 
	 * @access private
	 * @throws Exception
	 */
	private function save() {
		if($_POST['interfaces_assign_lan'] != $_POST['interfaces_assign_wan'] && $_POST['interfaces_assign_ext'] != $_POST['interfaces_assign_wan'] && $_POST['interfaces_assign_ext'] != $_POST['interfaces_assign_lan']){
			$interface = $this->getInterfaceConfig ( "Lan" );
			$interface->if = $_POST ['interfaces_assign_lan'];
			$interface = $this->getInterfaceConfig ( "Wan" );
			$interface->if = $_POST ['interfaces_assign_wan'];
			$interface = $this->getInterfaceConfig ( "Ext" );
			$interface->if = $_POST ['interfaces_assign_ext'];
			
			//Save config and print the data
			if ($this->config->saveConfig ()) {
				echo '<reply action="ok">';
				echo '<message>The system needs to reboot for settings to take effect.</message>';
				foreach ( $this->data->interface as $interface ) {
					echo "<interface current=\"{$interface['type']}\"><name>{$interface->if}</name></interface>\n";
				}
				echo '</reply>';
			} else {
				//The config file could not be written.
				throw new Exception ( "Error, could not save configuration file." );
			}
		}
		else{
			throw new Exception('Each interface can only be assigned to one type');
		}
	}
	
	/**
	 * Gets a list of dependend plugins
	 */
	public function getDependency() {}
	
	/**
	 * gets the settings of which interface is bound to which type.
	 * 
	 * @access private
	 * @return string Status of the service/plugin
	 */
	private function getSettings() {
		echo '<reply action="ok">';
		foreach ( $this->data->interface as $interface ) {
			echo "<interface current=\"{$interface['type']}\"><name>{$interface->if}</name></interface>\n";
		}
		echo '</reply>';
	}
	
	/**
	 * Prints a list of available interfaces 
	 * 
	 * @access private
	 */
	private function getInterfaces() {
		echo '<reply action="ok">';
		foreach ( $this->getInterfaceList () as $interface ) {
			echo "<interface current=\"{$interface['type']}\"><name>{$interface['name']}</name></interface>\n";
		}
		echo '</reply>';
	}
	
	/**
	 * Returns a array with all interfaces and their vendor
	 * 
	 * @access public
	 * @return array Returns a 2D Array, 1st key is number, 2nd keys are 'name' and 'vendor'.
	 */
	public function getInterfaceList() {
		$i = 0;
		$interfaces = array();
		
		$temp = Functions::shellCommand('ifconfig');
		$temp = explode("\n",$temp);

		while($i < count($temp)){
			if(stristr($temp[$i],'flags')){
				$position = strpos($temp[$i],":",0);
				$tmp = substr($temp[$i],0,$position);
		
				if($tmp != 'lo0'){
					$interfaces[] = $tmp;
				}
			}
			$i++;
		}
		
		return $interfaces;
	}
	
	/**
	 * echoes XML with ifconfig output for all configured interfaces
	 * 
	 * @access public
	 */
	private function getInterfaceStatus(){
		$buffer = '<reply action="ok">';
		$buffer .= '<interfaces>';
		foreach($this->data->interface as $interface){
			$ifconfig = null;
			$ifconfig = Functions::shellCommand('ifconfig '.$interface->if);
			$buffer .= '<'.strtolower($interface['type']).'>';

			$ip = substr($ifconfig,0,strpos($ifconfig,' ') - 1);
			$ifconfig = str_replace(substr($ifconfig,0,strpos($ifconfig,' ') + 1),'',$ifconfig);

			$buffer .= '<device>'.$ip.'</device>';
			$buffer .= '<status><![CDATA['.$ifconfig.']]></status>';
			$buffer .= '</'.strtolower($interface['type']).'>';
		}
		$buffer .= '</interfaces>';
		$buffer .= '</reply>';
		
		echo $buffer;
	}
	
	/**
	 * Returns the XML configration of the speciefied type. 
	 * 
	 * If the type not found, an interface config will be created for it.
	 * 
	 * @access private
	 * @param string $type Type of interface to get.
	 * @return SimpleXMLElement Interface config of that type.
	 */
	private function getInterfaceConfig($type) {
		foreach ( $this->data->interface as $interface ) {
			if ($interface ['type'] == $type) {
				return $interface;
			}
		}
		$interface = $this->data->addChild ( "interface" );
		$interface ['type'] = $type;
		return $interface;
	}
	
	/**
	 * Shutsdown the Plugin.
	 * 
	 * Called at program shutdown.
	 * 
	 * @access public
	 */
	public function shutdown() {}
	
	/**
	 * Starts the plugin
	 * 
	 * @access public
	 * @return string Status of the service/plugin
	 */
	public function getStatus() {}
}
?>