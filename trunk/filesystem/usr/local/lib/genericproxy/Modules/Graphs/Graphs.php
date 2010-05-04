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
 * Plugin to set up MRTG and generate graphs for the front-end
 *
 */
class Graphs implements Plugin{
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
	 * Return true if module launches / manages a service
	 * 
	 * @access public
	 * @return Boolean
	 */
	public function isService(){
		return true;
	}
	
	/**
	 * 	Webinterface access control list
	 * 
	 * 	@access private
	 * 	@var 	Array
	 */
	private $acl = array('ROOT','USR','OP');
	
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
	}
	
	/**
	 * Location of the MRTG config file
	 * 
	 * @var String
	 */
	const CONFIG_FILE = '/var/etc/mrtg.cfg';
	
	/**
	 * Stop MRTG daemon
	 */
	public function stop() {
		$pid = Functions::shellCommand('pgrep mrtg');
		if(is_numeric($pid)){
			Functions::shellCommand('kill '.$pid);
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Start MRTG daemon
	 * 
	 * @return Boolean
	 */
	public function start() {
		if($this->getStatus() == 'Stopped'){
			Functions::shellCommand('/usr/local/bin/mrtg '.self::CONFIG_FILE);
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Commands to run during system shutdown
	 * 
	 * Do nothing during shutdown
	 */
	public function shutdown() {}

	/**
	 * Commands to run during system boot
	 */
	public function runAtBoot() {
		$this->configure();
		$this->start();	
	}

	/**
	 * Get the status of the MRTG daemon
	 */
	public function getStatus() {
		$pid = Functions::shellCommand('pgrep mrtg');
		if(is_numeric($pid)){
			return 'Started';
		}
		else{
			return 'Stopped';
		}
	}

	/**
	 * Passthrough function for front-end functionality
	 */
	public function getPage() {
		
	}

	/**
	 * Get dependencies of this module
	 * 
	 * @return Array
	 */
	public function getDependency() {
		return null;
	}

	/**
	 * Configure the MRTG daemon
	 */
	public function configure() {
		$config = file_get_contents('/usr/local/lib/genericproxy/Modules/Graphs/mrtg.cfg');

		if(!is_dir('/tmp/mrtg')){
			mkdir('/tmp/mrtg');
		}
		
		//	Interfaces to loop over
		$interfaces = array('Lan','Wan','Ext');
		
		foreach($interfaces as $interface){
			$module = $this->framework->getPlugin($interface);
			$interface = strtolower($interface);
			if($module != null){
				$if = $module->getRealInterfaceName();
				$ip = $module->getIpAddress();
				$mac = $module->getMacAddress();
				
				$config .= <<<EOD
		
### Interface 1 >> Descr: '{$if}' | Name: '' | Ip: '{$ip}' | Eth: '{$mac}' ###

Target[{$interface}]: \{$if}:public@localhost:
SetEnv[{$interface}]: MRTG_INT_IP="{$ip}" MRTG_INT_DESCR="{$if}"
MaxBytes[{$interface}]: 12500000
Title[{$interface}]: Traffic for interface {$interface}

EOD;
			}
		}
		
		$fp = fopen(self::CONFIG_FILE,'w');
		fwrite($fp,$config);
		fclose($fp);
	}
}