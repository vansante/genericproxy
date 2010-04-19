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
 * Httpd plugin
 * This plugin manages the lighttpd webserver. 
 *
 * @author Sebastiaan Gibbon
 * @version 0.0
 */

class Httpd implements Plugin {
	
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
	 * Plugin configuration retrieved from $this->config->module
	 * 
	 * @var SimpleXMLElement
	 */
	private $plugin;
	
	/**
	 * 	Webinterface access control list
	 * 
	 * 	@access private
	 * 	@var 	Array
	 */
	private $acl = array('ROOT','USR');
	
	/**
	 * path and filename to the lighttpd config file
	 * 
	 * @var string
	 */
	const CONFIG_PATH = '/var/etc/lighttpd.conf';
	
	/**
	 * Path and filename to the lighttpd PID file
	 * 
	 * @var string
	 */
	const PID_PATH = '/var/run/lighttpd.pid';
	
	/**
	 * Path and filename to store lighttpd's SSL certificate 
	 * 
	 * @var string
	 */
	const CERT_PATH = '/var/etc/cert.pem';
	
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
		$this->plugin = $options;
		
		//get HTTPD config
		$this->data = $this->config->getElement ( 'httpd' );
	}
	
	/**
	 * Is the Plugin a service?
	 * 
	 * @return bool
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
		
		$pid = file_exists ( self::PID_PATH ) ? Functions::shellCommand ( "pgrep -F " . self::PID_PATH ) : 0;
		if ($pid < 1) {
			/* attempt to start lighthttpd */
			Logger::getRootLogger ()->info ( "Starting HTTPD" );
			$res = Functions::shellCommand ( "/usr/local/sbin/lighttpd -f " . self::CONFIG_PATH );
			
			if ($res != 0) {
				Logger::getRootLogger ()->error ( "Could not start HTTPD" );
			}
		} else {
			Logger::getRootLogger ()->info ( 'HTTPD was already running' );
			return true;
		}
	}
	
	/**
	 * Stop the service
	 * 
	 * @return bool false when service failed to stop
	 */
	public function stop() {
		Logger::getRootLogger ()->info ( "Stopping HTTPD" );
		
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
		Logger::getRootLogger ()->info ( "Configuring HTTPD" );
		Functions::mountFilesystem ( 'mount' );
		
		// init.
		$port = 80;
		$cert = "";
		$key = "";
		
		//
		if (strcasecmp ( $this->data->protocol , "https" ) == 0) {
			//Use port number in XML or default port
			$port = ((( int ) $this->data->port ) > 0) ? ( int ) $this->data->port  : 443;
			
			if ($this->data->{'private-key'}  && $this->data->certificate ) {
				$cert = base64_decode ( ( string ) $this->data->certificate  );
				$key = base64_decode ( ( string ) $this->data->{'private-key'}  );
			} else {
				// default certificate
				$cert = <<<EOD
-----BEGIN CERTIFICATE-----
MIIDEzCCAnygAwIBAgIJAJM91W+s6qptMA0GCSqGSIb3DQEBBAUAMGUxCzAJBgNV
BAYTAlVTMQswCQYDVQQIEwJLWTETMBEGA1UEBxMKTG91aXN2aWxsZTEQMA4GA1UE
ChMHcGZTZW5zZTEQMA4GA1UECxMHcGZTZW5zZTEQMA4GA1UEAxMHcGZTZW5zZTAe
Fw0wNjAzMTAyMzQ1MTlaFw0xNjAzMDcyMzQ1MTlaMGUxCzAJBgNVBAYTAlVTMQsw
CQYDVQQIEwJLWTETMBEGA1UEBxMKTG91aXN2aWxsZTEQMA4GA1UEChMHcGZTZW5z
ZTEQMA4GA1UECxMHcGZTZW5zZTEQMA4GA1UEAxMHcGZTZW5zZTCBnzANBgkqhkiG
9w0BAQEFAAOBjQAwgYkCgYEA3lPNTFH6qge/ygaqe/BS4oH59O6KvAesWcRzSu5N
21lyVE5tBbL0zqOSXmlLyReMSbtAMZqt1P8EPYFoOcaEQHIWm2VQF80Z18+8Gh4O
UQGjHq88OeaLqyk3OLpSKzSpXuCFrSN7q9Kez8zp5dQEu7sIW30da3pAbdqYOimA
1VsCAwEAAaOByjCBxzAdBgNVHQ4EFgQUAnx+ggC4SzJ0CK+rhPhJ2ZpyunEwgZcG
A1UdIwSBjzCBjIAUAnx+ggC4SzJ0CK+rhPhJ2ZpyunGhaaRnMGUxCzAJBgNVBAYT
AlVTMQswCQYDVQQIEwJLWTETMBEGA1UEBxMKTG91aXN2aWxsZTEQMA4GA1UEChMH
cGZTZW5zZTEQMA4GA1UECxMHcGZTZW5zZTEQMA4GA1UEAxMHcGZTZW5zZYIJAJM9
1W+s6qptMAwGA1UdEwQFMAMBAf8wDQYJKoZIhvcNAQEEBQADgYEAAviQpdoeabL8
1HSZiD7Yjx82pdLpyQOdXvAu3jEAYz53ckx0zSMrzsQ5r7Vae6AE7Xd7Pj+1Yihs
AJZzOQujnmsuim7qu6YSxzP34xonKwd1C9tZUlyNRNnEmtXOEDupn05bih1ugtLG
kqfPIgDbDLXuPtEAA6QDUypaunI6+1E=
-----END CERTIFICATE-----

EOD;
				// default key
				$key = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
MIICXgIBAAKBgQDeU81MUfqqB7/KBqp78FLigfn07oq8B6xZxHNK7k3bWXJUTm0F
svTOo5JeaUvJF4xJu0Axmq3U/wQ9gWg5xoRAchabZVAXzRnXz7waHg5RAaMerzw5
5ourKTc4ulIrNKle4IWtI3ur0p7PzOnl1AS7uwhbfR1rekBt2pg6KYDVWwIDAQAB
AoGAP7E0VFP8Aq/7os3sE1uS8y8XQ7L+7cUo/AKKoQHKLjfeyAY7t3FALt6vdPqn
anGjkA/j4RIWELoKJfCnwj17703NDCPwB7klcmZvmTx5Om1ZrRyZdQ6RJs0pOOO1
r2wOnZNaNWStXE9Afpw3dj20Gh0V/Ioo5HXn3sHfxZm8dnkCQQDwv8OaUdp2Hl8t
FDfXB1CMvUG1hEAvbQvZK1ODkE7na2/ChKjVPddEI3DvfzG+nLrNuTrAyVWgRLte
r8qX5PQHAkEA7GlKx0S18LdiKo6wy2QeGu6HYkPncaHNFOWX8cTpvGGtQoWYSh0J
tjCt1/mz4/XkvZWuZyTNx2FdkVlNF5nHDQJBAIRWVTZqEjVlwpmsCHnp6mxCyHD4
DrRDNAUfnNuwIr9xPlDlzUzSnpc1CCqOd5C45LKbRGGfCrN7tKd66FmQoFcCQQCy
Kvw3R1pTCvHJnvYwoshphaC0dvaDVeyINiwYAk4hMf/wpVxLZqz+CJvLrB1dzOBR
3O+uPjdzbrakpweJpNQ1AkEA3ZtlgEj9eWsLAJP8aKlwB8VqD+EtG9OJSUMnCDiQ
WFFNj/t3Ze3IVuAyL/yMpiv3JNEnZhIxCta42eDFpIZAKw==
-----END RSA PRIVATE KEY-----

EOD;
			}
		} elseif (strcasecmp ( $this->data->protocol , "http" ) == 0) {
			//Use port number in XML or default port
			$port = ((( int ) $this->data->port ) > 0) ? ( int ) $this->data->port  : 80;
		} else {
			Logger::getRootLogger ()->error ( "Invalid HTTPD config. httpd protocol not supported. Using HTTP on port 80." );
		}
		
		$avail = Functions::getFreeMemory ();
		
		//The more memory available, the more procs and requests will be handled.
		if ($avail < 1) {
			$max_procs = 1;
			$max_requests = 1;
		} else {
			$max_procs = ($avail < 512) ? ceil ( (4 / 512) * $avail ) : 4;
			$max_requests = ($avail < 512) ? ceil ( (16 / 512) * $avail ) : 16;
		}
		Logger::getRootLogger ()->info ( "Found $avail MB free memory. MaxProcs={$max_procs}" );
		
		//Read out the template file.
		$lighty_config = file_get_contents ( PluginFramework::MODULE_PATH . "/Httpd/httpd.conf" );
		
		//Replace config settings.				
		$lighty_config = str_replace ( "{max_procs}", $max_procs, $lighty_config );
		$lighty_config = str_replace ( "{max_requests}", $max_requests, $lighty_config );
		$lighty_config = str_replace ( "{port}", $port, $lighty_config );
		$lighty_config = str_replace ( "{PID_PATH}", self::PID_PATH, $lighty_config );
		$lighty_config = str_replace ( "{DOC_ROOT}", PluginFramework::WWW_PATH, $lighty_config );
		
		//do some strange pfSense stuff to the certificate
		$cert = str_replace ( "\r", "", $cert );
		$key = str_replace ( "\r", "", $key );
		
		$cert = str_replace ( "\n\n", "\n", $cert );
		$key = str_replace ( "\n\n", "\n", $key );
		
		//Set SSL certificate
		if (! empty ( $cert ) && ! empty ( $key )) {
			$fd = fopen ( self::CERT_PATH, "w" );
			if (! $fd) {
				Logger::getRootLogger ()->error ( "Error: Could not write SSL certificate  to " . self::CERT_PATH . "." );
			} else {
				chmod ( self::CERT_PATH, 0600 );
				fwrite ( $fd, $cert );
				fwrite ( $fd, "\n" );
				fwrite ( $fd, $key );
				fclose ( $fd );
				
				$lighty_config .= "## ssl configuration\n";
				$lighty_config .= "ssl.engine = \"enable\"\n";
				$lighty_config .= "ssl.pemfile = \"" . self::CERT_PATH . "\"\n";
			}
		}
		
		//Write away the HTTPD config.
		$fd = fopen ( self::CONFIG_PATH, "w" );
		if (! $fd) {
			Logger::getRootLogger ()->error ( "Error: Could not write HTTPD config to " . self::CONFIG_PATH );
		} else {
			fwrite ( $fd, $lighty_config );
			fclose ( $fd );
		}
		
		//Add firewall rules if they don't exist yet.
		if (empty ( $this->plugin->firewallid )) {
			$this->updateFirewall ( ( string ) $this->data->port );
		}
		
		Functions::mountFilesystem ( 'unmount' );
	}
	
	/**
	 * Starts the plugin
	 */
	public function runAtBoot() {
		Logger::getRootLogger ()->info ( "Init HTTPD" );
		//$this->stop ();
		$this->configure ();
		$this->start ();
	}
	
	/**
	 * Get info for a front-end page
	 */
	public function getPage() {
		if(in_array($_SESSION['group'],$this->acl)){
			if (isset ( $_POST ['page'] )) {
				switch ($_POST ['page']) {
					case 'getconfig' :
						echo '<reply action="ok">';
						echo $this->data->asXML ();
						echo '</reply>';
						break;
					case 'save' :
						$this->saveConfig ();
						break;
					default :
						throw new Exception ('Invalid page request');
				}
			} else {
				throw new Exception ('Invalid page request');
			}
		}
		else{
			throw new Exception('You do not have permission to do this');
		}
	}
	
	/**
	 * Save HTTPD settings 
	 * 
	 * After saving the config, the HTTPD server will be restarted. 
	 */
	private function saveConfig() {
		//Check if the POST values are correct
		if (empty ( $_POST ['services_httpd_protocol'] ) || ($_POST ['services_httpd_protocol'] != "http" ) && ( $_POST ['services_httpd_protocol'] != "https")) {
			ErrorHandler::addError('formerror','services_httpd_protocol');
			throw new Exception('There is invalid form input');
			return false;
		}
		
		if (isset ( $_POST ['services_httpd_port'] ) && (! is_numeric ( $_POST ['services_httpd_port'] ) || $_POST ['services_httpd_port'] < 1 || $_POST ['services_httpd_port'] > 65535)) {
			ErrorHandler::addError('formerror','services_httpd_port');
			throw new Exception('There is invalid form input');
			return false;
		}
		
		//Set config
		$this->data->protocol = $_POST ['services_httpd_protocol'];
		
		//update firewall if the port is changed.
		if (( int ) $this->data->port != ( int ) $_POST ['services_httpd_port'] || empty ( $this->plugin->firewallid )) {
			$this->updateFirewall ( $_POST ['services_httpd_port'] );
		}
		
		//set port
		if (empty ( $_POST ['services_httpd_port'] )) {
			//Use default port number
			$this->data->port = strcasecmp ( $_POST ['services_httpd_protocol'], "http" ) == 0 ? 80 : 443;
		} else {
			$this->data->port = $_POST ['services_httpd_port'];
		}
		
		Logger::getRootLogger ()->info ( "Setting HTTPD to {$_POST ['services_httpd_protocol']}:{$this->data->port}" );
		
		//TODO: Change certificate to a file upload.
		$this->data->certificate = $_POST ['certificate'];
		$this->data->{'private-key'} = $_POST ['private-key'];
		
		
		$_FILES['services_httpd_certificate'];
		//Save config and print the data
		if ($this->config->saveConfig ()) {
			//restart httpd.
			echo '<reply action="ok">';
			echo '<message>The system needs to reboot for settings to take effect.</message>';
			echo $this->data->asXML ();
			echo '</reply>';
			
		//Could restart httpd, but the client will never get the HTTP reply back
		//Logger::getRootLogger ()->info ( "Restarting HTTPD" );
		//$this->stop ();
		//$this->configure ();
		//$this->start ();
		} else {
			throw new Exception ( "Error, could not save configuration file." );
		}
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
		$pid = Functions::shellCommand('pgrep lighttpd');
		Logger::getRootLogger()->debug('HTTPD PID: '.$pid);
		if ($pid > 0) {
			Logger::getRootLogger()->info('Httpd plugin started');
			return 'Started';
		} else {
			Logger::getRootLogger()->info('Httpd plugin stopped');
			return 'Stopped';
		}
	}
	
	/**
	 * Shutsdown the Plugin.
	 * Called at program shutdown. 
	 */
	public function shutdown() {
		$this->stop ();
	}
	
	/**
	 * Update firewall rule to enable traffic on $port
	 * 
	 * @param integer $port
	 */
	private function updateFirewall($port) {
		$firewall = $this->framework->getPlugin ( "Firewall" );
		
		if (isset ( $firewall )) {
			Logger::getRootLogger ()->info ( 'updating firewall for HTTPd.' );
			//remove old rules.
			foreach ( $this->plugin->firewallid as $id ) {
				Logger::getRootLogger ()->info ( "Removing old Httpd firewall rule with ID '{$id}'." );
				if ($firewall->removeRule ( ( string ) $id, 'Httpd' ) != 1) {
					Logger::getRootLogger ()->error ( "Could not remove rule with ID '{$id}'." );
				} else {
					$this->config->deleteElement ( $id );
				}
			}
			
			Logger::getRootLogger ()->info ( 'Adding new firewall rules.' );
			$id = time ();
			//TODO: Check if firewall rule creation is correct.
			$source ['type'] = 'any';
			$source ['port'] = $port;
			$destination ['type'] = 'Lan';
			$destination['port'] = $port;
			
			$firewall->addRule ( 'true', 'pass', 'in', 'disabled', 'lan', 'tcp', null, $source, $destination, 'disabled', 'Generic Proxy HTTP deamon', 'Httpd', 0, $id );
			$firewall->addRule ( 'true', 'pass', 'in', 'disabled', 'ext', 'tcp', null, $source, $destination, 'disabled', 'Generic Proxy HTTP deamon', 'Httpd', 0, $id + 1 );
			
			//Remember added rules
			$this->plugin->addChild ( 'firewallid', $id );
			$this->plugin->addChild ( 'firewallid', $id + 1 );
		}
	}
}
?>