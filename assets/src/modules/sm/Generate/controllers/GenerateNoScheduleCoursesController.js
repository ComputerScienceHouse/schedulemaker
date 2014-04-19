angular.module('sm').controller('GenerateNoCourseItemsController', function($scope) {
	
	$scope.addNoC = function() {
		$scope.state.noCourses.push({
			startTime: '',
			endTime: '',
			days: []
		});
	};
	
	$scope.removeNoC = function(index) {
		$scope.state.noCourses.splice(index, 1);
	};
	
	$scope.ensureCorrectEndTime = function(index) {
		if($scope.state.noCourses[index].startTime >= $scope.state.noCourses[index].endTime) {
			$scope.state.noCourses[index].endTime = $scope.state.noCourses[index].startTime + 60;
		}
	};
});