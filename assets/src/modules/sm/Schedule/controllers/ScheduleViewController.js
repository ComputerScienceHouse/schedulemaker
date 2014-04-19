angular.module('sm').controller("ScheduleViewController", function($scope, $location, $stateParams) {
	
	id = $stateParams.id;
	$scope.saveInfo = {
		url: $location.absUrl(),
		id: id
	};
	
});