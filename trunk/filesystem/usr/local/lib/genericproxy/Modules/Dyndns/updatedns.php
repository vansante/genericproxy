<?php
	/*
	 * PHP.updateDNS (pfSense version) (Modified for GenericProxy)
	 *
	 * +====================================================+
	 *  Services Supported:
	 *    - DynDns (dyndns.org) [dynamic, static, custom]
	 *    - DHSDns (dhs.org)
	 *    - No-IP (no-ip.com)
	 *    - EasyDNS (easydns.com)
	 *    - DHS (www.dhs.org)
	 *    - HN (hn.org) -- incomplete checking!
	 *    - DynS (dyns.org)
	 *    - ZoneEdit (zoneedit.com)
	 *    - FreeDNS (freedns.afraid.org)
	 *    - Loopia (loopia.se)
	 *    - StaticCling (staticcling.org)
	 * +----------------------------------------------------+
	 *  Requirements:
	 *    - PHP version 4.0.2 or higher with CURL Library
	 * +----------------------------------------------------+
	 *  Public Functions
	 *    - updatedns()
	 *
	 *  Private Functions
	 *    - _update()
	 *    - _checkStatus()
	 *    - _error()
	 *    - _detectChange()
	 *    - _debug()
	 * +----------------------------------------------------+
	 *  DynDNS Dynamic - Last Tested: 12 July 2005
	 *  DynDNS Static  - Last Tested: NEVER
	 *  DynDNS Custom  - Last Tested: NEVER
	 *  No-IP          - Last Tested: 12 July 2005
	 *  HN.org         - Last Tested: 12 July 2005
	 *  EasyDNS        - Last Tested: NEVER
	 *  DHS            - Last Tested: 12 July 2005
	 *  ZoneEdit       - Last Tested: NEVER
	 *  Dyns           - Last Tested: NEVER
	 *  ODS            - Last Tested: 02 August 2005
	 *  FreeDNS        - Last Tested: NEVER
	 *  Loopia         - Last Tested: NEVER
	 *  StaticCling    - Last Tested: 27 April 2006
	 * +====================================================+
	 *
	 * @author 	E.Kristensen
	 * @link    	http://www.idylldesigns.com/projects/phpdns/
	 * @version 	0.8
	 * @updated	13 October 05 at 21:02:42 GMT
	 *
	 */

	class updatedns {
		var $_cacheFile = '/var/etc/dyndns.cache';
		var $_debugFile = '/var/etc/dyndns.debug';
		var $_UserAgent = 'User-Agent: phpDynDNS/0.7';
		var $_errorVerbosity = 0;
		var $_dnsService;
		var $_dnsUser;
		var $_dnsPass;
		var $_dnsHost;
		var $_dnsIP;
		var $_dnsWildcard;
		var $_dnsMX;
		var $_dnsBackMX;
		var $_dnsWanip;
		var $_dnsServer;
		var $_dnsPort;
		var $_dnsUpdateURL;
		var $status;
		var $_debugID;
		var $_wan_ip;
		
		/* 
		 * Public Constructor Function (added 12 July 05) [beta]
		 *   - Gets the dice rolling for the update. 
		 */
		function updatedns ($wan_ip, $dnsService = '', $dnsHost = '', $dnsUser = '', $dnsPass = '',
				    $dnsWildcard = 'OFF', $dnsMX = '', $dnsBackMX = '', $dnsWanip = '',
				    $dnsServer = '', $dnsPort = '', $dnsUpdateURL = '') {
			
			Logger::getRootLogger ()->info("DynDns: updatedns() starting");
			
			if (!$dnsService) $this->_error(2);
			if (!($dnsService == 'freedns')) {

				/* all services except freedns use these */

				if (!$dnsUser) $this->_error(3);
				if (!$dnsPass) $this->_error(4);
				if (!$dnsHost) $this->_error(5);
			} else {

				/* freedns needs this */

				if (!$dnsHost) $this->_error(5);
			}
			
			$this->_dnsService = strtolower($dnsService);
			$this->_dnsUser = $dnsUser;
			$this->_dnsPass = $dnsPass;
			$this->_dnsHost = $dnsHost;
			$this->_dnsWanip = $dnsWanip;
			$this->_dnsServer = $dnsServer;
			$this->_dnsPort = $dnsPort;
			$this->_dnsWildcard = $dnsWildcard;
			$this->_dnsMX = $dnsMX;
			$this->_wan_ip = $wan_ip;
				
			$this->_dnsIP = $wan_ip;
			$this->_debugID = rand(1000000, 9999999);
			
			if ($this->_detectChange() == FALSE) {
				$this->_error(10);
			} else {
				if ($this->_dnsService == 'dyndns' ||
					$this->_dnsService == 'dyndns-static' ||
					$this->_dnsService == 'dyndns-custom' ||
					$this->_dnsService == 'dhs' ||
					$this->_dnsService == 'noip' ||
					$this->_dnsService == 'easydns' ||
					$this->_dnsService == 'hn' ||
					$this->_dnsService == 'zoneedit' ||
					$this->_dnsService == 'dyns' ||
					$this->_dnsService == 'ods' ||
					$this->_dnsService == 'freedns' ||
					$this->_dnsService == 'loopia' ||
					$this->_dnsService == 'staticcling')
				{
					$this->_update();
				} else {
					$this->_error(6);
				}
			}					
		}
			
		/*
		 * Private Function (added 12 July 05) [beta]
		 *   Send Update To Selected Service.
		 */
		function _update() {
		
			Logger::getRootLogger ()->info("DynDns: DynDns _update() starting.");
		
			if ($this->_dnsService != 'ods') {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_USERAGENT, $this->_UserAgent);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			}

			switch ($this->_dnsService) {
				case 'dyndns':
					$needsIP = FALSE;
					Logger::getRootLogger ()->info("DynDns: DynDns _update() starting. Dynamic");
					if (isset($this->_dnsWildcard) && $this->_dnsWildcard != "OFF") $this->_dnsWildcard = "ON";
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
					curl_setopt($ch, CURLOPT_USERPWD, $this->_dnsUser.':'.$this->_dnsPass);
					$server = "https://members.dyndns.org/nic/update";
					$port = "";
					if($this->_dnsServer)
						$server = $this->_dnsServer;
					if($this->_dnsPort)
						$port = ":" . $this->_dnsPort;
					curl_setopt($ch, CURLOPT_URL, $server .$port . '?system=dyndns&hostname=' . $this->_dnsHost . '&myip=' . $this->_dnsIP.'&wildcard='.$this->_dnsWildcard . '&mx=' . $this->_dnsMX . '&backmx=NO');
					$data = curl_exec($ch);
					if (@curl_error($ch)) Logger::getRootLogger ()->info("Curl error occurred: " . curl_error($ch));
					curl_close($ch);
					$this->_checkStatus($data);
					break;
				case 'dyndns-static':
					$needsIP = FALSE;
					if (isset($this->_dnsWildcard) && $this->_dnsWildcard != "OFF") $this->_dnsWildcard = "ON";
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
					curl_setopt($ch, CURLOPT_USERPWD, $this->_dnsUser.':'.$this->_dnsPass);
					$server = "https://members.dyndns.org/nic/update";
					$port = "";
					if($this->_dnsServer)
						$server = $this->_dnsServer;
					if($this->_dnsPort)
						$port = ":" . $this->_dnsPort;
					curl_setopt($ch, CURLOPT_URL, $server.$port.'?system=statdns&hostname='.$this->_dnsHost.'&myip='.$this->_dnsIP.'&wildcard='.$this->_dnsWildcard.'&mx='.$this->_dnsMX.'&backmx=NO');
					$data = curl_exec($ch);
					if (@curl_error($ch)) Logger::getRootLogger ()->info("Curl error occurred: " . curl_error($ch));
					curl_close($ch);
					$this->_checkStatus($data);
					break;
				case 'dyndns-custom':
					$needsIP = FALSE;
					if (isset($this->_dnsWildcard) && $this->_dnsWildcard != "OFF") $this->_dnsWildcard = "ON";
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
					curl_setopt($ch, CURLOPT_USERPWD, $this->_dnsUser.':'.$this->_dnsPass);
					$server = "https://members.dyndns.org/nic/update";
					$port = "";
					if($this->_dnsServer)
						$server = $this->_dnsServer;
					if($this->_dnsPort)
						$port = ":" . $this->_dnsPort;					
					curl_setopt($ch, CURLOPT_URL, $server.$port.'?system=custom&hostname='.$this->_dnsHost.'&myip='.$this->_dnsIP.'&wildcard='.$this->_dnsWildcard.'&mx='.$this->_dnsMX.'&backmx=NO');
					$data = curl_exec($ch);
					if (@curl_error($ch)) Logger::getRootLogger ()->info("Curl error occurred: " . curl_error($ch));
					curl_close($ch);
					$this->_checkStatus($data);
					break;
				case 'dhs':
					$needsIP = TRUE;
					$post_data['hostscmd'] = 'edit';
					$post_data['hostscmdstage'] = '2';
					$post_data['type'] = '4';
					$post_data['updatetype'] = 'Online';
					$post_data['mx'] = $this->_dnsMX;
					$post_data['mx2'] = '';
					$post_data['txt'] = '';
					$post_data['offline_url'] = '';
					$post_data['cloak'] = 'Y';
					$post_data['cloak_title'] = '';
					$post_data['ip'] = $this->_dnsIP;
					$post_data['domain'] = 'dyn.dhs.org';
					$post_data['hostname'] = $this->_dnsHost;
					$post_data['submit'] = 'Update';
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
					$server = "https://members.dhs.org/nic/hosts";
					$port = "";
					if($this->_dnsServer)
						$server = $this->_dnsServer;
					if($this->_dnsPort)
						$port = ":" . $this->_dnsPort;					
					curl_setopt($ch, CURLOPT_URL, '{$server}{$port}');
					curl_setopt($ch, CURLOPT_USERPWD, $this->_dnsUser.':'.$this->_dnsPass);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
					$data = curl_exec($ch);
					if (@curl_error($ch)) Logger::getRootLogger ()->info("Curl error occurred: " . curl_error($ch));
					curl_close($ch);
					$this->_checkStatus($data);
					break;
				case 'noip':
					$needsIP = TRUE;
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
					$server = "http://dynupdate.no-ip.com/ducupdate.php";
					$port = "";
					if($this->_dnsServer)
						$server = $this->_dnsServer;
					if($this->_dnsPort)
						$port = ":" . $this->_dnsPort;
					curl_setopt($ch, CURLOPT_URL, $server . $port . '?username=' . $this->_dnsUser . '&pass=' . $this->_dnsPass . '&hostname=' . $this->_dnsHost.'&ip=' . $this->_dnsIP);
					$data = curl_exec($ch);
					if (@curl_error($ch)) Logger::getRootLogger ()->info("Curl error occurred: " . curl_error($ch));
					curl_close($ch);
					$this->_checkStatus($data);
					break;
				case 'easydns':
					$needsIP = TRUE;
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
					curl_setopt($ch, CURLOPT_USERPWD, $this->_dnsUser.':'.$this->_dnsPass);
					$server = "http://members.easydns.com/dyn/dyndns.php";
					$port = "";
					if($this->_dnsServer)
						$server = $this->_dnsServer;
					if($this->_dnsPort)
						$port = ":" . $this->_dnsPort;
					curl_setopt($ch, CURLOPT_URL, $server . $port . '?hostname=' . $this->_dnsHost . '&myip=' . $this->_dnsIP . '&wildcard=' . $this->_dnsWildcard . '&mx=' . $this->_dnsMX . '&backmx=' . $this->_dnsBackMX);
					$data = curl_exec($ch);
					if (@curl_error($ch)) Logger::getRootLogger ()->info("Curl error occurred: " . curl_error($ch));
					curl_close($ch);
					$this->_checkStatus($data);
					break;
				case 'hn':
					$needsIP = TRUE;
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
					curl_setopt($ch, CURLOPT_USERPWD, $this->_dnsUser.':'.$this->_dnsPass);
					$server = "http://dup.hn.org/vanity/update";
					$port = "";
					if($this->_dnsServer)
						$server = $this->_dnsServer;
					if($this->_dnsPort)
						$port = ":" . $this->_dnsPort;
					curl_setopt($ch, CURLOPT_URL, $server . $port . '?ver=1&IP=' . $this->_dnsIP);
					$data = curl_exec($ch);
					if (@curl_error($ch)) Logger::getRootLogger ()->info("Curl error occurred: " . curl_error($ch));
					curl_close($ch);
					$this->_checkStatus($data);
					break;
				case 'zoneedit':
					$needsIP = FALSE;
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
					curl_setopt($ch, CURLOPT_USERPWD, $this->_dnsUser.':'.$this->_dnsPass);

					$server = "https://dynamic.zoneedit.com/auth/dynamic.html";
					$port = "";
					if($this->_dnsServer)
						$server = $this->_dnsServer;
					if($this->_dnsPort)
						$port = ":" . $this->_dnsPort;
					curl_setopt($ch, CURLOPT_URL, "{$server}{$port}?host=" .$this->_dnsHost);

					$data = curl_exec($ch);
					if (@curl_error($ch)) Logger::getRootLogger ()->info("Curl error occurred: " . curl_error($ch));
					curl_close($ch);
					$this->_checkStatus($data);
					break;
				case 'dyns':
					$needsIP = FALSE;
					$server = "http://www.dyns.cx/postscript011.php";
					$port = "";
					if($this->_dnsServer)
						$server = $this->_dnsServer;
					if($this->_dnsPort)
						$port = ":" . $this->_dnsPort;					
					curl_setopt($ch, CURLOPT_URL, $server . $port . '?username=' . $this->_dnsUser . '&password=' . $this->_dnsPass . '&host=' . $this->_dnsHost);
					$data = curl_exec($ch);
					if (@curl_error($ch)) Logger::getRootLogger ()->info("Curl error occurred: " . curl_error($ch));
					curl_close($ch);
					$this->_checkStatus($data);
					break;
				case 'ods':
					$needsIP = FALSE;
					$misc_errno = 0;
					$misc_error = "";
					$server = "ods.org";
					$port = "";
					if($this->_dnsServer)
						$server = $this->_dnsServer;
					if($this->_dnsPort)
						$port = ":" . $this->_dnsPort;						
					$this->con['socket'] = fsockopen("{$server}{$port}", "7070", $misc_errno, $misc_error, 30);
					/* Check that we have connected */
					if (!$this->con['socket']) {
						print "error! could not connect.";
						break;
					}
					/* Here is the loop. Read the incoming data (from the socket connection) */
					while (!feof($this->con['socket'])) {
						$this->con['buffer']['all'] = trim(fgets($this->con['socket'], 4096));
						$code = substr($this->con['buffer']['all'], 0, 3);
						sleep(1);
						switch($code) {
							case 100:
								fputs($this->con['socket'], "LOGIN ".$this->_dnsUser." ".$this->_dnsPass."\n");
								break;
							case 225:
								fputs($this->con['socket'], "DELRR ".$this->_dnsHost." A\n");
								break;
							case 901:
								fputs($this->con['socket'], "ADDRR ".$this->_dnsHost." A ".$this->_dnsIP."\n");
								break;
							case 795:
								fputs($this->con['socket'], "QUIT\n");
								break;
						}
					}
					$this->_checkStatus($code);
					break;
				case 'freedns':
					$needIP = FALSE;
					curl_setopt($ch, CURLOPT_URL, 'http://freedns.afraid.org/dynamic/update.php?'.$this->_dnsHost);
					$data = curl_exec($ch);
					if (@curl_error($ch)) Logger::getRootLogger ()->info("Curl error occurred: " . curl_error($ch));
					curl_close($ch);
					$this->_checkStatus($data);
					break;
				case 'loopia':
					$needsIP = TRUE;
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
					curl_setopt($ch, CURLOPT_USERPWD, $this->_dnsUser.':'.$this->_dnsPass);
					curl_setopt($ch, CURLOPT_URL, 'https://dns.loopia.se/XDynDNSServer/XDynDNS.php?hostname='.$this->_dnsHost.'&myip='.$this->_dnsIP);
					$data = curl_exec($ch);
					if (@curl_error($ch)) Logger::getRootLogger ()->info("Curl error occurred: " . curl_error($ch));
					curl_close($ch);
					$this->_checkStatus($data);
					break;
                                case 'staticcling':
                                        $needsIP = FALSE;
                                        curl_setopt($ch, CURLOPT_URL, 'http://www.staticcling.org/update.html?login='.$this->_dnsUser.'&pass='.$this->_dnsPass);
                                        $data = curl_exec($ch);
                                        if (@curl_error($ch)) Logger::getRootLogger ()->info("Curl error occured: " . curl_error($ch));
                                        curl_close($ch);
                                        $this->_checkStatus($data);
                                        break;
				default:
					break;
			}
		}

		/*
		 * Private Function (added 12 July 2005) [beta]
		 *   Retrieve Update Status
		 */
		function _checkStatus($data) {
			Logger::getRootLogger ()->info("DynDns: DynDns _checkStatus() starting.");
			Logger::getRootLogger ()->info("DynDns: Current Service: {$this->_dnsService}");
			$successful_update = false;
			switch ($this->_dnsService) {
				case 'dyndns':
					if (preg_match('/notfqdn/i', $data)) {
						$status = "phpDynDNS: (Error) Not A FQDN!";
					} else if (preg_match('/nochg/i', $data)) {
						$status = "phpDynDNS: (Success) No Change In IP Address";
						$successful_update = true;
					} else if (preg_match('/good/i', $data)) {
						$status = "phpDynDNS: (Success) IP Address Changed Successfully! (".$this->_dnsIP.")";
						$successful_update = true;
					} else if (preg_match('/noauth/i', $data)) {
						$status = "phpDynDNS: (Error) User Authorization Failed";
					} else {
						$status = "phpDynDNS: (Unknown Response)";
						Logger::getRootLogger ()->info("phpDynDNS: PAYLOAD: {$data}");
						$this->_debug($data);
					}
					break;
				case 'dyndns-static':
					if (preg_match('/notfqdn/i', $data)) {
						$status = "phpDynDNS: (Error) Not A FQDN!";
					} else if (preg_match('/nochg/i', $data)) {
						$status = "phpDynDNS: (Success) No Change In IP Address";
						$successful_update = true;
					} else if (preg_match('/good/i', $data)) {
						$status = "phpDynDNS: (Success) IP Address Changed Successfully!";
						$successful_update = true;
					} else if (preg_match('/noauth/i', $data)) {
						$status = "phpDynDNS: (Error) User Authorization Failed";
					} else {
						$status = "phpDynDNS: (Unknown Response)";
						Logger::getRootLogger ()->info("phpDynDNS: PAYLOAD: {$data}");
						$this->_debug($data);
					}
					break;
				case 'dyndns-custom':
					if (preg_match('/notfqdn/i', $data)) {
						$status = "phpDynDNS: (Error) Not A FQDN!";
					} else if (preg_match('/nochg/i', $data)) {
						$status = "phpDynDNS: (Success) No Change In IP Address";
						$successful_update = true;
					} else if (preg_match('/good/i', $data)) {
						$status = "phpDynDNS: (Success) IP Address Changed Successfully!";
						$successful_update = true;
					} else if (preg_match('/noauth/i', $data)) {
						$status = "phpDynDNS: (Error) User Authorization Failed";
					} else {
						$status = "phpDynDNS: (Unknown Response)";
						Logger::getRootLogger ()->info("phpDynDNS: PAYLOAD: {$data}");
						$this->_debug($data);
					}
					break;
				case 'dhs':
					break;
				case 'noip':
					list($ip,$code) = split(":",$data);
					switch ($code) {
						case 0:
							$status = "phpDynDNS: (Success) IP address is current, no update performed.";
							$successful_update = true;
							break;
						case 1:
							$status = "phpDynDNS: (Success) DNS hostname update successful.";
							$successful_update = true;
							break;
						case 2:
							$status = "phpDynDNS: (Error) Hostname supplied does not exist.";
							break;
						case 3:
							$status = "phpDynDNS: (Error) Invalid Username.";
							break;
						case 4:
							$status = "phpDynDNS: (Error) Invalid Password.";
							break;
						case 5:
							$status = "phpDynDNS: (Error) To many updates sent.";
							break;
						case 6:
							$status = "phpDynDNS: (Error) Account disabled due to violation of No-IP terms of service.";
							break;
						case 7:
							$status = "phpDynDNS: (Error) Invalid IP. IP Address submitted is improperly formatted or is a private IP address or is on a blacklist.";
							break;
						case 8:
							$status = "phpDynDNS: (Error) Disabled / Locked Hostname.";
							break;
						case 9:
							$status = "phpDynDNS: (Error) Host updated is configured as a web redirect and no update was performed.";
							break;
						case 10:
							$status = "phpDynDNS: (Error) Group supplied does not exist.";
							break;
						case 11:
							$status = "phpDynDNS: (Success) DNS group update is successful.";
							$successful_update = true;
							break;
						case 12:
							$status = "phpDynDNS: (Success) DNS group is current, no update performed.";
							$successful_update = true;
							break;
						case 13:
							$status = "phpDynDNS: (Error) Update client support not available for supplied hostname or group.";
							break;
						case 14:
							$status = "phpDynDNS: (Error) Hostname supplied does not have offline settings configured.";
							break;
						case 99:
							$status = "phpDynDNS: (Error) Client disabled. Client should exit and not perform any more updates without user intervention.";
							break;
						case 100:
							$status = "phpDynDNS: (Error) Client disabled. Client should exit and not perform any more updates without user intervention.";
							break;
						default:
							$status = "phpDynDNS: (Unknown Response)";
							$this->_debug("Unknown Response: ".$data);
							break;
					}
					break;
				case 'easydns':
					if (preg_match('/NOACCESS/i', $data)) {
						$status = "phpDynDNS: (Error) Authentication Failed: Username and/or Password was Incorrect.";
					} else if (preg_match('/NOSERVICE/i', $data)) {
						$status = "phpDynDNS: (Error) No Service: Dynamic DNS Service has been disabled for this domain.";
					} else if (preg_match('/ILLEGAL INPUT/i', $data)) {
						$status = "phpDynDNS: (Error) Illegal Input: Self-Explantory";
					} else if (preg_match('/TOOSOON/i', $data)) {
						$status = "phpDynDNS: (Error) Too Soon: Not Enough Time Has Elapsed Since Last Update";
					} else if (preg_match('/NOERROR/i', $data)) {
						$status = "phpDynDNS: (Success) IP Updated Successfully!";
						$successful_update = true;
					} else {
						$status = "phpDynDNS: (Unknown Response)";
						Logger::getRootLogger ()->info("phpDynDNS: PAYLOAD: {$data}");
						$this->_debug($data);
					}
					break;
				case 'hn':
					/* FIXME: add checks */
					break;
				case 'zoneedit':
					if (preg_match('/799/i', $data)) {
						$status = "phpDynDNS: (Error 799) Update Failed!";				
					} else if (preg_match('/700/i', $data)) {
						$status = "phpDynDNS: (Error 700) Update Failed!";
					} else if (preg_match('/200/i', $data)) {
						$status = "phpDynDNS: (Success) IP Address Updated Successfully!";
						$successful_update = true;
					} else if (preg_match('/201/i', $data)) {
						$status = "phpDynDNS: (Success) IP Address Updated Successfully!";
						$successful_update = true;						
					} else {
						$status = "phpDynDNS: (Unknown Response)";
						Logger::getRootLogger ()->info("phpDynDNS: PAYLOAD: {$data}");
						$this->_debug($data);
					}
					break;
				case 'dyns':
					if (preg_match("/400/i", $data)) {
						$status = "phpDynDNS: (Error) Bad Request - The URL was malformed. Required parameters were not provided.";
					} else if (preg_match('/402/i', $data)) {
						$status = "phpDynDNS: (Error) Update Too Soon - You have tried updating to quickly since last change.";
					} else if (preg_match('/403/i', $data)) {
						$status = "phpDynDNS: (Error) Database Error - There was a server-sided database error.";
					} else if (preg_match('/405/i', $data)) {
						$status = "phpDynDNS: (Error) Hostname Error - The hostname (".$this->_dnsHost.") doesn't belong to you.";
					} else if (preg_match('/200/i', $data)) {
						$status = "phpDynDNS: (Success) IP Address Updated Successfully!";
						$successful_update = true;
					} else {
						$status = "phpDynDNS: (Unknown Response)";
						Logger::getRootLogger ()->info("phpDynDNS: PAYLOAD: {$data}");
						$this->_debug($data);
					}
					break;
				case 'ods':
					if (preg_match("/299/i", $data)) {
						$status = "phpDynDNS: (Success) IP Address Updated Successfully!";
						$successful_update = true;
					} else {
						$status = "phpDynDNS: (Unknown Response)";
						Logger::getRootLogger ()->info("phpDynDNS: PAYLOAD: {$data}");
						$this->_debug($data);
					}
					break;
				case 'freedns':
					if (preg_match("/has not changed./i", $data)) {
						$status = "phpDynDNS: (Success) No Change In IP Address";
						$successful_update = true;
					} else if (preg_match("/Updated/i", $data)) {
						$status = "phpDynDNS: (Success) IP Address Changed Successfully!";
						$successful_update = true;
					} else {
						$status = "phpDynDNS: (Unknown Response)";
						Logger::getRootLogger ()->info("phpDynDNS: PAYLOAD: {$data}");
						$this->_debug($data);
					} 
					break;
				case 'loopia':
					if (preg_match("/nochg/i", $data)) {
						$status = "phpDynDNS: (Success) No Change In IP Address";
						$successful_update = true;
					} else if (preg_match("/good/i", $data)) {
						$status = "phpDynDNS: (Success) IP Address Changed Successfully!";
						$successful_update = true;
					} else if (preg_match('/badauth/i', $data)) {
						$status = "phpDynDNS: (Error) User Authorization Failed";
					} else {
						$status = "phpDynDNS: (Unknown Response)";
						Logger::getRootLogger ()->info("phpDynDNS: PAYLOAD: {$data}");
						$this->_debug($data);
					}
					break;
                                case 'staticcling':
                                        if (preg_match("/invalid ip/i", $data)) {
                                                $status = "phpDynDNS: (Error) Bad Request - The IP provided was invalid.";
                                        } else if (preg_match('/required info missing/i', $data)) {
                                                $status = "phpDynDNS: (Error) Bad Request - Required parameters were not provided.";
                                        } else if (preg_match('/invalid characters/i', $data)) {
                                                $status = "phpDynDNS: (Error) Bad Request - Illegal characters in either the username or the password.";
                                        } else if (preg_match('/bad password/i', $data)) {
                                                $status = "phpDynDNS: (Error) Invalid password.";
                                        } else if (preg_match('/account locked/i', $data)) {
                                                $status = "phpDynDNS: (Error) This account has been administratively locked.";
                                        } else if (preg_match('/update too frequent/i', $data)) {
                                                $status = "phpDynDNS: (Error) Updating too frequently.";
                                        } else if (preg_match('/DB error/i', $data)) {
                                                $status = "phpDynDNS: (Error) Server side error.";
                                        } else if (preg_match('/success/i', $data)) {
                                                $status = "phpDynDNS: (Success) IP Address Updated Successfully!";
                                                $successful_update = true;
                                        } else {
                                                $status = "phpDynDNS: (Unknown Response)";
                                                Logger::getRootLogger ()->info("phpDynDNS: PAYLOAD: {$data}");
                                                $this->_debug($data);
                                        }
                                        break;
			}
			
			if($successful_update == true) {
				/* Write WAN IP to cache file */
				$currentTime = time();				  
				Logger::getRootLogger ()->info("phpDynDNS: updating cache file {$this->_cacheFile}: {$this->_wan_ip}");
				$file = fopen($this->_cacheFile, 'w');
				fwrite($file, $this->_wan_ip.':'.$currentTime);
				fclose($file);
			}
			$this->status = $status;
			Logger::getRootLogger ()->info($status);
		}

		/*
		 * Private Function (added 12 July 05) [beta]
		 *   Return Error, Set Last Error, and Die.
		 */
		function _error($errorNumber = '1') {
			switch ($errorNumber) {
				case 0:
					break;
				case 2:
					$error = 'phpDynDNS: (ERROR!) No Dynamic DNS Service provider was selected.';
					break;
				case 3:
					$error = 'phpDynDNS: (ERROR!) No Username Provided.';
					break;
				case 4:
					$error = 'phpDynDNS: (ERROR!) No Password Provided.';
					break;
				case 5:
					$error = 'phpDynDNS: (ERROR!) No Hostname Provided.';
					break;
				case 6:
					$error = 'phpDynDNS: (ERROR!) The Dynamic DNS Service provided is not yet supported.';
					break;
				case 7:
					$error = 'phpDynDNS: (ERROR!) No Update URL Provided.';
					break;
				case 10:
					$error = 'phpDynDNS: No Change In My IP Address and/or 25 Days Has Not Past. Not Updating Dynamic DNS Entry.';
					break;
				default:
					$error = "phpDynDNS: (ERROR!) Unknown Response.";
					/* FIXME: $data isn't in scope here */
					/* $this->_debug($data); */
					break;
			}
			$this->lastError = $error;
			Logger::getRootLogger ()->info($error);
		}

		/*
		 * Private Function (added 12 July 05) [beta]
		 *   - Detect whether or not IP needs to be updated.
		 *      | Written Specifically for pfSense (pfsense.com) may
		 *      | work with other systems. pfSense base is FreeBSD.
		 */
		function _detectChange() {
			
			Logger::getRootLogger ()->info("DynDns: _detectChange() starting.");
		
			$currentTime = time();

			$this->_dnsIP = $this->_wan_ip;
			Logger::getRootLogger ()->info("DynDns: Current WAN IP: {$this->_wan_ip}");

			if (file_exists($this->_cacheFile)) {
				if(file_exists($this->_cacheFile))
					$contents = file_get_contents($this->_cacheFile);
				else
					$contents = "";
				list($cacheIP,$cacheTime) = split(':', $contents);
				$this->_debug($cacheIP.'/'.$cacheTime);
				$initial = false;
				Logger::getRootLogger ()->info("DynDns: Cached IP: {$cacheIP}");
			} else {
				Functions::mountFilesystem ( 'mount' );
				$file = fopen($this->_cacheFile, 'w');
				fwrite($file, '0.0.0.0:'.$currentTime);
				fclose($file);
				Functions::mountFilesystem ( 'unmount' );
				$cacheIP = '0.0.0.0';
				$cacheTime = $currentTime;
				$initial = true;
				Logger::getRootLogger ()->info("DynDns: No Cached IP found.");
			}

			/*   use 2419200 for dyndns, dhs, easydns, noip, hn
			 *   zoneedit, dyns, ods
			 */
			$time = '2160000';

			$needs_updating = FALSE;
			/* lets deterimine if the item needs updating */
			if ($cacheIP != $this->_wan_ip) {
				$needs_updating = TRUE;
				Logger::getRootLogger ()->info("DynDns: cacheIP != wan_ip.  Updating.");
			}
			$update_reason = "Cached IP: {$cacheIP} WAN IP: {$this->_wan_ip} ";
			if (($currentTime - $cacheTime) > $time ) {
				$needs_updating = TRUE;
				Logger::getRootLogger ()->info("DynDns: More than 25 days.  Updating.");
			}
			$update_reason .= "{$currentTime} - {$cacheTime} > {$time} ";
			if ($initial == TRUE) {
				$needs_updating = TRUE;
				$update_reason .= "Inital update. ";
				Logger::getRootLogger ()->info("DynDns: Initial run.   Updating.");
			}
			/*   finally if we need updating then store the
			 *   new cache value and return true
                         */
			if($needs_updating == TRUE) {
				return TRUE;
			} else {
				return FALSE;			
			}
			
			Logger::getRootLogger ()->info("DynDns debug information: {$update_reason}");
			
		}

		/*
		 * Private Funcation (added 16 July 05) [beta]
		 *   - Writes debug information to a file.
		 *   - This function is only called when a unknown response
		 *   - status is returned from a DynDNS service provider.
		 */
		function _debug ($data) {
			$string = date('m-d-y h:i:s').' - ('.$this->_debugID.') - ['.$this->_dnsService.'] - '.$data.'\n';
			Functions::mountFilesystem ( 'mount' );
			$file = fopen($this->_debugFile, 'a');
			fwrite($file, $string);
			fclose($file);
			Functions::mountFilesystem ( 'unmount' );
		}

	}

?>