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
 * Plugin manager
 * Plugin to add/remove plugins from the framework
 * 
 *
 * @author Sebastiaan Gibbon
 * @version 0.0
 */

class PluginManager implements Plugin {
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
		
		//get plugin configs
		$this->data = $this->config->getElement ( 'modules' );
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
	 */
	public function configure() {
	
	}
	
	/**
	 * Starts the plugin
	 */
	public function runAtBoot() {
	
	}
	
	/**
	 * Get info for a front-end page
	 * 
	 * Plugin: PluginManager
	 * Pages: getPlugins, enabled, uninstall, install
	 * 
Page: getPlugins
Exects: null
Reply:
<reply action="ok">
	<plugin (1 or more)
		status="string"			Status of the plugin
		can_uninstall="true|false"	If the plugin can be uninstalled or not.
		name="string"			Name of the plugin.
		startup_order="int"		When the plugin will start 
		enabled="true|false"		If the plugin is enabled or not.
	/>
<reply/>

Page: enabled
Expects: 
$_POST['enable_module'] Name of the plugin to be enabled or disabled
$_POST['enable'] 	The new status of the plugin

Reply:
<reply action="ok">
	<plugin (only one)
		name="string"			Name of the plugin.
		startup_order="int"		When the plugin will start 
		enabled="true|false"		If the plugin is enabled or not.
	/>
</reply>


Page: Uninstall
Expects:
$_POST['uninstall'] Name of the plugin to be uninstalled
Expects(optimal):
$_POST ['keep_data'] If the data should be kept

Reply:
Reply depends on the plugin to be uninstalled. It should only return messages.
<reply action="ok"/>

Page: Install
Expects:
$_FILE['install'] Compressed archive that contains a plugin
unfinished

Reply:
Unfinished
	 */
	public function getPage() {
		//TODO: Check if user may look at the pages.
		

		if (isset ( $_POST ['page'] )) {
			switch ($_POST ['page']) {
				case 'getPlugins' :
					$this->pageGetPlugins ();
					break;
				case 'enabled' :
					$this->pageEnabled ();
					break;
				case 'priority' :
					//Change the priority of a plugin
					//Should not be possible to change using the GUI
					//Install/uninstall classes should change plugin priority.
					break;
				case 'uninstall' :
					$this->pageUninstall ();
					break;
				case 'install' :
					$this->pageInstall ();
					break;
				default :
					throw new Exception ( "page request not valid" );
			}
		} else {
			throw new Exception ( "page request not valid" );
		}
	}
	
	private function pageGetPlugins() {
		//Print a list with all plugins and information about the plugins.
		echo "<reply action=\"ok\">\n";
		
		//To get the status of all plugins, they must all be started first.
		//As a safety measure, check that it's not running at boot.
		$this->framework->startAllPlugins ( false );
		
		//Print each plugin
		foreach ( $this->data as $plugin ) {
			$plugin_object = $this->framework->getPlugin ( ( string ) $plugin ['name'] );
			$status = "";
			if (isset ( $plugin_object )) {
				$status = $plugin_object->getStatus ();
			} else if (( string ) $plugin ['enabled'] == "true") {
				throw new Exception('The module '.$plugin['name'].' could not be retrieved');
			}
			
			echo "<plugin status=\"{$status}\" ";
			//also print all other attributes the plugin has.
			foreach ( $plugin->attributes () as $name => $value ) {
				echo " $name = \"$value\"";
			}
			echo "/>\n"; //Closing tag of plugin
		}
		echo "</reply>";
	}
	
	/**
	 * Enable or disable a plugin.
	 */
	private function pageEnabled() {
		//Check if the plugin is valid and exits 	
		if (isset ( $_POST ['enable_module'] ) || strlen ( $_POST ['enable_module'] ) < 1 || ! ereg ( "[a-zA-Z]+", $_POST ['enable_module'] )) {
			throw new Exception ( "Invalid plugin name {$_POST['enable_module']}" );
		}
		
		$plugin = $this->data->xpath ( "plugin[@name='{$_POST ['enable_module']}']" );
		
		//Check if there where any plugins found with that name
		if (! is_array ( $plugin ) || empty ( $plugin [0] )) {
			throw new Exception ( "The plugin {$_POST['enable_module']} could not be found." );
		}
		
		Logger::getRootLogger ()->info ( "Changing status of module {$_POST ['enable_module']} to {$_POST ['enable']}" );
		
		//Enable/disbale the plugin in XML
		if ($_POST ['enable'] == 'true' || $_POST ['enable'] == '1') {
			$plugin [0] ['enabled'] = "true";
		} elseif ($_POST ['enable'] == 'false' || $_POST ['enable'] == '0') {
			
			//Try to stop the plugin, when going from enabled to disabled.
			$plugin_object = $this->framework->getPlugin ( ( string ) $plugin [0] ['name'] );
			if (isset ( $plugin_object ) && $plugin_object->isService () && ( string ) $plugin [0] ['enabled'] == "true") {
				$plugin_object->stop ();
			}
			
			$plugin [0] ['enabled'] = "false";
		} else {
			throw new Exception ( "Invalid status option for a plugin. Did not disable/enable {$_POST ['enable_module']}." );
		}
		
		//Save config and print the changed plugin
		if ($this->config->saveConfig ()) {
			echo "<reply action=\"ok\"><plugin";
			foreach ( $plugin [0]->attributes () as $name => $value ) {
				echo " $name = \"$value\"";
			}
			echo "/></reply>";
		} else {
			throw new Exception ( "Error, could not save configuration file." );
		}
	}
	
	/**
	 * Uninstall a plugin.
	 * 
	 * expecting $_POST['uninstall'] with module name to be uninstalled.
	 */
	private function pageUninstall() {
		//Test if the module has a valid name and is a valid plugin and can uninstall
		if (empty ( $_POST ['uninstall'] ) || strlen ( $_POST ['uninstall'] ) < 1 || ! ereg ( "[a-zA-Z]+", $_POST ['uninstall'] )) {
			throw new Exception ( "Invalid plugin name {$_POST['uninstall']} or could not uninstall module." );
		}
		
		//Get the plugin and check if it is possible to uninstall.
		$plugin = $this->data->xpath ( "plugin[@name='{$_POST ['uninstall']}']" );
		
		//Check if there where any plugins found with that name and can be uninstalled
		if (sizeof($plugin) < 1 || empty ( $plugin [0] ) || empty ( $plugin [0] ['can_uninstall'] ) || ( string ) $plugin [0] ['can_uninstall'] != "true") {
			throw new Exception ( "The plugin {$_POST ['uninstall']} may not be uninstalled." );
		}
		
		Logger::getRootLogger ()->info ( "Uninstalling plugin {$plugin [0] ['name']}" );
		
		/*
		//Stop the plugin, if possible
		//move to uninstall scripts?
		$plugin_obj = $this->framework->getPlugin ( (string)$plugin [0] ['name'] );
		if (isset ( $plugin_obj )) {
			Logger::getRootLogger ()->info ( "Stopping plugin for uninstall" );
			$plugin_obj->stop ();
			unset ( $plugin_obj );
		}*/
		
		$result = "uninstalled"; //default

		//Call uninstall.php, if exists, to perform additional uninstall instructions
		if (file_exists ( PluginFramework::MODULE_PATH."/" . ( string ) $plugin [0] ['name'] . "/uninstall.php" )) {
			//run uninstall class in Modules/$module/uninstall.php
			Logger::getRootLogger ()->info ( "Calling {$plugin [0] ['name']}/uninstall.php" );
			require_once PluginFramework::MODULE_PATH."/" . ( string ) $plugin [0] ['name'] . "/uninstall.php";
			
			$menu = new Config ( PluginFramework::MENU_PATH );
			$uninstall = new Uninstall ( $this->framework, $this->config, $plugin [0], $menu );
			$result = $uninstall->getResult ();
		}
		
		//remove plugin config
		if ($result == "uninstalled") { // && empty ( $_POST ['keep_data'] )
			Logger::getRootLogger ()->info ( "Removing plugin configuration" );
			
			$this->config->deleteElement ( $plugin [0] );
			
			//safe the config and remove the plugin directory
			if ($this->config->saveConfig ()) {
				//Remove plugin location. rmdir() does not do it recursively, so do it using the shell.
				Logger::getRootLogger ()->info ( "Removing plugin files" );
				Functions::shellCommand ( "rm -r ".PluginFramework::MODULE_PATH."/" . ( string ) $plugin [0] ['name'] );
				
				echo "<reply action=\"ok\"/>";
			} else {
				throw new Exception ( "Error, could not save configuration file." );
			}
		} else {
			throw new Exception ( "Could not uninstall the plugin. Reason given: {$result}" );
		}
	}
	
	private function pageInstall() {
		//TODO: Create install page
	//expect $_FILE['install']
	//extract file to /tmp/
	//read module information file
	//put files into modules/$module/
	//run install class in modules/$module/install.php
	//install class may have a custom install form and should return information if install is done.
	}
	
	/**
	 * Gets a list of dependend plugins
	 */
	public function getDependency() {
	
	}
	
	/**
	 * Starts the plugin
	 * 
	 * @return string Status of the service/plugin
	 */
	public function getStatus() {
	
	}
	
	/**
	 * Shutsdown the Plugin.
	 * Called at program shutdown. 
	 */
	public function shutdown(){

	}
}
?>