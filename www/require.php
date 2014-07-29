<?php
$pageStartTime = microtime(true);

/** Functions and definitions that will be included for every page **/
require 'config.php';

$path = dirname(__FILE__) . '/include'; 
set_include_path(get_include_path() . PATH_SEPARATOR . $path); //Adds the './include' folder to the include path
// That doesn't explain much, but basically, if I say "include 'file.php';", 
// it now searches './include' for file.php, as well as the default include locations.

/*
 * Includes all of the necessary helper classes and files.
 * $initLight is basically used to allow for cases where one may not want to include all of the files.
 * This is mostly for cases where custom files are used (not the default PageObject classes and templates),
 * where one may wish to access resources from this framework, without creating a page handled by this framework. 
 */
function init($initLight = false){
	require_once 'databasecontroller.php';
	//require_once 'currentuser.php';
	if(!$initLight){ //If we want to include everything. 
		require_once 'output.php';
		require_once 'pageobj.php';
		require_once 'nopage.php';

		//require_once 'user.php';
		require_once 'formkeys.php';
		require_once 'password.php';
		
		/* 
		 * cleaner.php requires HTMLPurifier. 
		 * Download it from //htmlpurifier.org/ and place the htmlpurifier folder in /include/ 
		 */
		//require_once 'cleaner.php'; 
	}
}

function getStartTime(){
	global $pageStartTime;
	return $pageStartTime;
}

/*
 * Here's a happy little log function.
 * Use it for errors.
 * $description is for a written description of the problem.
 * $error is for the output of error functions.
 */
function logError($description, $error){
	$debug = debug_backtrace();
	if(isset($debug[0])){
		$data = sprintf("[%s][%s][%s] (%s): %s %s\n",
			'error',
			date('D, j M Y, \a\t g:i:s A'),
			$_SERVER['REMOTE_ADDR'],
			($debug[0]['file'].':'.$debug[0]['line']),
			$description,
			$error
		);
		file_put_contents(SERVER_LOG_PATH_ERRORS, $data, FILE_APPEND);
	}
}

/*
 * Nice little log function for warnings.
 */
function logWarning($description){
	$debug = debug_backtrace();
	if(isset($debug[0])){
		$data = sprintf("[%s][%s][%s] (%s): %s\n",
			'warning',
			date('D, j M Y, \a\t g:i:s A'),
			$_SERVER['REMOTE_ADDR'],
			$debug[0]['file'].':'.$debug[0]['line'],
			$description
		);
		file_put_contents(SERVER_LOG_PATH_ERRORS, $data, FILE_APPEND);
	}
}

/*
 * Echo safe
 * Hopefully echos information in a way that is safe to echo
 */
function es($message){
	echo htmlspecialchars($message);
}

function debug($message){
	echo $message . '<br />';
}

/*
 * Connects to a PDO database and returns an instance of DBController, from databasecontroller.php
 * DO NOT call this function directly to access the database. 
 * This file calls it (in getDatabaseController() ONLY), and maintains a reference to the value.
 * Call getDatabaseController() to get a reference to it. 
 */
function SQLConnect(){
	try {
		$SQLCON = new DBController(DB_PDO_NAME.':host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
		$SQLCON->setPDOAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $SQLCON;
	}
	catch(PDOException $e){
		logError("Could not select database (".DB_NAME.").",$e->getMessage(),time());
	}
	
	require_once 'nodatabasecontroller.php';
	return new NoDatabaseController();
}

/*
 * Access method for receiving a reference to the database controller (DBController).
 */
function getDatabaseController(){
	global $SQLCON;
	if($SQLCON !== null){
		//echo 'giving current dbCon';
		return $SQLCON;
	}
	else{
		logWarning('Require.php generating new Database Controller. This shouldn\'t happen usually.');
		return SQLConnect();
	}
}

/*
 * Access method for receiving a reference to the CurrentUser object. 
 */
function getCurrentUser(){
	return null;
	global $USER;
	if($USER !== null){
		return $USER;
	}
	else{
		logWarning('Require.php generating new CurrentUser. This shouldn\'t happen usually.');
		return new CurrentUser();
	}
}

/*
 * Access method for receiving a reference to the FormKeyManager object.
 */
function getFormKeyManager(){
	global $FORMKEYMAN;
	if($FORMKEYMAN !== null){
		return $FORMKEYMAN;
	}
	else{
		logWarning('Require.php generating new Form Key Manager. This shouldn\'t happen usually.');
		return new FormKeyManager();
	}
}

function parsePath($withQueryArgs = true){
	//http://stackoverflow.com/questions/16388959/url-rewriting-with-php
	$uri = rtrim( dirname($_SERVER["SCRIPT_NAME"]), '/' );
	$uri = '/' . trim( str_replace( $uri, '', $_SERVER['REQUEST_URI'] ), '/' );
	$uri = urldecode( $uri );
	if(!$withQueryArgs){
		$matchVal = preg_match('#^(?\'path\'[^\?]*)(?:\?.*)?$#i',$path,$matches);
		if($matchVal !== 0 && $matchVal !== false){
			return $matches['path'];
		}
	}
	return $uri;
}

function cleanPath($path){
	if($path == '/') return $path;
	
	$matchVal = preg_match('#^/?(?\'path\'[^\.]+)\.php(?:\?.*)?$#i',$path,$matches);
	if($matchVal === 0 || $matchVal === false){
		return false;
	}
	return $matches['path'];
}

function getRewritePath($path){
	global $REWRITE_RULES; //get rewrite rules from config.php
	foreach($REWRITE_RULES as $file=>$rule){
		$match = preg_match('#'.$rule.'#i',$path,$matches);
		if($match !== 0 && $match !== false){
			return array($file,$matches);
		}
	}
	return false;
}

function getCurrentUrl(){
	return parsePath();
}


/** Imports and includes **/
global $INIT_LIGHT; //Get the INIT_LIGHT global variable. This should be set before 'require.php' is included. 
$INIT_LIGHT = ($INIT_LIGHT === true)?true:false; //Make sure this is false if the variable is anything other than true (null/non-bool).
init($INIT_LIGHT); //Import stuff

/** Create an SQL connection **/
$SQLCON = SQLConnect();
//$USER = new CurrentUser(); //Keep these in global scope
$FORMKEYMAN = null; //Keep these in global scope

if($INIT_LIGHT === false){
	global $FORMKEYMAN;
	$FORMKEYMAN = new FormKeyManager();
}
		
?>