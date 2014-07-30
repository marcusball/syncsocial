<?php
require_once 'user.php';
class CurrentUser extends User{	
	private $_isLoggedIn = false; //If the user has been validated as being logged in.
	private $_hasCheckedAuthentication = false; //If the class has checked yet whether the user is logged in. 
	
	/*
	 * Constructor for the Authentication class
	 */
	public function __construct(){
		session_start();
		$this->dbCon = getDatabaseController();
		
		$this->checkAuthentication();
		if($this->isLoggedIn()){
			$this->renewSession();
		}
		
		parent::__construct();
	}
	
	private function checkAuthentication(){
		$this->_isLoggedIn = $this->hasAuthentication();
		$this->_hasCheckedAuthentication = true;
	}
	
	private function hasAuthentication(){
		if(!isset($_SESSION['USER_ID']) || (trim($_SESSION['USER_ID'])=='')) { 
			return false;
		}
		if(!$this->sessionNotExpired()){
			$this->cleanSessionData();
			return false;
		}
		
		if(!$this->isValidUser($_SESSION['USER_ID'])){
			$this->cleanSessionData();
			return false;
		}
		
		$this->setClassCredentials($_SESSION['USER_ID']);
		return true;
	}
	
	private function isValidUser($uid){
		return $this->dbCon->isValidUid($uid);
	}
	
	private function sessionNotExpired(){
		if(!isset($_SESSION['LAST_USE']) || time() - $_SESSION['LAST_USE'] >= SESSION_EXPIRATION_AGE){
			return false;
		}
		return true;
	}
	
	/** Update the LAST_USE time of the session, and regenerate the session ID **/
	private function renewSession(){
		session_regenerate_id();
		$_SESSION['LAST_USE'] = time();
	}
	
	public function isLoggedIn(){
		return $this->_isLoggedIn;
	}
	
	public function giveCredentials($uid){
		$_SESSION['USER_ID'] = $uid;
		$_SESSION['LAST_USE'] = time();
		$this->setClassCredentials($uid);
	}
	
	private function setClassCredentials($uid){
		$this->uid = $uid;
		$this->getUserInformation();
	}
	
	private function cleanSessionData(){
		unset($_SESSION['USER_ID']);
		unset($_SESSION['LAST_USE']);
	}
	
	public function logOut(){
		unset($_SESSION['USER_ID']);
		unset($_SESSION['LAST_USE']);
		session_unset();
		session_destroy();
	}
	
	public function getIp(){
		return $_SERVER['REMOTE_ADDR'];
	}
	
	public function getGuestUid(){
		if(!isset($_SESSION['GUEST_UID'])){
			$guid = $this->dbCon->newGuest($this->getIp(),$_SERVER['HTTP_USER_AGENT']);
			if($guid !== false){
				$_SESSION['GUEST_UID'] = $guid;
			}
			else{
				return 0;
			}
		}
		return $_SESSION['GUEST_UID'];
	}
}
?>