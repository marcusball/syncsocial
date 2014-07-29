<?php

/*
 * The properties in this class should correspond with those in your users table in your database.
 */
class UserRow{ 
	public $uid;
	protected $full_name;
	protected $email;
	protected $password;
}

class User extends UserRow{
	/** TODO **/
	protected $dbCon;
	private $tempData;
	public function __construct(){
		if(func_num_args() < 1 && $this->uid === null && get_class($this) !== 'CurrentUser'){ //uid was not supplied to constructor, and $uid has not already been set (as would be done if object created by function like pdo->fetchObject). 
			throw new Exception('User object expects at least one argument!');
			return;
		}
		$this->dbCon = getDatabaseController();

		if(func_num_args() >= 1){
			for($i=0;$i<func_num_args();$i++){
				$arg = func_get_arg($i);
				switch($i){
					case 0: //UID
						if(is_string($arg) && !ctype_digit($arg)){
							throw new Exception(sprintf('First parameter to User expects an integer, value was a %s. Value: '.$arg,(gettype($arg) != 'object')?gettype($arg):get_class($arg)));
						}
						$this->setUid((integer)$arg); //Keep this cast, it makes it safe to use === 
						break;
				}
			} 
		}
		else{
			$this->setUid($this->uid); //Yeah, this is bad, but it's 2am. I'll make this better later
		}
	}
	
	public function getUserInformation(){
		$tempData = $this->dbCon->getUserInformation($this->uid);
		if($tempData !== false){
			$this->_userData = $tempData;
			return true;
		}
		return false;
	}
	
	private function setUid($uid){
		$this->uid = $uid;
		$this->getUserInformation();
	}
	
	public function getUid(){
		return $this->uid;
	}
	
	public function getFullName(){
		return $this->_userData['full_name'];
	}
}

?>