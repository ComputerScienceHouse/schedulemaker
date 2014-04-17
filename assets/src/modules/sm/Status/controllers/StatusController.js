angular.module('sm').controller('StatusController', function($scope, $http) {
	
	$scope.logs = [];
	
	$http({
		method: 'GET',
		url: '/api/status',
		headers: {
			'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
		}, 
		withCredentials: true
	}).success(function(data, status, headers, config) {
		if(status == 200 && ! data.error) {
			$scope.logs = data;
		} else {
			
			//TODO: Better error checking 
			alert(scope.error);
		}
	});
});