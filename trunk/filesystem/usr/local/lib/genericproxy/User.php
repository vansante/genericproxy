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
 * User and autentication.
 * Single user login. Only one user may be loggd in at any time. This
 * is done by saving the only active session to a session file that
 * contains information about the current logged in user.
 *
 * No one else my log in while the session is active. If the session is
 * x minutes old, the session will be removed. If a user loses his session ID
 *  while logged in, he may log in again under the same username.
 * @author Sebastiaan Gibbon
 * @version 0
 */

class User {
	/**
	 * Group where the user belongs too.
	 * 
	 * @var unknown_type
	 */
	public $group;
	/**
	 * Username
	 * 
	 * @var string
	 */
	public $name;
	/**
	 * Config of the system
	 * 
	 * @var Config
	 */
	public $configuration;
	
	/**
	 * value if the user is logged in.
	 * 
	 * @var bool
	 */
	private $isLoggedin = false;
	
	/**
	 * Array with the current active session or null
	 * 
	 * @var array|null
	 */
	private $session;
	
	const SESSION_FILE = '/tmp/login.session';
	
	public function __construct($configuration) {
		$this->configuration = &$configuration;
		
		//Get system session data and check if an user is already logged in.
		$this->getSession ();
		
		//The user is already logged in
		if (isset ( $this->session ) && $this->session ['uid'] == session_id () && $this->session ['ip'] == $_SERVER ['REMOTE_ADDR']) {
			//populate User with user data
			$xmlUser = $this->getUser ( $this->session ['name'] );
			$this->name = $xmlUser ['name'];
			$this->group = $xmlUser ['group'];
			$this->isLoggedin = true;
			$this->updateSession ();
			Logger::getRootLogger ()->info ( "User {$xmlUser['name']} is logged in." );
		}
	}
	
	/**
	 * Get the account with the all access
	 * 
	 * @param Config $configuration System configuration
	 * @return User Returns an user with super admin rights
	 */
	public static function getRoot($configuration) {
		$user = new User ( $configuration );
		
		$user->group = "xxx";
		$user->name = "Super Admin";
		$user->isLoggedin = true;
		
		return $user;
	}
	
	/**
	 * Asks the system to login.
	 * 
	 * Expects $_POST['user'] and $_POST['password'] for login data.
	 * On both success and failiare a XML is returned with the login result.
	 * 
	 * @access public
	 */
	public function login() {
		//see if the user exists in the config.
		if (empty ( $_SESSION['uid'] ) && !isset($_POST['user'])) {
			echo '<reply action="login-error"><message type="error">Login required.</message></reply>';
			return;
		}
		
		$xmlUser = $this->getUser ( $_POST ['user'] );
		
		//Validate the user
		if ( isset ( $xmlUser ) && (empty($xmlUser ['password']) || (isset($_POST ['password']) && $xmlUser ['password'] == crypt($_POST ['password'],$xmlUser['password'])))) {
			//if a session exists, restrict login to only that user.
			if (! isset ( $this->session ) || ($this->session ['name'] == $xmlUser ['name'] && $this->session ['ip'] == $_SERVER ['REMOTE_ADDR'])) {
				//Login OK
				$this->isLoggedin = true;
				$this->name = $xmlUser ['name'];
				$this->group = $xmlUser ['group'];
				$this->session ['uid'] = session_id ();
				$this->session ['name'] = ( string ) $xmlUser ['name'];
				$this->session ['ip'] = $_SERVER ['REMOTE_ADDR'];
				session_register('uid');
				$_SESSION['uid'] = (string) $xmlUser['name'];
				$_SESSION['group'] = $xmlUser['group'];
				$this->updateSession ();
				
				Logger::getRootLogger ()->info ( "User {$xmlUser['name']} is logged in." );
				echo '<reply action="login-ok"><message>logged in.</message></reply>';
			} else {
				//Another user is logged in
				Logger::getRootLogger ()->info ( "Could not login user {$_POST['user']}. Another user is already logged in." );
				echo '<reply action="login-error"><message type="error">Error logging in. Another user is already logged in.</message></reply>';
			}
		} else {
			if(isset($_POST['password'])){
				//Wrong user and/or pass
				session_destroy();
				Logger::getRootLogger ()->info ( "Could not login user {$_POST['user']}. Wrong username and/or password." );
				echo '<reply action="login-error"><message type="error">Error logging in. Username and or password is incorrect.</message></reply>';
			}
			else{
				session_destroy();
				echo '<reply action="login-error"><message type="error">Your session has expired</message></reply>';
			}
		}
	}
	
	/**
	 * Log the user out and delete session information.
	 * 
	 * Javascript should forget the sessionID client side.
	 */
	public function logout() {
		if ($this->isLoggedIn ()) { //only the logged in user may call logout.
			//Remove session data
			unset ( $this->session );
			$this->updateSession ();
			unset ($_SESSION['uid']);
			session_destroy();
		}
		echo '<reply action="ok" />';
	}
	
	/**
	 * 	Bumps the USR group users to ROOT if requested by the front-end
	 * 
	 * 	@throws Exception
	 */
	public function elevatePremissions() {
		if($user->group == 'USR'){
			$user->group = "ROOT";
		}
		else{
			throw new Exception('This user group cannot elevate permissions');
		}
	}
	
	/**
	 * Check if the user is logged in
	 * 
	 * @return bool
	 */
	public function isLoggedIn() {
		return $this->isLoggedin;
	}
	
	/**
	 * Retrieve a user from the XML
	 * 
	 * @param String $name
	 * @return SimpleXMLElement|null
	 */
	private function getUser($name) {
		$i = 0;
		
		foreach ( $this->configuration->getElement ( "system" )->users [0] as $user ) {
			if ($user->getName () == 'user' && strcasecmp ( $user ['name'], $name ) == 0) {
				return $user;
			} elseif ($user->getName () == 'user') {
				$i ++; // count the users.
			}
		}
		
		//If no users exist, get the default user
		if ($i == 0) {
			$user = $this->configuration->getElement ( "system" )->users [0]->{'default-user'} [0];
			if (strcasecmp ( $user ['name'], $name ) == 0) {
				return $user;
			}
		}
		
		return null; // user not found.
	}
	
	/**
	 * Writes the session to disk and updates the lastlogin time.
	 */
	private function updateSession() {
		//update the login time.
		if (isset ( $this->session )) {
			$this->session ['lastlogin'] = time ();
		}
		
		//write $session['uid'], $session['name'], $session['lastlogin'] to file
		if (isset ( $this->session )) {
			file_put_contents ( self::SESSION_FILE, serialize ( $this->session ) );
		} elseif (file_exists ( self::SESSION_FILE )) { //remove session information
			session_destroy();
			unlink ( self::SESSION_FILE );
		}
	}
	
	/**
	 * Retrives the session data from disk and fills $this->session
	 * 
	 * If no session data exists, $this->session will remain empty.
	 */
	private function getSession() {
		//read session file and fill the array
		if (file_exists ( self::SESSION_FILE )) {
			$this->session = unserialize ( file_get_contents ( self::SESSION_FILE ) );
		}
		
		//If the last session has expired, remove the session
		if (isset ( $this->session ) && $this->session ['lastlogin'] + (10 * 60) <= time ()) {
			unset ( $this->session );
			$this->updateSession ();
		}
	}
}
?>