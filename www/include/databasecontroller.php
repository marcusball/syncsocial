<?php
class DBController{
	private $sqlCon;
	
	//public __construct ( string $dsn [, string $username [, string $password [, array $driver_options ]]] )
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
	
	/** Place your database access methods here! **/
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