<?php
require_once 'emailvalidate.php';
class Validator{
	public static function validLength($input,$max,$min = 0){
        $len = strlen($input);
        if($len > $max) return false;
        if($len < $min) return false;
        return true;
    }
	
	/*
	 * Checks if an email appears to be valid.
	 * $email: The emaid address to test
	 * $maxLen: the maximum length we will allow for the email string
	 * $minLen: the smallest allowable length for the email string
	 * $clean: if true, we will also trim, strip_tags, stripslashes, etc. 
	 * Note: This will not shorten an email that is above maxLen.
	 */
	public static function validEmail($email, $maxLen, $minLen = 6,$clean = true){
		if(!is_null($email)){
			if($clean){
				$email = @strip_tags($email);	
				$email = @stripslashes($email);
				$email = trim($email);	
			}
			if(self::validLength($email,$maxLen,$minLen) && validEmail($email)){
				return $email;
			}
		}
		return false;
	}
	
	/* 
	 * Checks if a name appears to be valid. Allows most unicode characters
	 * so as to permit names from other regions and cultures. Only denys names
	 * with a select set of unicode values.
	 * $name: the name to test
	 * $maxLen: the maximum length for the name
	 * $minLen: the minimum length for the name (default 3)
	 * $clean: when true, it will replace some occurrences of duplicate characters
	 * return: false if not valid, string (representing $name) if valid.
	 */
	public static function validName($name, $maxLen, $minLen = 3,$clean = true){
		//$invalidChars = '/[\u0000-\u0020\!\"\#\$\%\&\(\)\*\+\,0-9:;\<\=\>\?\@\[\\\]\^_\`\{\|\}\~\u00A0-\u00BF]/u';
		$validName = '~^(?:[\p{L}\p{Mn}\p{Pd}\']++\s?)++$~u'; //don't forget to double check with the regex used in cleaner.php
		if(preg_match($validName,$name) !== 1){
			debug('name does not match acceptable pattern');
			return false;
		}
		if(!self::validLength($name,$maxLen,$minLen)){
			debug('name is not valid length');
			return false;
		}
		if($clean){
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
		}
		return $name;
	}
}

?>