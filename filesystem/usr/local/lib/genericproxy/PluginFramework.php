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
 * Create a object from config.xml
 * 
 * @author Sebastiaan Gibbon
 * @version 0
 */
require_once ("config.php");
require_once ("log4php/Logger.php");
require_once ("Plugin.php");
require_once ("libs/Functions.php");
require_once ("User.php");
require_once ("Error.php");

class PluginFramework {
	/**
	 * An array of all loaded plugins. Key contains modulename, value is the Plugin.
	 * 
	 * At boot, all plugins will be loaded, in browser mode they are loaded on demand.
	 * 
	 * @var Plugin[]
	 */
	public $plugins = array ();
	/**
	 * The config to be used throughout the application
	 * 
	 * @var Config
	 */
	public $configuration;
	/**
	 * The user that is logged in.
	 * 
	 * @var User
	 */
	public $user;
	/**
	 * Running mode of the script. Contains RUNTYPE_STARTUP or RUNTYPE_BROWSER
	 * 
	 * @var int
	 */
	public $runtype;
	
	/**
	 * The class is being called when booting the system
	 * 
	 * @var int
	 */
	const RUNTYPE_STARTUP = 1;
	
	/**
	 * The class is being called from the browser
	 * 
	 * @var int
	 */
	const RUNTYPE_BROWSER = 2;
	
	/**
	 * The class is being called from the command line
	 * 
	 * @var int
	 */
	const RUNTYPE_CLI = 3;
	
	/**
	 * The class is being called when shutting down the system
	 * 
	 * @var int
	 */
	const RUNTYPE_SHUTDOWN = 4;
	
	/**
	 * Location to the config file.
	 * 
	 * @var string
	 */
	const CONFIG_PATH = '/etc/GenericProxy';
	
	/**
	 * Location to where the menu file will be saved to, used for browser mode.
	 * 
	 * @var string
	 */
	const MENU_PATH = '/usr/local/www/menu.xml';
	
	/**
	 * Path to the www diretory.
	 * 
	 * @var string
	 */
	const WWW_PATH = '/usr/local/www';
	
	/**
	 * Location to the logging configuration file when the system is in boot mode
	 * 
	 * @var string
	 */
	const LOGGER_CONFIG_BOOT = 'logger.boot.properties';
	
	/**
	 * Location to the logging configuration file when the system is in browser mode
	 * 
	 * @var string
	 */
	const LOGGER_CONFIG_BROWSER = 'logger.browser.properties';
	
	/**
	 * Location to the logging configuration file when the system is in CLI mode
	 * 
	 * @var string
	 */
	const LOGGER_CONFIG_CLI = 'logger.cli.properties';
	
	/**
	 * Location to the logging configuration file when the system is in shutdown mode
	 * 
	 * @var string
	 */
	const LOGGER_CONFIG_SHUTDOWN = 'logger.shutdown.properties';
	
	/**
	 * Location of this file, where GenericProxy framework files are located
	 * 
	 * @var string
	 */
	const FRAMEWORK_PATH = '/usr/local/lib/genericproxy';
	
	/**
	 * Location to where the framework plugins are located.
	 * 
	 * @var string
	 */
	const MODULE_PATH = '/usr/local/lib/genericproxy/Modules';
	
	/**
	 * Software version 
	 * 
	 * @var String
	 */
	const VERSION = '0.9';
	
	/**
	 * This class is the start of the application.
	 * 
	 * Which plugins will be loaded depends on the configuration. Do not make more then one instance.
	 * 
	 * @param int $runtype Running mode of the script. Pass RUNTYPE_STARTUP or RUNTYPE_BROWSER.
	 */
	public function __construct($runtype) {
		$this->runtype = $runtype;
		try {
			//Config log4php; the logger
			date_default_timezone_set ( date_default_timezone_get() ); //Looks odd, but using date_default_timezone_set will turn off the E_NOTICE and E_WARNING when a time/date function is called.
			
			//Logger::configure ( self::CONFIG_PATH . "/" . (($runtype == self::RUNTYPE_STARTUP) ? self::LOGGER_CONFIG_BOOT : self::LOGGER_CONFIG_BROWSER) );
			switch ($runtype) {
				case self::RUNTYPE_STARTUP :
					Logger::configure ( self::CONFIG_PATH . "/" . self::LOGGER_CONFIG_BOOT );
					Logger::getRootLogger ()->info ( "Starting up plugin framework in Boot mode" );
					break;
				case self::RUNTYPE_BROWSER :
					Logger::configure ( self::CONFIG_PATH . "/" . self::LOGGER_CONFIG_BROWSER );
					Logger::getRootLogger ()->info ( "Starting up plugin framework in browser mode" );
					break;
				case self::RUNTYPE_CLI :
					Logger::configure ( self::CONFIG_PATH . "/" . self::LOGGER_CONFIG_CLI );
					Logger::getRootLogger ()->info ( "Starting up plugin framework in CGI mode" );
					break;
				case self::RUNTYPE_SHUTDOWN :
					Logger::configure ( self::CONFIG_PATH . "/" . self::LOGGER_CONFIG_SHUTDOWN );
					Logger::getRootLogger ()->info ( "Starting up plugin framework in shutdown mode" );
					break;
			}
			
			//Set custom error handler
			$old_error_handler = set_error_handler ( "Functions::errorHandler" );
			
			//Load the configuration
			$this->loadConfig ();
			
			//The pluginframework can be called at boot, or from the browser.
			//At boot, all plugins will be loaded.  The browser only loads the one.			
			switch ($this->runtype) {
				case self::RUNTYPE_BROWSER :
					$this->startBrowser ();
					break;
				case self::RUNTYPE_STARTUP :
					$this->startBoot ();
					break;
				case self::RUNTYPE_CLI :
					$this->startCLI ();
					break;
				case self::RUNTYPE_SHUTDOWN :
					$this->startShutdown ();
					break;
				default :
					throw new Exception ( "Invalid startup type" );
			}
			//End of program.
		} catch ( Exception $e ) {
			$this->printException ( $e );
		}
		
		Logger::shutdown ();
	}
	
	/**
	 * Start the framework in Browser mode
	 */
	private function startBrowser() {
		//Running in Browser mode
		

		ignore_user_abort ( true ); //Always complete the script, to avoid unpleasantness. For example; while saving the config and the script stops midway.
		

		//Send headers
		header ( 'Content-type: text/xml' );
		header ( 'Expires: ' . gmdate ( 'D, d M Y H:i:s', time () ) . ' GMT' );
		
		//Handle login
		$this->User = new User ( $this->configuration );
		if(empty($_POST['module']) && $_POST['page'] == 'logout'){
			$this->User->logout();
		}
		elseif (! $this->User->isLoggedIn ()) {
			//Return login error and/or handle login.
			$this->User->login ();
		}
		elseif (isset ( $_POST ['module'] ) && strlen ( $_POST ['module'] ) > 0 && ereg ( "[a-zA-Z]+", $_POST ['module'] )) {
			//start the plugin and return the requested page. 
			$plugin = $this->getPlugin ( $_POST ['module'] );
			if (isset ( $plugin )) {
				$plugin->getPage ();
			} else {
				throw new Exception ( "Module could not be found or started." );
			}
		} else {
			throw new Exception ( "Invalid module name." );
		}
	}
	
	/**
	 * Start the framework in CLI mode
	 */
	private function startCLI() {
		global $argv;
		
		//Show the satus of all plugins.
		if (empty ( $argv [1] )) {
			echo "Plugin\t\tStatus\n";
			Logger::getRootLogger ()->setLevel ( LoggerLevel::getLevelWarn () );
			$this->startAllPlugins ( false );
			
			foreach ( $this->plugins as $name => $plugin ) {
				echo $name . "\t\t" . (($plugin->isService ()) ? $plugin->getStatus () : "Not a service.") . "\n";
			}
			return;
		}
		
		//start the plugin and return the requested page. 
		$plugin = $this->getPlugin ( $argv [1] );
		if (isset ( $plugin )) {
			switch ($argv [2]) {
				case 'start' :
					$plugin->start ();
					break;
				case 'stop' :
					$plugin->stop ();
					break;
				case 'boot' :
					$plugin->runAtBoot ();
					break;
				case 'config' :
					$plugin->configure ();
					break;
				case 'config' :
					$plugin->getStatus ();
					break;
				default :
					$plugin->$argv [2] ();
					break;
			}
		}
		Logger::getRootLogger ()->debug ( "Peak Memory usage: " . memory_get_peak_usage ( false ) . " Bytes" );
		Logger::getRootLogger ()->debug ( "Peak  Memory real usage: " . memory_get_peak_usage ( true ) . " Bytes" );
	}
	
	/**
	 * Start the framework in boot mode
	 */
	private function startBoot() {
		//At Boot
		//Enable console output if its muted.
		//Functions::shellCommand("/sbin/conscontrol mute off >/dev/null");
		

		//TODO: Mount filsystem here, instead of in plugins.
		//Functions::mountFilesystem('rw');
		

		//Run early shell commands found in the config
		foreach ( $this->configuration->getElement ( 'system' )->earlyshellcmd [0] as $command ) {
			if ($command->getName () == 'command' && strlen ( ( string ) $command ) > 1) {
				$result = Functions::shellCommand ( ( string ) $command );
				Logger::getRootLogger ()->info ( "Running custom command '$command' Result: {$result}" );
			}
		}
		
		//No user information exists at boot.
		//$this->User = User::getRoot ( $this->configuration ); 
		$this->startAllPlugins ( true );
		
		//Run remaining shell commands found in the config
		foreach ( $this->configuration->getElement ( 'system' )->shellcmd [0] as $command ) {
			if ($command->getName () == 'command' && strlen ( ( string ) $command ) > 1) {
				$result = Functions::shellCommand ( ( string ) $command );
				Logger::getRootLogger ()->info ( "Running custom command '$command' " . ((strlen ( $result ) > 0) ? (" Result: " . $result) : "") );
			}
		}
		
		//Functions::mountFilesystem('ro');
		

		Logger::getRootLogger ()->debug ( "Peak Memory usage: " . memory_get_peak_usage ( false ) . " Bytes" );
		Logger::getRootLogger ()->debug ( "Peak  Memory real usage: " . memory_get_peak_usage ( true ) . " Bytes" );
	}
	
	/**
	 * Start the shutdown of the system.
	 */
	private function startShutdown() {
		
		//Get a list of plugins
		$modules = $this->configuration->getElement ( "modules" );
		foreach ( $modules as $plugin_xml ) {
			if (( string ) $plugin_xml ["enabled"] == "true") {
				$pluginStartOrder [( string ) $plugin_xml ["name"]] = ( int ) $plugin_xml ["startup_order"];
			}
		}
		//Sort plugins according to startup order
		arsort ( $pluginStartOrder );
		
		//Start all the plugins.
		foreach ( $pluginStartOrder as $plugin_name => $priority ) {
			$plugin_object = $this->getPlugin ( $plugin_name );
			
			if (empty ( $plugin_object )) {
				Logger::getRootLogger ()->error ( "Could not boot {$plugin_name}. Plugin could not be created." );
			} else {
				//When called at boot, run boot method.
				$plugin_object->shutdown ();
			}
		}
		
		Logger::getRootLogger ()->debug ( "Peak Memory usage: " . memory_get_peak_usage ( false ) . " Bytes" );
		Logger::getRootLogger ()->debug ( "Peak  Memory real usage: " . memory_get_peak_usage ( true ) . " Bytes" );
	}
	
	/**
	 * Starts all plugins
	 * 
	 * @param bool $doBootup if true; call each plugin's runAtBoot.
	 */
	public function startAllPlugins($doBootup = false) {
		Logger::getRootLogger ()->info ( "Starting all plugins." );
		
		//Get a list of plugins
		$modules = $this->configuration->getElement ( "modules" );
		foreach ( $modules as $plugin_xml ) {
			if (( string ) $plugin_xml ["enabled"] == "true") {
				$pluginStartOrder [( string ) $plugin_xml ["name"]] = ( int ) $plugin_xml ["startup_order"];
			}
		}
		//Sort plugins according to startup order
		asort ( $pluginStartOrder );
		
		//Start all the plugins.
		foreach ( $pluginStartOrder as $plugin_name => $priority ) {
			$plugin_object = $this->getPlugin ( $plugin_name );
			
			if (empty ( $plugin_object )) {
				Logger::getRootLogger ()->error ( "Could not boot {$plugin_name}. Plugin could not be created." );
			} elseif ($doBootup == true) {
				//When called at boot, run boot method.
				$plugin_object->runAtBoot ();
			}
		}
	}
	
	/**
	 * Fetches a specific plugin
	 * 
	 * If the plugin has not yet been started, it will load it as well.
	 * 
	 * @param string $name Name of the plugin to get
	 * @return Plugin Returns the plugin or null on a failed get
	 */
	public function getPlugin($plugin) {
		Logger::getRootLogger()->debug('Checking for plugin: '. print_r($plugin, true));
		if (is_object($plugin)){
			$plugin = (string) $plugin;
		}
		
		if (file_exists ( self::MODULE_PATH . "/{$plugin}/{$plugin}.php" )) {

			//Check if the plugin is enabled and get the plugin's config
			$modules = $this->configuration->getElement ( "modules" );
			$pluginConfig = $modules->xpath ( "plugin[@name='{$plugin}']" );
			
			//Note: If there is no plugin in the config, it counts as disabled.
			if (( string ) $pluginConfig [0] ['enabled'] == "true") {
				//check to see if it's loaded already.

				if (empty ( $this->plugins [$plugin] )) {
					//Plugin exists, so start it up
					Logger::getRootLogger ()->info ( "Starting plugin: {$plugin}" );
					require_once (self::MODULE_PATH . "/{$plugin}/{$plugin}.php");
					$this->plugins [$plugin] = new $plugin ( $this, $this->configuration, $pluginConfig [0], $this->runtype );
				}
				
				return $this->plugins [$plugin];
			}
			
			//Asked for a plugin that is disabled.
			return null;
		} else {
			Logger::getRootLogger ()->error ( "Could not find and load the plugin: '{$plugin}'." );
			return null;
		}
	}
	
	/**
	 * (re)Loads the configuration file.
	 */
	public function loadConfig() {
		try{
			$this->configuration = new Config ( self::CONFIG_PATH . "/config.xml" );
		}
		catch(Exception $e){
			if($this->runtype == self::RUNTYPE_STARTUP){
				/*
				 * 	Default configuration could not be loaded, return to factory
				 * 	defaults so the system can be inspected and recovered by the user
				 * 	
				 * 	TODO make the error led blink to signal the system is being booted with defaults during boot?
				 */
				//	Copy corrupt config.xml into /cfg/broken-config.xml 
				Functions::mountFilesystem('w');
				Functions::shellCommand('cp '.self::CONFIG_PATH.'/config.xml /cfg/broken-config.xml');
				Functions::mountFilesystem('r');
				
				//	Replace saved config with default config and reload
				Functions::shellCommand('cp '.self::CONFIG_PATH.'/default-config.xml '.self::CONFIG_PATH.'/config.xml');
				$this->configuration = new Config( self::CONFIG_PATH.'/config.xml');
			}
			elseif($this->runtype == self::RUNTYPE_CLI || $this->runtype == self::RUNTYPE_BROWSER){
				/*	Malformed config.xml detected during runtime, re-throw the exception so the front-end can notify
					the end-user */
				throw $e;
			}
		}
	}
	
	/**
	 * Print an uncaught exception
	 */
	private function printException(Exception $e) {
		Logger::getRootLogger ()->error ( "Uncaught exception " . $e->getMessage () );
		if ($this->runtype == self::RUNTYPE_BROWSER) {
			ErrorHandler::addError('error',$e->getMessage());
			ErrorHandler::returnOutput();
		} else {
			echo $e->getMessage ();
		}
	}
}

?>