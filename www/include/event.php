<?php
class Event{
	public $eid;
	public $time;
	public $ip;
	public $diff;
	
	public function __construct(){
		if(isset($this->time)){
			$this->time = strtotime($this->time);
		}
	}
}
?>