<?php
/** This is the page that will handle all incoming api requests **/
$startTime = microtime(true);
require 'require.php'; 

class RequestHandler{
	private $_request = null;
	private $sqlCon = null;
	private $user = null;
	private $reqArgs = null;
	
	public $pageFunctionObject;
	
	/** Contructor
	 ** Optional arguments: string Request, PDO SQL connection
	 **/
	public function __construct(){
		for($i=0;$i<func_num_args();$i++){
			$arg = func_get_arg($i);
		}
		$this->dbCon = getDatabaseController();
		$this->user = getCurrentUser();
		$this->pageFunctionObject = null;
	}
	
	/** Set the request **/
	/*public function setRequest($request){
		$this->_request = $request;
	}*/
	
	/* 
	 * Gets the include path for the script that handles the given request
	 * Returns false if there is no script for the input request
	 * Returns array of the path to the handling script, and the path to the template for the handling script
	 * The second element of the array will be false if the template file does not exist
	 */
	private function getRequestScript($requested){
		if($requested != null){
			$includeFile = INCLUDE_PATH_PHP.$requested.INCLUDE_PHP_EXTENSION;
			$templateFile = INCLUDE_PATH_TEMPLATE.$requested.INCLUDE_TEMPLATE_EXTENSION;
			
			$hasPhp = file_exists($includeFile);
			$hasTemplate = file_exists($templateFile);
			
			if($hasPhp || $hasTemplate){
				return array(($hasPhp)?$includeFile:false,($hasTemplate)?$templateFile:false);
			}
		}
		return array(false,false);
	}
	
	/*
	 * Tries to execute the pages given by the requested page. 
	 * If neither a template file (/page-content/), nor a PageObject file (/page-functions/) are found, then it will return the standard 404 page.
	 * $requestedPage: the page name (eg, 'index.php' will be 'index'; 'foo/bar.php' will be 'foo/bar'). 
	 */
	public function executeRequest($requestedPage){
		list($requestedScript,$requestedTemplate) = $this->getRequestScript($requestedPage);
		if($requestedScript === false && $requestedTemplate === false){
			$this->handleOutput(404);
		}
		else{
			$this->executePage($requestedScript, $requestedTemplate);
		}
	}
	
	private function executePage($requestedScript, $requestedTemplate){
		if($requestedScript !== false){
			require $requestedScript; //Bring in the script that will perform the server side operations for the requested page

			$requestClass = $this->getPageFunctionClass(REQUEST_CLASS_PARENT); //Get the name of the class that corresponds to the class within $requestedScript. 
			if($requestClass === false){ //If no such class exists, then we'll fall back to using just the template page. 
				$this->executeWithoutPage($requestedTemplate); //Instead of dying, let's just display the template. 
				return;
			}

			$this->pageFunctionObject = new $requestClass(); //Instantiate our page handling object
			$this->pageFunctionObject->setRequestArgs($this->reqArgs); //Tell the handler any args that might be present.
			
			//executes the requireLoggedIn() function from the PageObject handler. If that function returns true, then test if the user is logged in. 
			if(call_user_func(array($this->pageFunctionObject,REQUEST_FUNC_REQUIRE_LOGGED_IN)) === true && !$this->user->isLoggedIn()){ //If the user must be logged in to view this page, and the user is not logged in
				$this->handleOutput(401); //not authorized
			}
			else{ 
				$this->interalPreExecute(); //Call the global RequestHandler pre-execution function. Take care of anything that should happen before the page begins loading. 
				$preexResult = call_user_func(array($this->pageFunctionObject,REQUEST_FUNC_PRE_EXECUTE)); //Call the page specific pre-execution function. 
				if($preexResult !== false){ //Only load the template if preExecute() does not return false. 
					if($requestedTemplate !== false){ //If we have a template file, import that.
						$this->includePageFile($requestedTemplate,$this->pageFunctionObject);
					}
				}
				call_user_func(array($this->pageFunctionObject,REQUEST_FUNC_POST_EXECUTE)); //Page specific post-execution function. 

				$outputData = call_user_func(array($this->pageFunctionObject, REQUEST_FUNC_RET_DATA));
				$outputStatus = call_user_func(array($this->pageFunctionObject, REQUEST_FUNC_RET_STATUS));
				
				$this->handleOutput($outputStatus,$outputData);
			}
		}
		else{ //Since we know we have either the script or the template, then, here, we must have only the template.
			$this->executeWithoutPage($requestedTemplate);
		}
	}
	
	/*
	 * This will display the template file corresponding to the request, without a corresponding Page object.
	 */
	private function executeWithoutPage($requestedTemplate){
		$this->interalPreExecute(); //Call the global RequestHandler pre-execution function. Take care of anything that should happen before the page begins loading. 
		$templateReferenceVar = new NoPage(); //This is essentially to make error reporting obvious. If the template without an associated php file tries to call functions as though there is a php file for it, this will output some nice helpful errors. 
		$this->includePageFile($requestedTemplate,$templateReferenceVar);
	}

	/*
	 * Finds the name of a class that is a descendent of $parentClass. 
	 * This searches the list of currently declared classes, and finds any that are
	 * chidren of $parentClass. Currently, if more than one is found, it will return
	 * the first one found. 
	 * Returns the name of the class, or false is none are found.
	 */
	private function getPageFunctionClass($parentClass){
		$classes = get_declared_classes();
		$children = array();
		$parent = new ReflectionClass($parentClass); //A class that reports information about a class
		
		foreach ($classes AS $class){
			$current = new ReflectionClass($class);
			if ($current->isSubclassOf($parent)){
				$children[] = $current;
			}
		}
		
		if(count($children) < 1){
			//debug("No class was found that is a subclass of {$parentClass}!");
			logWarning("No class was found that is a subclass of {$parentClass}!");
			return false;
		}
		return $children[0]->name;
	}
	
	/*
	 * If there is anything that must happen before any page starts loading, you can do it here.
	 */ 
	private function interalPreExecute(){
		OutputHandler::preExecute();
	}
	
	private function handleOutput($status,$data = null){
		OutputHandler::handleOutput($status,$data);
	}
	
	private function unexpectedError($data = null){
		$this->handleOutput(500,$data);
	}
	
	private function includePageFile($file, $templateRequestHandler){
		${TEMPLATE_REFERENCE_VARIABLE} = $templateRequestHandler;
		${GLOBAL_REFERENCE_VARIABLE} = $this; //This will give the page a reference to this RequestHandler to access public methods 
		${USER_REFERENCE_VARIABLE} = $this->user;
		include $file; //Execution of the template begins. 
	}
	
	/*
	 * Calls the pageTitle() function given in the request's PageObject class. If that function is not defined for the requested page, it will return the default (in this case, the SITE_NAME).
	 */
	public function pageTitle(){
		if($this->pageFunctionObject != null){
			if(method_exists($this->pageFunctionObject,'pageTitle')){
				$this->pageFunctionObject->pageTitle();
				return;
			}
		}
		echo SITE_NAME;
	}

	/*
	 * Includes a page from the /page-include/ directory.
	 */
	public function includeFile($file){
		$path = INCLUDE_PATH_PAGE_INCLUDE . $file;
		if(file_exists($path)){
			$this->includePageFile($path,new NoPage());
			return true;
		}
		return false;
	}
	
	/*
	 * Set an array containing key value pairs which will be sent to the PageObject
	 * for the requested URI path.
	 */
	public function setRequestArgs($args){
		$this->reqArgs = $args;
	}
}

//Just remember that, internally "bla.com/" will still be considered "bla.com/index.php" when checking the rewrite condition. 
$path = parsePath();
$requestArgs = array();

if(($rewrite = getRewritePath($path)) !== false){ //If this requested URL is being handled as a rewrite page
	list($file,$groups) = $rewrite; //Get the file system file name , and the regex groups from the regex that matched this request. 
	
	$path = $file; //Update the request path with the file system file (as defined in $REWRITE_RULES in config.php).
	$requestArgs = array_merge($requestArgs,$groups);
}
$request = cleanPath($path);
if($request === false){
	OutputHandler::handleOutput(404);
}
else{
	if($request == '/'){ $request = cleanPath('index.php'); }

	$Handler = new RequestHandler();
	$Handler->setRequestArgs($requestArgs);
	$Handler->executeRequest($request);
}

$endTime = microtime(true);
//debug(sprintf('<br />Execution time: %5f seconds',($endTime - $startTime)));
?>