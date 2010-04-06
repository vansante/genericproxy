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
 * 		Functions.php
 * 		Includes functions used throughout the modules
 *
 */

class Functions {
	
	/**
	 * 	Mount filesystem
	 *
	 * 	Mounts / unmounts the /cfg partition
	 * 
	 * 	@static
	 * 	@access public
	 * 	@param String $mode Mode to mount in (r / rw)
	 * 	@return Boolean
	 */
	public static function mountFilesystem($mode) {
		if($mode == 'unmount'){
			Functions::shellCommand('umount /cfg',$errors,$returncode,null);	
		}
		elseif($mode == 'mount'){
			Functions::shellCommand('mount /cfg',$errors,$returncode,null);
		}
		return true;
	}
	
	/**
	 * 	Validate IPv4 address
	 *
	 * 	@static
	 * 	@access public
	 * 	@param String $ip	IP address to validate
	 *  @return Bool
	 */
	public static function is_ipAddr($ip) {
		$temp = long2ip ( ip2long ( $ip ) );
		if ($temp == $ip) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Get IP address for $interface
	 * 
	 * @static
	 * @access public
	 * @param  String	$interface 	interface identifier
	 * @return String
	 */
	public static function getIpAddress($interface) {
		$tmp = Functions::shellCommand ( "/sbin/ifconfig " . ( string ) $interface . " | /usr/bin/grep -w \"inet\" | /usr/bin/cut -d\" \" -f 2| /usr/bin/head -1" );
		$ip = str_replace ( "\n", "", $tmp );
		return $ip;
	}
	
	/**
	 * Validate hostname
	 * 
	 * @static
	 * @param String $hostname
	 * @return Bool
	 */
	public static function is_hostname($hostname){
		return eregi('^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$',$hostname);
	}
	
	/**
	 * Checks if $subnet is a subnet identifier (/1-32)
	 * 
	 * @static
	 * @param String $subnet
	 * @return Boolean
	 * @access public
	 */
	public static function is_subnet($subnet) {
		if (is_numeric ( $subnet )) {
			if ($subnet > 1 && $subnet <= 32) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * calculates network from the IPaddr and a subnet mask
	 * 
	 * @static
	 * @param String	$ipaddr 
	 * @param Integer	$subnetmask
	 * @return String	$subnet
	 * @access public
	 */
	public static function calculateNetwork($ipaddr, $subnetmask,$subnet_type = 'dotted') {
		$mask = $subnetmask==0?0:0xffffffff << (32 - $subnetmask);
		$network = long2ip(ip2long($ipaddr) & $mask);

		return $network.'/'.$subnetmask;
	}
	
	/**
	 * Converts CIDR notation into dotted netmask
	 * 
	 * @static
	 * @param unknown_type $cidr_mask
	 */
	public static function prefix2mask($cidr_mask)
	{
	        return long2ip(0xFFFFFFFF ^ (pow(2, 32 - $cidr_mask) - 1));
	}

	/**
	 * Converts dotted netmask into CIDR notation
	 * 
	 * Courtecy of php.net comments
	 * 
	 * @static
	 * @param Netmask $mask
	 */
	public static function mask2prefix($mask)
	{
	    if (($long = ip2long($mask)) === false)
	        return false;
	    for ($prefix = 0; $long & 0x80000000; ++$prefix, $long <<= 1) {}
	    if ($long != 0)
	        return false;
	    return $prefix;
	}
	
	/**
	 * check if $mac is a valid MAC address
	 * 
	 * @static
	 * @param String $mac
	 * @return Bool
	 */
	public static function isMacAddress($mac) {
		return preg_match("/^([0-9a-f]{2}([:-]|$)){6}$/i", $mac);
	}
	
	/**
	 * Execute shell command
	 * 
	 * @static
	 * @access public
	 * @param string $command Command to execute
	 * @param string $errors Errors from STRERR. Returns empty string when there have been no errors.
	 * @param int $returncode Command return code
	 * @param string $input Input to be used to feed to the command.
	 * @return string Returns all the output of the command
	 */
	public static function shellCommand($command, &$errors = null, &$returncode = null, $input = null) {
		$descriptorspec [0] = array ("pipe", "r" ); // stdin is a pipe that the child will read from
		$descriptorspec [1] = array ("pipe", "w" ); // stdout is a pipe that the child will write to
		$descriptorspec [2] = array ("pipe", "w" ); // stderr is a pipe that the child will write to
		
		//Open and execute command
		$process = proc_open ( $command, $descriptorspec, $pipes, null, $_ENV );
		
		if (is_resource ( $process )) {
			if (isset ( $input )) {
				fwrite ( $pipes [0], $input );
			}
			fclose ( $pipes [0] );
			
			$output = trim ( stream_get_contents ( $pipes [1] ) );
			fclose ( $pipes [1] );
			
			$errors = trim ( stream_get_contents ( $pipes [2] ) );
			fclose ( $pipes [2] );
			
			// It is important that you close any pipes before calling
			// proc_close in order to avoid a deadlock
			$returncode = proc_close ( $process );
			
			Logger::getRootLogger ()->debug ( "Running: " . $command . ((strlen ( $output ) > 0) ? (" Output: '" . $output . "'") : "") . ((strlen ( $errors ) > 0) ? (" Errors: '" . $errors . "'") : "") );
			
			return $output;
		}
	}
	
	/**
	 * Custom error handling.
	 * 
	 * @static
	 * @param unknown_type $errno  the level of the error raised, as an integer.
	 * @param unknown_type $errstr contains the error message, as a string. 
	 * @param unknown_type $errfile which contains the filename that the error was raised in, as a string. 
	 * @param unknown_type $errline which contains the line number the error was raised at, as an integer. 
	 */
	public static function errorHandler($errno, $errstr, $errfile, $errline) {
		$error = "[{$errno}] {$errstr} on line {$errline} in file {$errfile}";
		Logger::getRootLogger ()->error ( $error );
	}
	
	/**
	 * Get the available free memory.
	 * 
	 * @static
	 * @return int free memory
	 */
	public static function getFreeMemory() {
		$pagesize = Functions::shellCommand ( "/sbin/sysctl -n hw.pagesize" );
		$freememory = Functions::shellCommand ( "/sbin/sysctl -n vm.stats.vm.v_free_count" );
		
		return round ( ($freememory * $pagesize) / (1024 * 1024) );
	}
}

?>