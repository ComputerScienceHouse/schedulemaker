angular.module('sm').controller("ScheduleController", function($scope, parsedSchedule) {
	
	if(!parsedSchedule.error) {
		if(parsedSchedule.hasOwnProperty('courses')) {
	
			$scope.schedule = parsedSchedule.courses;
			
		} else if(parsedSchedule.hasOwnProperty('schedule')) {
	
			$scope.schedule = parsedSchedule.schedule;
			
		} else {
	
			$scope.schedule = [];
		}
	} else {
		$scope.schedule = [];
	}

	if($scope.schedule.length > 0) {
		
		// Set the correct draw options
		for(var key in $scope.state.drawOptions) {
			$scope.state.drawOptions[key] = parsedSchedule[key];
		}
	
		// Set image property
		if(parsedSchedule.hasOwnProperty('image')) {
			$scope.imageSupport = parsedSchedule.image;
		} else {
			$scope.imageSupport = true;
		}
	
		// Set the correct term
		$scope.state.requestOptions.term = +parsedSchedule.term;
	
		// Don't save these state settings
		$scope.noStateSaveOnUnload();
	}
});