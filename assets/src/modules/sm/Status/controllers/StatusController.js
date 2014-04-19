angular.module('sm').controller('StatusController', function($scope, $http) {
	
	$scope.logs = [];
	
	$http.get('/status')
	.success(function(data, status, headers, config) {
		if(status == 200 && ! data.error) {
			$scope.logs = data;
		} else {
			
			//TODO: Better error checking 
			alert(scope.error);
		}
	});
	
	$scope.timeConvert = function(UNIX_timestamp){
	 var a = new Date(+UNIX_timestamp*1000);
	 var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
	     var year = a.getFullYear();
	     var month = months[a.getMonth()];
	     var date = a.getDate();
	     var hour = a.getHours();
	     var min = a.getMinutes();
	     var sec = a.getSeconds();
	     if(sec <= 10) sec = "0" + sec;
	     if(min <= 10) min = "0" + min;
	     var time = month+' '+date+' '+year+' '+hour+':'+min+':'+sec ;
	     return time;
	 }


});