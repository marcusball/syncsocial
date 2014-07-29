<?php
class DBController{
	private $sqlCon;
	
	public function __construct(){
		$dsn = "";
		$username = "";
		$password = "";
		$driver_options = null;
		for($i=0;$i<func_num_args();$i++){	//Loop through all of the arguments provided to the instruction ("RequestObject($arg1,$arg2,...)").
			switch($i){
				case 0:
					$dsn = func_get_arg($i);
					continue;
				case 1:
					$username = func_get_arg($i);
					continue;
				case 2:
					$password = func_get_arg($i);
					continue;
				case 3:
					$driver_options = func_get_arg($i);
					continue;
				default:
			}
		} 
		
		$this->sqlCon = new PDO($dsn,$username,$password,$driver_options);
	}
	public function setPDOAttribute($attribute,$value){
		return $this->sqlCon->setAttribute($attribute,$value);
	}
	
	public function getSQLTimeStamp($time = null){
		if($time == null) $time = time();
		return date('Y-m-d H:i:s',$time);
	}
	
	/***********************************************************/
	/* DO NOT MODIFY THE CODE ABOVE THIS SECTION               */
	/* Add in your own database access methods below this point*/
	/***********************************************************/
	
	
	/***********************************************************/
	/* Registration and Authentication methods                 */
	/***********************************************************/
	
	public function isValidUid($uid){
		try{
			$uidCheck = $this->sqlCon->prepare('SELECT uid FROM users WHERE uid=:uid');
			$uidCheck->execute(array(':uid'=>$uid));
			
			if(($uidReturn = $uidCheck->fetch(PDO::FETCH_ASSOC)) !== false){
				if($uidReturn['uid'] == $uid){
					return true;
				}
			}
		}
		catch(PDOException $e){
			logError('Error while checking if Uid is valid',$e->getMessage());
		}
		return false;
	}
	
	/*
	 * Checks if a user is currently registered with this email address 
	 * Returns 1 if email exists, 0 if email does not exist, false on error
	 */
	public function checkIfEmailExists($email){
		$val = $this->getUidFromEmail($email);
		if($val !== false){
			return min(1,$val); //If val >= 1, return 1; if val == 0, return 0;
		}
		return false;
	}
	
	/*
	 * Registers a new user.
	 * Returns false on error, or an int representing the user's UID on success.
	 */
	public function registerNewUser($fullName,$email,$password){
		$passwordHash = password_hash($password.PASSWORD_SALT, PASSWORD_BCRYPT, array("cost" => AUTH_HASH_COMPLEXITY));
		$nowDatetime = $this->getSQLTimeStamp();
		$insertQuery="INSERT INTO users (email,full_name,password) VALUES (:email,:fullname,:password);";
		try{
			$this->sqlCon->beginTransaction(); //Registering a new user means a lot of different inserts, so we want to make sure either all or nothing occurs. 
			
			
			//Let's insert the user into the user table
			$regStatement = $this->sqlCon->prepare($insertQuery);
			$regStatement->execute(array(':email'=>$email,':fullname'=>$fullName,':password'=>$passwordHash));
			
			$this->sqlCon->commit();
			
			$uidValue = $this->getUidFromEmail($email); //get the ID we just inserted, because lastInsertId can be weird sometimes.
		}
		catch(PDOException $e){
			$this->sqlCon->rollBack();
			logError("An error occurred while registering a new user! Code: {$e->getCode()}.",$e->getMessage());
			return false;
		}
		
		//If the uidValue is valid
		if($uidValue > 0 && $uidValue != false){
			return $uidValue;
		}
		return false;
	}
	
	/*
	 * Changes a users password
	 * returns true on successful update, false otherwise.
	 */
	public function changeUserPassword($uid,$newPassword){
		$passwordHash = password_hash($newPassword.PASSWORD_SALT, PASSWORD_BCRYPT, array('cost' => AUTH_HASH_COMPLEXITY));
		try{
			$updateQuery = $this->sqlCon->prepare('UPDATE users SET password=:password WHERE uid=:uid');
			$updateQuery->execute(array(':uid'=>$uid,':password'=>$passwordHash));
			if($updateQuery->rowCount() == 1){
				return true;
			}
		}
		catch(PDOException $e){
			logError('databasecontroller.php',__LINE__,'Error while trying to update user\'s password!',$e->getMessage,time(),false);
			
		}
		return false;
	}
	
	/*
	 * Checks whether a user's login credentials are valid
	 * $email: The user's email address
	 * $password: the user's unhashed password
	 * Returns false on error, user's UID on valid credentials (uid > 0), 0 on invalid credentials
	 */
	public function isValidLogin($email, $password){
		$uid = $this->getUidFromEmail($email);
		if($uid === 0 || $uid === false){ // Invalid email, or an error occurred
			return $uid; 
		}
		
		return $this->isValidPassword($uid,$password);
	}	
	
	/*
	 * Checks whether a user's login credentials are valid
	 * $email: The user's email address
	 * $password: the user's unhashed password
	 * Returns false on error, user's UID on valid credentials (uid > 0), 0 on invalid credentials
	 */
	 public function isValidPassword($uid, $password){
		/** BEGIN: Query database for login authentication **/
		$loginQuery = 'SELECT uid, email, password FROM users WHERE uid=:uid LIMIT 1;';
		try{
			$loginStatement = $this->sqlCon->prepare($loginQuery);
			$loginStatement->bindParam(':uid',$uid,PDO::PARAM_STR);
			$loginStatement->execute();
			$loginResult = $loginStatement->fetch();
		}
		catch(PDOException $e){
			logError("Could not check user's login credentials. Code: {$e->getCode()}. UID: \"{$uid}\"");
			return false;
		}
		
		/** We've gotten the result of the query, now we need to validate **/
		if($loginResult === false || $loginResult == null){
			return 0;
			// Email was wrong, but we don't tell the
			// user as this information could be exploited 
		}
		
		/** At this point we know the email matches a record in the DB.
		 ** Now we just need to make sure the password is correct.
		 ** If the password is correct we'll give session info
		 **/
		$uidValue = $loginResult['uid'];
		$hash = $loginResult['password'];
		if(!password_verify($password.PASSWORD_SALT,$hash)){
			/** The password provided did not match the one in the database **/
			
			/** Increment the attempt_count for this user, and lock the account if necessary **/
			return 0;
		}
		else{
			if (password_needs_rehash($hash, PASSWORD_BCRYPT, array("cost" => AUTH_HASH_COMPLEXITY))) {
				/** If we change the hash algorithm, or the complexity, then old passwords need to be rehashed and updated **/
				$this->updatePasswordHash($uidValue, $password);
			}
			return $uidValue;
		}
		
		return 0; //This code should never be reached, but I like to be safe.
	}	
	
	/* 
	 * Updates the database with a password hash of new complexity value.
	 */
	private function updatePasswordHash($uid, $unhashedPassword){
		$hash = password_hash($password.PASSWORD_SALT, PASSWORD_BCRYPT, array("cost" => AUTH_HASH_COMPLEXITY));
					
		$hashUpdate = "UPDATE users SET password=:hash WHERE uid=:uid;";
		try{
			$hashUpdateStatement = $this->sqlCon->prepare($hashUpdate);
			$hashUpdateStatement->execute(array(':hash'=>$hash,':uid'=>$uid));
		}
		catch(PDOException $e){
			logError("Could not update a user's rehashed password! Code: {$e->getCode()}. UID: \"{$uid}\"",$e->getMessage());
		}
	}
	
	/*
	 * Gets a user's UID from the user table corresponding to the given email address.
	 * Returns an int representing the uid of the user, 0 if the there is no matching email, or false on error.
	 */ 
	public function getUidFromEmail($email){
		$userCheckQuery = "SELECT uid FROM users WHERE email=:email LIMIT 1;"; //Make sure this keeps LIMIT 1
		try{
			$statement = $this->sqlCon->prepare($userCheckQuery);
			$statement->execute(array(':email' => $email));
			
			//If the query returned rows, then someone IS registered using this email
			if($statement->rowCount() > 0){
				$match = $statement->fetch();
				return $match['uid'];
			}
			else{
				return 0;
			}
		}
		catch(PDOException $e){
			logError("Error executing getting user from email! Query: \"$userCheckQuery\", Email: \"$email\".",$e->getMessage());
		}
		return false;
	}
	
	/***********************************************************/
	/* User information methods                                */
	/***********************************************************/
	
	/*
	 * Get information from the user table
	 */
	public function getUserInformation($uid){
		$userQuery = 'SELECT uid, email, full_name FROM users WHERE uid=:uid LIMIT 1;';
		try{
			$userStatement = $this->sqlCon->prepare($userQuery);
			$userStatement->bindParam(':uid',$uid,PDO::PARAM_INT);
			$userStatement->execute();
			
			if(($userData = $userStatement->fetch(PDO::FETCH_ASSOC)) !== false){
				return $userData;
			}
		}
		catch(PDOException $e){
			logError("Error executing getting user information! Uid = {$uid}.",$e->getMessage());
		}
		return false;
	}
	
	/*
	 * Returns the string of the full name of the user corresponding to the given uid.
	 */
	public function getUserFullName($uid){
		if(($info = $this->getUserInformation($uid)) !== false){
			return $info['full_name'];
		}
		return false;
	}
	
	/***********************************************************/
	/* Database Methods                                        */
	/***********************************************************/
	public function getLastErrorCode(){
		return $this->sqlCon->errorCode();
	}
	
	/***********************************************************/
	/* Event Methods                                           */
	/***********************************************************/
	public function createNewEvent($time,$ip){
		try{
			$eventCreateStatement = $this->sqlcon->prepare('INSERT INTO events (time, ip) VALUES (:time,:ip)');
			$eventCreateStatement->bindParam(':time',$this->getSQLTimeStamp($time));
			$eventCreateStatement->bindParam(':ip',$ip,PDO::PARAM_STR);
			$eventCreateStatement->execute();
			
			if($eventCreateStatement->rowCount() > 0){
				return true;
			}
		}
		catch(PDOException $e){
			logError('databasecontroller.php',__LINE__,'Unable to create new event!',$e->getMessage());
		}
		return false;
	}
}
?>