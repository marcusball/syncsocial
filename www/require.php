<?php
$pageStartTime = microtime(true);

/** Functions and definitions that will be included for every page **/
require 'config.php';

$path = 'include'; 
set_include_path(get_include_path() . PATH_SEPARATOR . $path); //Adds the './include' folder to the include path
// That doesn't explain much, but basically, if I say "include 'file.php';", 
// it now searches './include' for file.php, as well as the default include locations.

require_once 'databasecontroller.php';

function getStartTime(){
	global $pageStartTime;
	return $pageStartTime;
}

function logError($script,$line,$description, $error){
	//$data = "File:        $script (Line: $line)\nDescription: ".$description."\nError:       ".$error."\nTime:        ".date('l, j F Y, \a\t g:i:s:u A')."\n--------------------------------\n";
	if($line !== null){
		$script .= ':'.$line;
	}
	if($error !== null){
		$error = '!{ '. $error . '}!';
	}
	$data = sprintf("[%s][%s][%s] (%s): %s %s\n",'error',date('D, j M Y, \a\t g:i:s A'),$_SERVER['REMOTE_ADDR'],$script,$description,$error);
	file_put_contents(SERVER_LOG_PATH_ERRORS, $data, FILE_APPEND);
}

function debug($message){
	echo $message . '<br />';
}

function SQLConnect(){
	try {
		$SQLCON = new DBController(DB_PDO_NAME.':host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
		$SQLCON->setPDOAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $SQLCON;
	}
	catch(PDOException $e){
		logError('require.php',__LINE__,"Could not select database (".DB_NAME.").",$e->getMessage(),time());
	}
	return null;
}

/** Create an SQL connection **/
$SQLCON = SQLConnect();
		
?>