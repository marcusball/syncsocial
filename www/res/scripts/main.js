

(function(){
	this.ntpResults = {};
	this.eventTime = null;
	this.timerActive = false;
	this.eventId = 0;
	this.eventCount = 0;
	this.alertedEventId = 0;
	
	this.ntp = function ntp(t0, t1, t2, t3){
		return{
			roundTripDelay: (t3 - t0) - (t2 - t1),
			offset: ((t1 - t0) + (t2 - t3)) / 2
		}
	}

	this.performNTP = function performNTP(){
		var t0 = (new Date()).valueOf();
		var parent = this;
		$.ajax({
			type:'POST',
			url:'/index.php',
			data:{
				'clientTime':t0
			},
			contentType:'application/x-www-form-urlencoded',
			dataType:'json',
			success: function(data,textStatus,jqXHR){
				
				t3 = (new Date()).valueOf();
				t1 = data.receiptTimestamp;
				t2 = data.responseTimestamp;
				parent.ntpResults = ntp(t0,t1,t2,t3);
				//console.log(this.ntpResults);
			}
		});
	}

	this.updateTime = function updateTime(){
		//console.log(this.ntpResults);
		if('roundTripDelay' in this.ntpResults){
			date = (new Date((new Date()).valueOf() + this.ntpResults.offset));
			
			diff = null;
			if(this.timerActive == true){
				a = moment(date);
				b = moment.unix(this.eventTime);
				diff = b.diff(a,'seconds',true);
				
				if(diff <= 0){
					this.timerActive = false;
					this.eventTime = null;
					
					if(this.alertedEventId != this.eventId){
						this.alertedEventId = this.eventId;
						alert("DO STUFF"); 
					}
				}
			}
			
			
			$('.debug').html(
				"NTP delay:" + this.ntpResults.roundTripDelay + 
				"<br />NTP offset:" + this.ntpResults.offset +
				"<br />corrected: " + date + 
				"<br />number: " + this.makeBetterNumber(this.hash(date.valueOf())) + 
				((this.timerActive)?("<br />Countdown: " + diff):"") + 
				"<br />count: " + this.eventCount
			);
			
			$('.box').css({
				'width':'100px',
				'height':'100px',
				'background-color':this.createColor(date.valueOf())
			}).html(this.createColor(date.valueOf()));
			
		}
	}
	
	this.makeBetterNumber = function makeBetterNumber(number){
		number = number / 100000000;
		return (number) - Math.floor(number);
	}
	
	this.hash = function hash(a){
		a = (a+0x7ed55d16) + (a<<12);
		a = (a^0xc761c23c) ^ (a>>19);
		a = (a+0x165667b1) + (a<<5);
		a = (a+0xd3a2646c) ^ (a<<9);
		a = (a+0xfd7046c5) + (a<<3);
		a = (a^0xb55a4f09) ^ (a>>16);
		if( a < 0 ) a = 0xffffffff + a;
		return a;
	}
	
	this.createColor = function createColor(seed){
		var letters = '0123456789ABCDEF'.split('');
		var color = '#';
		for (var i = 0; i < 6; i++ ) {
			color += letters[Math.floor(this.makeBetterNumber(this.hash(seed * i)) * 16)];
		}
		return color;
	}
	
	this.hookForm = function hookForm(){
		var parent = this;
		$('form.event_create').submit(function(e){
			e.preventDefault();
			if($(this).hasClass('event_active')){
				return;
			}
			$.ajax({
				type:'post',
				url:'index.php',
				data: {'start':'oh yeah'},
				dataType:'json',
				success: function(data,textStatus,jqXHR){
					if(data.status == 'much-success'){
						$(this).addClass('event_active');
						parent.timerActive = true;
						parent.eventTime = data.time;
						alert('it begins');
					}
					else if(data.status == 'very-fail'){
						alert('something bad happened');
					}
				}
			});
		});
		
		$('form.event_join').submit(function(e){
			alert('trying');
			e.preventDefault();
			if($(this).hasClass('has_joined')){
				return;
			}
			$.ajax({
				type:'post',
				url:'index.php',
				data: {'join':':D'},
				dataType:'json',
				success: function(data,textStatus,jqXHR){
					console.log(data);
					if(data.status == 'totes-joined'){
						$(this).addClass('has_joined');
					}
				}
			});
		});
	}
	
	this.pollForInfo = function pollForInfo(){
		var parent = this;
		$.ajax({
			type:'post',
			url:'index.php',
			data: {'infoPoll':'yespls'},
			dataType:'json',
			success: function(data,textStatus,jqXHR){
				if(data.active == true){
					parent.eventTime = data.time;
					parent.timerActive = true;
					parent.eventId = data.id;
					parent.eventCount = data.count;
				}
				else{
					parent.timerActive = false;
					parent.eventTime = null;
					parent.eventId = 0;
					parent.eventCount = 0;
				}
			}
		});
	}
	
	$(document).ready(function(){
		var clientTime = new Date();

		performNTP();
		updateTime();
		pollForInfo();
		
		setInterval("performNTP()",5000);
		setInterval("updateTime()",100);
		setInterval("pollForInfo()",2000);
		
		hookForm();
	});
})();