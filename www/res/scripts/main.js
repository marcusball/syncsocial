

(function(){
	this.ntpResults = {};
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
			$('.debug').html(
				"NTP delay:" + this.ntpResults.roundTripDelay + 
				"<br />NTP offset:" + this.ntpResults.offset +
				"<br />corrected: " + date + 
				"<br />number: " + this.makeBetterNumber(this.hash(date.valueOf()))
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
		$('form.create_event').submit(function(){
			if($(this).hasClass('event_active')){
				return;
			}
			$.ajax({
				type:'post',
				url:'index.php',
				data: $(this).serialize(),
				success: function(data,textStatus,jqXHR){
					if(data.status == 'much-success'){
						$(this).addClass('event_active');
						alert('it begins');
					}
					else if(data.status == 'very-fail'){
						alert('something bad happened');
					}
				}
			});
		});
	}
	
	$(document).ready(function(){
		var clientTime = new Date();

		
		setInterval("performNTP()",1000);
		setInterval("updateTime()",100);
		
		hookForm();
	});
})();