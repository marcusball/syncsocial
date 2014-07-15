<?php
class IndexPage extends PageObject{
	public function pageTitle(){
		echo "Welcome to the template home page!";
	}
	
	public function preExecute(){
		if($this->issetReq('clientTime')){
			die(json_encode(array('receiptTimestamp'=>(string) round(getStartTime() * 1000),'responseTimestamp'=>(string) round(microtime(true) * 1000))));
		}
	}
	
	public function createBody(){?>
		<span class="debug"></span>
	<?php
	}
}
?>