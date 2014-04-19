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
});