

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
			$('.debug').html(
				"NTP delay:" + this.ntpResults.roundTripDelay + 
				"<br />NTP offset:" + this.ntpResults.offset +
				"<br />corrected: " + (new Date((new Date()).valueOf() + this.ntpResults.offset))
			);
		}
	}
	$(document).ready(function(){
		var clientTime = new Date();

		
		setInterval("performNTP()",1000);
		setInterval("updateTime()",10);
	});
})();