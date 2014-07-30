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
			$eventCreateStatement = $this->sqlCon->prepare('INSERT INTO events (time, ip) VALUES (:time,:ip)');
			$eventCreateStatement->bindValue(':time',$this->getSQLTimeStamp($time));
			$eventCreateStatement->bindParam(':ip',$ip,PDO::PARAM_STR);
			$eventCreateStatement->execute();
			
			if($eventCreateStatement->rowCount() > 0){
				return true;
			}
		}
		catch(PDOException $e){
			logError('Unable to create new event!',$e->getMessage());
		}
		return false;
	}
	
	/*
	 * Gets events that are active, or were active within the past $maxSecondsPast.
	 * Returns the event if it's still active (the countdown is still occurring), or it ended within the last $maxSecondsPast.
	 * returns false if there is no active event, or if there was an error.
	 */
	public function getActiveEvent($maxSecondsPast = 5){
		try{
			$eventStatement = $this->sqlCon->prepare('
				SELECT 
						events.*, 
						extract(EPOCH FROM (current_timestamp - events.time)::INTERVAL) AS diff
					FROM events 
					WHERE extract(EPOCH FROM (current_timestamp - events.time)::INTERVAL) <= :max
			');
			$eventStatement->bindParam(':max',$maxSecondsPast,PDO::PARAM_INT); 
			$eventStatement->execute();
			
			$event = $eventStatement->fetchObject('Event');
			if($event !== false){
				return $event;
			}
		}
		catch(PDOException $e){
			logError('Unable to fetch active events!',$e->getMessage());
		}
		return false;
	}
	
	public function guestJoinEvent($guid, $eid){
		if($this->guestIsAttending($guid,$eid)){
			return false;
		}
		try{
			$guestStatement = $this->sqlCon->prepare('INSERT INTO attendees (eid,guid) VALUES (:eid, :guid)');
			$guestStatement->bindParam(':eid',$eid,PDO::PARAM_INT);
			$guestStatement->bindParam(':guid',$guid,PDO::PARAM_INT);
			$guestStatement->execute();
			
			if($guestStatement->rowCount() > 0){
				return true;
			}
		}
		catch(PDOException $e){
			logError('Unable to add guest to attendees!',$e->getMessage());
		}
		return false;
	}
	
	public function guestIsAttending($guid,$eid){
		try{
			$guestStatement = $this->sqlCon->prepare('SELECT 1 FROM attendees WHERE eid=:eid AND guid=:guid');
			$guestStatement->bindParam(':eid',$eid,PDO::PARAM_INT);
			$guestStatement->bindParam(':guid',$guid,PDO::PARAM_INT);
			$guestStatement->execute();
			
			if($guestStatement->fetch()){
				return true;
			}
		}
		catch(PDOException $e){
			logError('Unable to check if guest is attending event!',$e->getMessage());
		}
		return false;
	}
	
	public function getAttendeeCount($eid){
		try{
			$countStatement = $this->sqlCon->prepare('SELECT count(*) AS count FROM attendees WHERE eid=:eid');
			$countStatement->bindParam(':eid',$eid,PDO::PARAM_INT);
			$countStatement->execute();
			
			if(($countVal = $countStatement->fetch(PDO::FETCH_ASSOC)) !== false){
				return $countVal['count'];
			}
		}
		catch(PDOException $e){
			logError('Unable to get attendee count!',$e->getMessage());
		}
		return false;
	}
	
	
	/***********************************************************/
	/* Guest User Methods                                      */
	/***********************************************************/
	
	/*
	 * Creates a new guest and returns the guest uid.
	 * $ip: the visitors IP address, for logging.
	 * returns the gUid, or false on error.
	 */
	public function newGuest($ip,$useragent){
		if(($numRecent = $this->getNumberRecentGuestsByIp($ip,60 * 60 * 24)) > 0){
			$recent = $this->getRecentGuestByIpAgent($ip,$useragent,60 * 60 * 24);
			if($recent !== false){
				return $recent;
			}
				
		}

		if($numRecent <= 20){
			try{
				$guestStatement = $this->sqlCon->prepare('INSERT INTO guests (ip,first_time,browser) VALUES (:ip,:time,:ua)');
				$guestStatement->bindParam(':ip',$ip,PDO::PARAM_STR);
				$guestStatement->bindValue(':time',$this->getSQLTimeStamp(),PDO::PARAM_STR);
				$guestStatement->bindValue(':ua',sha1($useragent));
				$guestStatement->execute();
				
				if($guestStatement->rowCount() > 0){
					$gUid = $this->sqlCon->lastInsertId('guests_guid_seq');
					if((integer)$gUid > 0){
						return (integer)$gUid;
					}
				}
			}
			catch(PDOException $e){
				logError('Unable to create new guest!',$e->getMessage());
			}
		}
		return false;
	}
	
	/*
	 * Checks for recent visitations by the given IP within the past $seconds seconds. 
	 * Returns the guest Uid of the visitor if he/she has visited recently, otherwise false.
	 */
	public function getRecentGuestByIpAgent($ip,$useragent,$seconds){
		try{
			$guestCheck = $this->sqlCon->prepare('SELECT guid FROM guests WHERE extract(EPOCH FROM (current_timestamp - first_time)::INTERVAL) <= :max AND ip=:ip AND browser=:ua');
			$guestCheck->bindParam(':max',$seconds,PDO::PARAM_INT);
			$guestCheck->bindParam(':ip',$ip);
			$guestCheck->bindValue(':ua',sha1($useragent));
			$guestCheck->execute();
			
			$guest = $guestCheck->fetch(PDO::FETCH_ASSOC);
			if($guest !== false){
				return $guest['guid'];
			}
		}
		catch(PDOException $e){
			logError('Unable to check for recent guest by IP and UserAgent!',$e->getMessage());
		}
		return false;
	}
	
	public function getNumberRecentGuestsByIp($ip,$seconds){
		try{
			$guestCheck = $this->sqlCon->prepare('SELECT count(guid) AS count FROM guests WHERE extract(EPOCH FROM (current_timestamp - first_time)::INTERVAL) <= :max AND ip=:ip');
			$guestCheck->bindParam(':max',$seconds,PDO::PARAM_INT);
			$guestCheck->bindParam(':ip',$ip);
			$guestCheck->execute();
			
			$guest = $guestCheck->fetch(PDO::FETCH_ASSOC);
			if($guest !== false){
				return $guest['count'];
			}
		}
		catch(PDOException $e){
			logError('Unable to check for recent guest by IP!',$e->getMessage());
		}
		return 0;
	}
	
	/***********************************************************/
	/* Database Methods                                        */
	/***********************************************************/
	public function beginTransaction(){
		return $this->sqlCon->beginTransaction();
	}
	public function commit(){
		return $this->sqlCon->commit();
	}
	public function rollBack(){
		return $this->sqlCon->rollBack();
	}
}
?>