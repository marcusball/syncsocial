<?php
class PageObject{
	private $_DATA = null;
	private $_STATUS = 200;
	
	protected $dbCon;
	private $_errors;
	
	private $returnStatus;
	private $hasReturnInfo;
	private $requestArgs = array();
	
	public $user;
	public $formKeyManager;
	
	protected $Req;
	
	/** Constructor
	 ** Currently takes optional PDO connection argument
	 ** There really isn't much of a reason to modify this unless you really need something initialized before performRequest()
	 **/
	public function __construct(){
		$this->dbCon = getDatabaseController();
		$this->user = getCurrentUser();
		$this->formKeyManager = getFormKeyManager();
		$errors = array();
		$this->Req = $_REQUEST;
		
		$this->requestArgs = array_merge($_GET,$this->requestArgs);
	}
	
	/*
	 * This function will be called by most template pages
	 * Overwrite it to set a new title for each page.
	 */
	public function pageTitle(){
		echo SITE_NAME;
	}
	
	/*
	 * This function will be called before the template begins executing.
	 * If this function returns false, the page will stop loading. 
	 */
	public function preExecute(){}
	
	/*
	 * This function will be called after the template has completed execution.
	 */
	public function postExecute(){}
	
	/* 
	 * Overwrite this function in a page class to only allow the page to load if the viewer is logged in
	 */
	public function requireLoggedIn(){
		return false;
	}
	


	/*
	 * Used by the page functions to change the status and return data.
	 * As of this writing, data is useless. 
	 * Status is a standard HTTP status code (200, 404, etc).
	 */
	protected function setResult($status,$data = null){
		$this->_STATUS = $status;
		$this->_DATA = $data;
	}
	
	/*
     * The function is used by request.php to get the output data once the request has been performed.
	 */
	public function getData(){
		if($this->_DATA != null){
			return $this->_DATA;
		}
		else{
			return false;
		}
	}
	
	/** This function is used by request.php to get the result status once the request has been performed **/
	public function getStatus(){
		return $this->_STATUS;
	}
	
	/*
	 * Add an array of errors
	 */
	protected function addErrors($messages){
		foreach($messages as $message){
			$this->addError($message);
		}
	}
	protected function addError($message){
		if(is_array($message)){
			$this->addErrors($messages);
		}
		else{
			$this->_errors[] = $message; //Append the message
		}
	}
	public function getErrors(){
		return $this->_errors;
	}
	public function hasErrors(){
		return count($this->_errors) > 0;
	}
	public function outputErrors($message = null){
		if($message == null){
			$message = 'The following errors were reported:';
		}
		echo $message;
		echo '<ul class="errorsList">';
		foreach($this->_errors as $error){
			echo '<li class="errorItem">'.$error.'</li>';
		}
		echo '</ul>';
	}
	
	/* Let's have some nice helper functions. */
	
	/*
	 * Checks if multiple values are set in $this->Req.
	 * Usage: issetReqList('param1','param2','param3').
	 * Will return true if values are all set. Ex: index.php?param1=foo&param2=bar&param3 will return true.
	 * If values are missing, than it will return an array of the missing values.
	 * Ex: index.php?param1=foo will return array('param2','param3'). 
	 */ 
	protected function issetReqList(){
		$toTest = func_get_args();
		$missing = array();
		$isset = true;
		foreach($toTest as $testArg){
			$isset = $isset & ($isMissing = isset($this->Req[$testArg]));
			if($isMissing){
				$missing[] = $testArg; //Append $testArg to the list of missing arguments
			}
		}
		if(!$isset){
			return $missing;
		}
		return true;
	}
	
	/* 
	 * Checks if a value, or multiple values are present in $this->Req.
	 * Functionally equivilent to isset($_REQUEST['arg']), except this will accept multiple values as arguments.
	 * Usage: issetReq('singleParam') or issetReq('param1','param2').
	 * Returns true if all are set, false otherwise.
	 */
	protected function issetReq(){
		$toTest = func_get_args();
		foreach($toTest as $testArg){
			if(!isset($this->Req[$testArg])){
				return false;
			}
		}
		return true;
	}
	
	/*
	 * Gets a value from $this->Req. 
	 * Pretty much the same as just accessing $_REQUEST[$arg]
	 */
	protected function req($arg){
		if(isset($this->Req[$arg])){
			return $this->Req[$arg];
		}
		else{
			return null;
		}
	}
	
	/*
	 * Gets multiple values from $this->Req and returns them in an array that can be used with list(). 
	 * Ex: for request "index.php?param1=dog&param2=cat",
	 * reqList('param1','param2') would return array('dog','cat'). 
	 */ 
	protected function reqList(){
		$requested = func_get_args();
		$requestedArgs = array();
		foreach($requested as $requestedArg){
			if(isset($this->Req[$requestedArg])){
				$requestedArgs[] = $this->Req[$requestedArg];
			}
			else{
				$requestedArgs[] = null;
			}
		}
		return $requestedArgs;
	}

	/*
	 * If $_REQUEST contains the key $name, then this will echo the value corresponding to $name for use in a mirrored input box. 
	 * $name: the name value corresponding to the input box. 
	 * $type: the type of input. Currently only accepts 'text' and 'checkbox' (anything else is treated as 'text'). \
	 *        If type is 'checkbox' it will echo 'checked="checked"' or nothing. 
	 * $arg: If the type is 'checkbox' and there are multiple checkboxes using this name, then $_REQUEST[$name] is an array.
	 *       Use this to address the specific checkbox in question ($_REQUEST[$name][$arg]). 
	 */
	public function outputCurrentInput($name,$type='text',$arg = null){
		if(isset($_REQUEST[$name])){
			if($type == 'text'){
				echo htmlspecialchars($_REQUEST[$name]);
			}
			elseif($type == 'checkbox'){
				if(is_array($_REQUEST[$name])){
					if($arg == null){
						throw new Exception(sprintf('Current input to %s is an array, but no index was specified!',$name));
					}
					else{
						if(isset($_REQUEST[$name][$arg])){
							echo 'checked="checked"';
						}
					}
				}
				else{
					echo 'checked="checked"';
				}
			}
			else{
				echo htmlspecialchars($_REQUEST[$name]);
			}
		}
		else{
			echo "";
		}
	}
	
	/*
	 * Gets the current URL in an encoded way such that it is safe to echo to the page.
	 */
	public function getSafeUrl($append = null){
		$url = getCurrentUrl();
		if($append !== null){
			$url .= $append;
		}
		return htmlentities(urlencode($url));
	}
	
	public function hasReturnInformation(){
		if($this->hasReturnInfo == null){
			$this->hasReturnInfo = $this->checkReturnInformation();
		}
		return $this->hasReturnInfo;
	}
	
	private function checkReturnInformation(){
		if($this->issetReq('form_key','return_status') && $this->formKeyManager->isValidSingleUseFormKey($this->req('form_key'))){
			$this->returnStatus = ($this->req('return_status') == 'success');
			if($this->issetReq('return_errors')){
				$this->addErrors(json_decode(base64_decode($this->req('return_errors'))));
			}
			return true;
		}
		return false;
	}
	
	public function returnedSuccess(){
		return $this->returnStatus;
	}
	
	public function setRequestArgs($args){
		$this->requestArgs = array_merge($this->requestArgs,$args);
	}
	public function issetArg($key){
		return isset($this->requestArgs[$key]);
	}
	public function arg($key){
		if(isset($this->requestArgs[$key])){
			return $this->requestArgs[$key];
		}
		return null;
	}
}
?>