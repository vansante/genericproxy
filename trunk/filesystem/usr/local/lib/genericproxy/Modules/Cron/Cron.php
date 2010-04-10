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
 * Cron plugin
 * This plugin manages the cron service 
 *
 * @author Sebastiaan Gibbon
 * @version 0.0
 */

class Cron implements Plugin {
	
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
	 * path and filename to the cron config file
	 * 
	 * @var string
	 */
	const CONFIG_PATH = '/etc/crontab';
	
	/**
	 * Path and filename to the lighttpd PID file
	 * 
	 * @var string
	 */
	const PID_PATH = '/var/run/cron.pid';
	
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
		
		//get HTTPD config
		$this->data = $this->config->getElement ( 'cron' );
	}
	
	/**
	 * Is the Plugin a service?
	 * 
	 * @returne bool
	 */
	public function isService() {
		return true;
	}
	
	/**
	 * Start the service
	 * 
	 * @return bool false when service failed to start
	 */
	public function start() {
		Logger::getRootLogger ()->info ( "Starting cron" );
		$pid = file_exists ( self::PID_PATH ) ? Functions::shellCommand ( "pgrep -F " . self::PID_PATH ) : 0;
		if ($pid < 1) {
			Functions::shellCommand ( "/usr/sbin/cron -s" );
		}
	}
	
	/**
	 * Stop the service
	 * 
	 * @return bool false when service failed to stop
	 */
	public function stop() {
		Logger::getRootLogger ()->info ( "Stopping cron" );
		
		$pid = file_exists ( self::PID_PATH ) ? Functions::shellCommand ( "pgrep -F " . self::PID_PATH ) : 0;
		if ($pid > 0) {
			Functions::shellCommand ( "/bin/kill {$pid}" );
			sleep ( 1 );
		}
	}
	
	/**
	 * Write configuration to the system
	 */
	public function configure() {
		Logger::getRootLogger ()->info ( "Configuring cron" );
		$this->writeCrontab ();
	}
	
	/**
	 * Starts the plugin
	 */
	public function runAtBoot() {
		Logger::getRootLogger ()->info ( "Init cron" );
		
		//unlink(self::PID_PATH);
		$this->configure ();
		$this->start ();
	}
	
	/**
	 * Get info for a front-end page
	 * 
	 * CRON has no front-end
	 */
	public function getPage() {}
	
	/**
	 * Gets a list of dependend plugins
	 */
	public function getDependency() {}
	
	/** 
	 * Add a cronjob to cron and the config.
	 * 
	 * When adding a job, it will be added to the config, and the crontab file.
	 * 
	 * @param string $minute
	 * @param string $hour
	 * @param string $mday
	 * @param string $month
	 * @param string $wday
	 * @param string $who
	 * @param string $command
	 * @return SimpleXMLElement Returns the added cron job. Each job has a unique ID called 'id'
	 */
	public function addJob($minute, $hour, $mday, $month, $wday, $who, $command) {
		$job = $this->data->addChild ( "item" );
		$job ['id'] = time ();
		$job->minute = $minute;
		$job->hour = $hour;
		$job->mday = $mday;
		$job->month = $month;
		$job->wday = $wday;
		$job->who = $who;
		$job->command = $command;
		
		Logger::getRootLogger ()->info ( "Adding cron job: {$job->minute} {$job->hour} {$job->mday} {$job->month} {$job->wday} {$job->who} {$job->command}" );
		
		if ($this->config->saveconfig ()) {
			$this->writeCrontab ( $job );
		}
		return $job;
	}
	
	/**
	 * Get a cronjob from an ID. Changes made to the job require to call Cron::configure, unless it's done before Cron plugin is started.
	 * 
	 * @param unknown_type $id
	 * @return SimpleXMLElement|NULL Returns the job's XML element, or null if it was not found.
	 */
	public function getJob($id){
		foreach ($this->data->item as $job){
			if ($job['id'] == $id){
				return $job;
			}
		}
		return null;
	}
	
	/**
	 * Write to the crontab file. 
	 * 
	 * If a job is given it will be appended to crontab. If no arguments are given all jobs are writen to crontab.
	 *  
	 * @param null|SimpleXMLElement $job Job to be added
	 */
	private function writeCrontab($job = null) {
		$fd = fopen ( self::CONFIG_PATH, (empty ( $job ) ? "w" : "a") );
		if (! $fd) {
			Logger::getRootLogger ()->error ( "Error: Could not write cron conifg to " . self::CONFIG_PATH );
		} else {
			if (empty ( $job )) {
				//Write new crontab file
				fwrite ( $fd, "#This is a generated file, created by GenericProxy. Do not change.\n" );
				
				//Add all cron jobs in the config to crontab file
				foreach ( $this->data as $job ) {
					if ($job->getName () == 'item') { //Make sure it's the correct tag
						fwrite ( $fd, "#Job ID: {$job['id']}\n{$job->minute}\t{$job->hour}\t{$job->mday}\t{$job->month}\t{$job->wday}\t{$job->who}\t{$job->command}\n" );
					}
				}
			} else {
				//add $job to crontab
				fwrite ( $fd, "#ID: {$job['id']}\n{$job->minute}\t{$job->hour}\t{$job->mday}\t{$job->month}\t{$job->wday}\t{$job->who}\t{$job->command}\n" );
			}
			fclose ( $fd );
		}
	}
	
	/**
	 * Starts the plugin
	 * 
	 * @return string Status of the service/plugin
	 */
	public function getStatus() {
		$pid = file_exists ( self::PID_PATH ) ? Functions::shellCommand ( "pgrep -F " . self::PID_PATH ) : 0;
		if ($pid > 0) {
			return 'Started';
		} else {
			return 'Stopped';
		}
	}
	
	/**
	 * Shutsdown the Plugin.
	 * 
	 * Called at program shutdown. 
	 */
	public function shutdown(){
		$this->stop();
	}
}