<?php
class IndexPage extends PageObject{
	public function pageTitle(){
		echo "Welcome to the template home page!";
	}
	
	public function preExecute(){
		if($this->issetReq('clientTime')){
			header('application/json');
			die(json_encode(array('receiptTimestamp'=>(string) round(getStartTime() * 1000),'responseTimestamp'=>(string) round(microtime(true) * 1000))));
		}
		elseif($this->issetReq('start')){
			$time = time() + 60; //1 minute from now
			if($this->dbCon->createNewEvent($time,$_SERVER['REMOTE_ADDR'])){
				header('application/json');
				die(json_encode(array('status'=>'much-success','wow'=>'such-created','time'=>$time)));
			}
			else{
				header('application/json');
				die(json_encode(array('status'=>'very-fail')));
			}
		}
	}
	
	public function createBody(){?>
		<span class="debug"></span>
		<div class="box"></div>
		<form class="event_create">
			<input type="submit" name="start" value="LET'S DO THIS" />
		</form>
	<?php
	}
}
?>