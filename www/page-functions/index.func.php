<?php
class IndexPage extends PageObject{
	public function pageTitle(){
		echo "Welcome to the template home page!";
	}
	
	public function preExecute(){
		$this->user->getGuestUid();
		
		if($this->issetReq('clientTime')){
			header('application/json');
			die(json_encode(array('receiptTimestamp'=>(string) round(getStartTime() * 1000),'responseTimestamp'=>(string) round(microtime(true) * 1000))));
		}
		elseif($this->issetReq('infoPoll')){
			header('application/json');
			$event = $this->dbCon->getActiveEvent(EVENT_ACTIVE_EXPIRATION);
			$return = array();
			if($event !== false){
				$return['active'] = true;
				$return['time'] = $event->time;
				$return['id'] = $event->eid;
				$return['count'] = $this->dbCon->getAttendeeCount($event->eid);
			}
			else{
				$return['active'] = false;
			}
			echo json_encode($return);
			return false;
		}
		elseif($this->issetReq('start')){
			$time = time() + 60; //1 minute from now
			
			header('application/json');
			if(($event = $this->dbCon->getActiveEvent(EVENT_ACTIVE_EXPIRATION)) !== false){ //There is already an active event
				die(json_encode(array('status'=>'too-late','time'=>$event->time)));
			}
			
			if($this->dbCon->createNewEvent($time,$_SERVER['REMOTE_ADDR'])){
				$newEvent = $this->dbCon->getActiveEvent();
				if(!$this->user->isLoggedIn()){
					$gUid = $this->user->getGuestUid();
					$join = $this->dbCon->guestJoinEvent($gUid,$newEvent->eid);
				}
				die(json_encode(array('status'=>'much-success','wow'=>'such-created','time'=>$newEvent->time)));
			}
			else{
				die(json_encode(array('status'=>'very-fail')));
			}
		}
		elseif($this->issetReq('join')){
			$event = $this->dbCon->getActiveEvent(5);
			$status = 'fail';
			if(!$this->user->isLoggedIn()){
				$gUid = $this->user->getGuestUid();
				if($event !== false){ 
					$join = $this->dbCon->guestJoinEvent($gUid,$event->eid);
					if($join !== false){
						$status = 'totes-joined';
					}
				}
			}
			$count = $this->dbCon->getAttendeeCount($event->eid);
			die(json_encode(array('status'=>$status,'time'=>$event->time,'count'=>$count)));
		}
	}
	
	public function createBody(){?>
		<span class="debug"></span>
		<div class="box"></div>
		<div class="event"></div>
		<form class="event_create">
			<input type="submit" name="start" value="LET'S DO THIS" />
		</form>
		
		<form class="event_join">
			<input type="submit" name="start" value="Join" />
		</form>
	<?php
	}
}
?>