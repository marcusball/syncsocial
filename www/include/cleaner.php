<?php 
/*******************************************/
/* This script requires HTMLPurifier.      */
/* Download it from //htmlpurifier.org/    */
/* Place htmlpurifier folder in /include/  */
/*******************************************/

require_once 'htmlpurifier/HTMLPurifier.auto.php';

class Cleaner{
	private static $purifier;
	
	/*
	 * Cleans a body of text that will contain Html output. 
	 * $body: the text to clean
	 * $textOnly: if true, the only tags allowed will be text-based ones; 
	 *            if false, tags like img and video will be permitted (depends on configuration)
	 */
	public static function cleanHtmlText($body,$textOnly = false){
		$config = self::getConfig(); //Gets an HTMLPurifier config
		if(!$textOnly){ //If we're allowing multimedia
			$config->set('HTML.Allowed', 'p,em,strong,a[href],img[src],pre');
		}
		else{
			$config->set('HTML.Allowed', 'p,em,strong,a[href]');
		}
		$config->set('AutoFormat.AutoParagraph', true);
		
		$cleaner = self::getPurifier($config); //Get a purifier using this config. 
		
		return $cleaner->purify($body); //Perform cleanse! 
	}
	
	/* 
	 * Cleans a body of text that will permit no HTML tags.
	 * $body: text to clean
	 */
	public static function cleanPlainText($body){
		$config = self::getConfig(); //Gets an HTMLPurifier config
		$config->set('HTML.Allowed', ''); //No tags at all
		
		$cleaner = self::getPurifier($config);
		return $cleaner->purify($body);
	}
	
	/*
	 * Cleans a string that will be used with a name field.
	 */
	public static function cleanNameString($name){ //Be sure to double check with the regex used in validation.php if modifying this!		
		$name = self::cleanPlainText($name); //Remove all HTML
		
		$invalidNameCharacters = '#[^\p{L}\p{Mn}\p{Pd}\'\s]#u'; 
		$name = preg_replace($invalidNameCharacters,'',$name); //Remove all invalid name characters
		
		//Remove other excess symbols and spaces
		$toClean = array(
			'/\s{2,}/', //two or more whitespace characters
			'/\p{Pd}+/u', //dash punctuation, one or more
			'/\.{2,}/' //two or more periods
		);
		$replaceWith = array(
			' ', //a single space
			'-', //a single minus sign
			'.'  //a single period
		);
		$name = preg_replace($toClean,$replaceWith,$name);
		
		return $name; 
	}
	
	/*
	 * Get an HTMLPurifier config object.
	 * Returns the config stored in self::$config.
	 * If self::$config is null, it will create a new HTMLPurifier_Config and assign it to self::$config.
	 */
	private static function getConfig(){
		$config = HTMLPurifier_Config::createDefault();
		$config->set('URI.Base', '//'.SITE_DOMAIN);
		$config->set('URI.MakeAbsolute', true);
		$config->set('HTML.Allowed', '');
		return $config;
	}
	
	/*
	 * Get an HTMLPurifier object.
	 * If $config IS NOT supplied, it will use the purifier in self::$purifier.
	 * If self::$purifier is null, it will create a new one using the return from self::getConfig().
	 * If $config IS supplied, it will (always) create a new HTMLPurifier object using that configuration. 
	 */ 
	private static function getPurifier(HTMLPurifier_Config $config = null){
		if($config !== null){ //If an HTMLPurifier config is supplied. 
			self::$purifier = new HTMLPurifier($config);
		}
		else{ //otherwise we'll use the previously used purifier, or a new one using the previous config. 
			if(self::$purifier === null){
				self::$purifier = new HTMLPurifier(self::getConfig());
			}
		}
		return self::$purifier;
	}
}

?>
