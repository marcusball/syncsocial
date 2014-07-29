<?php
class FormKeyManager{
	private $hasReturnedKey = false;
	function __construct(){
	}
	
	public function getFormKey(){
		if(!$this->hasReturnedKey){ //This check will make sure a single page load can request a form key multiple times without it incrementing the counter
			if(!isset($_SESSION['form_key']) || !isset($_SESSION['form_key_uses'])){
				$_SESSION['form_key'] = $this->getNewFormKey();
				$_SESSION['form_key_uses'] = 0;
			}
			elseif($_SESSION['form_key_uses'] >= FORM_KEY_MAX_USES){
				$this->renewFormKey();
			}
			else{
				$_SESSION['form_key_uses'] += 1;
			}
			$this->hasReturnedKey = true;
		}
		return $_SESSION['form_key'];
	}
	
	//Primarily used to ensure form data passed during forwarding is valid
	//Example case is when newthread.php forwards back to previous URL with POST data containing result message
	public function getSingleUseFormKey(){
		return ($_SESSION['form_key_single'] = $this->getNewFormKey());
	}
	
	private function getOldFormKey(){
		if(isset($_SESSION['form_key_old'])){
			return $_SESSION['form_key_old'];
		}
		return null;
	}
	
	private function renewFormKey(){
		$_SESSION['form_key_old'] = $_SESSION['form_key'];
		$_SESSION['form_key'] = $this->getNewFormKey();
		$_SESSION['form_key_uses'] = 0;
	}
	
	public function getNewFormKey(){
		$bytes = openssl_random_pseudo_bytes(64);
		$formKey = substr(hash('sha256',$bytes),0,40);
		return $formKey;
	}
	
	public function isValidSingleUseFormKey($formKey){
		if($formKey == null){ return false; }
		if(isset($_SESSION['form_key_single'])){
			if($_SESSION['form_key_single'] == $formKey){
				unset($_SESSION['form_key_single']);
				return true;
			}
		}
		return false;
	}
	
	public function isValidFormKey($formKey){
		if($formKey == null){ return false; }
		if($formKey == $this->getFormKey()){ 
			$this->renewFormKey();
			return true;
		}
		else{
			if($formKey == $this->getOldFormKey()){ 
				//It's old, but it's the most recent old one.
				//For now, we'll accept this, because it will be less likely 
				//to screw with slightly stale pages/tabs on the client's browser.
				logWarning('Old form key used. Accepted for now.'); //We'll log it though
				unset($_SESSION['form_key_old']);
				return true; 
			}
		}
		return false;
	}
}
?>