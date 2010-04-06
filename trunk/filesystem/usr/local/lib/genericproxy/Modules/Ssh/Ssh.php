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
 * Plugin to setup the SSH Daemon
 *
 * @version 1.0
 */
class Ssh implements Plugin {
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
	 * 	@access = private;
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
		$this->logger = Logger::getRootLogger();

		//      Get firewall XML configuration
		$this->data = $this->config->getElement ( 'ssh' );
		$this->logger->debug("Config XML: ".$this->data->asXML());
	}
	
	/**
	 * 	Configure SSH Daemon
	 * 
	 * 	@access public
	 * 	@return void
	 */
	public function configure() {
		$this->logger->info('Configuring SSHD');
		Functions::mountFilesystem('mount');
		
		/*		SSH configuration */
		$sshconf = "# This file is automatically generated at startup\n
		PermitRootLogin yes\n
		Compression yes\n
		ClientAliveInterval 30\n
		UseDNS no\n
		X11Forwarding no\n";
		if ((string)$this->data->login == "keyonly") {
			$sshconf .= "# Login via Key only\n
			PasswordAuthentication no\n
			ChallengeResponseAuthentication no\n
			PubkeyAuthentication yes\n";
		} else {
			$sshconf .= "# Login via Key and Password\n
			PasswordAuthentication yes\n
			ChallengeResponseAuthentication yes\n
			PubkeyAuthentication yes\n";
		}
		$sshconf .= "# override default of no subsystems\n
		Subsystem       sftp    /usr/libexec/sftp-server\n";
		
		/* Only allow protocol 2, because we say so */
		$sshconf .= "Protocol 2\n";
		
		/* Run the server on another port if we have one defined */
		$sshconf .= "Port ".((string)$this->data->port)."\n";
		
		/* Write the config file */
		$fd = fopen ( "/etc/ssh/sshd_config", "w" );
		fwrite ( $fd, $sshconf );
		fclose ( $fd );
		
		//	Authorized keys
		if((string)$this->data->authorizedKey != ''){
			if (!is_dir("/root/.ssh")) {
				mkdir('/root/.ssh', 0700);
			}
			$authorizedkeys  = "# This file is automatically generated at startup\n";
			$authorizedkeys .= base64_decode($this->data->authorizedKey);
			$fd = fopen("/root/.ssh/authorized_keys", "w");
			fwrite($fd, $authorizedkeys);
			pclose($fd);
			chmod("/root/.ssh/authorized_keys",0644);
		}
		else{
			if(file_exists("/root/.ssh/authorized_keys")) {
				unlink("/root/.ssh/authorized_keys");
			}
		}
		
		//	Check if the host key exists
		$this->generateHostKey();
		
		Functions::mountFilesystem('unmount');
		
		$firewall = $this->framework->getPlugin('Firewall');
		
		$source = null;
		$source['type'] = 'Lansubnet';
		$source['port'] = (string)$this->data->port;
		
		$destination = null;
		$destination['type'] = 'any';
		$destination['port'] = (string)$this->data->port;
	}
	
	/**
	 * 	Generates SSH host key
	 * 
	 * 	Generates keys if they don't exist already, typically only happens at first boot.
	 * 
	 * 	@access private
	 * 	@return void
	 */
	private function generateHostKey(){
		if (!file_exists("/etc/ssh/ssh_host_key") || file_exists("/etc/keys_generating")) {
			/* remove previous keys and regen later */
			$this->logger->info('Generating SSH Host key, could take a while');
			Functions::shellCommand("rm /etc/ssh/ssh_host_*");
			touch("/etc/keys_generating");
			touch("/tmp/keys_generating");
			
			Functions::shellCommand("/usr/bin/nice -n20 /usr/bin/ssh-keygen -t rsa1 -N '' -f /etc/ssh/ssh_host_key");
			Functions::shellCommand("/usr/bin/nice -n20 /usr/bin/ssh-keygen -t rsa -N '' -f /etc/ssh/ssh_host_rsa_key");
			Functions::shellCommand("/usr/bin/nice -n20 /usr/bin/ssh-keygen -t dsa -N '' -f /etc/ssh/ssh_host_dsa_key");
			unlink("/etc/keys_generating");
			unlink("/tmp/keys_generating");
			
			$this->logger->info('SSH Host key generation complete');
			
			Functions::mountFilesystem('mount');
			Functions::shellCommand('mkdir /cfg/ssh');
			Functions::shellCommand('cp -r /etc/ssh/* /cfg/ssh/');
			Functions::mountFilesystem('unmount');
		}
	}
	
	/**
	 * 	Get XML to load in a page
	 * 
	 * @access public
	 * @throws Exception
	 */
	public function getPage(){
		if($_POST['page'] == 'getconfig'){
			echo '<reply action="ok">';
			echo $this->data->asXML();
			echo '</reply>';
		}
		elseif($_POST['page'] == 'save'){
			$this->saveSettings();
		}
		else{
			throw new Exception('Invalid page request');
		}
	}
	
	/**
	 * 	Save the edited SSH settings
	 */
	public function saveSettings(){
		if($_POST['services_ssh_enabled']){
			//		Start SSHD if it was disabled before
			if((string)$this->data->enable == 'false'){
				$this->configure();
				$this->start();
			}
			$this->data['enable'] = 'true';
		}
		else{
			$this->data['enable'] = 'false';
			$this->stop();
		}
		$this->config->saveConfig();
		echo '<reply action="ok">';
		echo $this->data->asXML();
		echo '</reply>';
	}
	
	/**
	 * 	Return dependencies
	 * 
	 * 	@access public
	 * 	@return null
	 */
	public function getDependency() {
		return null;
	}
	
	/**
	 * 	Return the status of the SSH daemon
	 * 
	 * 	@access public
	 * 	@return String
	 */
	public function getStatus() {
		$sshd_pid = Functions::shellCommand("ps ax | egrep '/usr/sbin/[s]shd' | awk '{print $1}'");
		if($sshd_pid <> "") {
			return 'Started';
		}
		else{
			return 'Stopped';
		}
	}
	
	/**
	 * 	Start the SSHD server
	 * 
	 * 	@access public
	 * 	@return Boolean
	 */
	public function start() {
		$sshd_pid = Functions::shellCommand("ps ax | egrep '/usr/sbin/[s]shd' | awk '{print $1}'");
		if($sshd_pid == "") {
			$this->logger->info('Starting SSH Daemon');
			$status = Functions::shellCommand("/usr/sbin/sshd");
			if($status != 0) {
				$this->logger->error('SSHD failed to start');
				return false;
			}
			return true;
		}
		else{
			$this->logger->info('SSHD was already running');
			return true;
		}
		
	}
	
	/**
	 * 	Initialize Plugin
	 * 
	 * 	@access public
	 * 	@return void
	 */
	public function runAtBoot() {
		if((string)$this->data['enable'] == 'true'){
			$this->logger->info('Init SSHD');
			$this->configure();
			$this->start();
		}
	}
	
	/**
	 * 	Stop SSHD server
	 * 
	 * 	@access public
	 * 	@return Boolean
	 */
	public function stop() {
		$sshd_pid = Functions::shellCommand("ps ax | egrep '/usr/sbin/[s]shd' | awk '{print $1}'");
		if($sshd_pid <> "") {
			$this->logger->info('Stopping SSHD');
			Functions::shellCommand("kill $sshd_pid");
			return true;
		}
		else{
			$this->logger->info('SSHD was terminated without it running');
			return false;
		}
	}

	/**
	 * Shutsdown the Plugin.
	 * Called at program shutdown. 
	 */
	public function shutdown(){
		$this->stop();
	}
}
?>