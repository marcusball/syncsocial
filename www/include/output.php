<?php

class OutputHandler{
	public static function preExecute(){
		ob_start();
	}
	public static function handleOutput($status,$data = null){
		switch($status){
			case 200:
				static::handleSuccess();
				break;
			case 210: //I made this case up; "Use alternate data"
				static::handleUseAlternateData($data);
				break;
			case 302:
				static::handleTemporaryRedirect($data);
				break;
			case 400:
				static::handleBadRequest();
				break;
			case 401:
				static::handleNotAuthorized();
				break;
			case 403:
				static::handleForbidden();
				break;
			case 404: 
				static::handleNotFound();
				break;
		}
	}
	
	private static function handleBadRequest(){
		ob_end_clean();
		echo 'Stop trying to break things';
	}
	private static function handleForbidden(){
		ob_end_clean();
		echo "None shall pass";
	}
	/*
	 * In this case, we just ignore any of the page output, and instead only output the
	 * data returned by a page via Page->setResult(210,$alternateData).
	 */
	private static function handleUseAlternateData($data){
		ob_end_clean();
		echo $data;
	}
	
	private static function handleNotFound(){
		//ob_end_clean();
		static::handleSuccess();
		echo "not found";
	}
	
	private static function handleSuccess(){
		$text = ob_get_clean();
		echo $text;
	}
	
	private static function handleNotAuthorized(){
		ob_end_clean();
		header('Location: /login.php');
	}
	
	private static function handleTemporaryRedirect($url){
		ob_end_clean();
		header('Location: '.str_replace(array('\r','\n'),'',$url));
	}
}
?>