angular.module('sm').controller('GenerateNonCourseItemsController', function($scope) {
	
	$scope.addNonC = function() {
		$scope.state.nonCourses.push({
			title: '',
			startTime: '',
			endTime: '',
			days: []
		});
	};
	
	$scope.removeNonC = function(index) {
		$scope.state.nonCourses.splice(index, 1);
	};
	
	$scope.ensureCorrectEndTime = function(index) {
		if($scope.state.nonCourses[index].startTime >= $scope.state.nonCourses[index].endTime) {
			$scope.state.nonCourses[index].endTime = $scope.state.nonCourses[index].startTime + 60;
		}
	};
});