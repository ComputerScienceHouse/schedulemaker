angular.module('sm').controller("BrowseController", function($scope, entityDataRequest) {
	
	$scope.schools = [];
	
	$scope.$watch('state.requestOptions.term', function(newTerm) {
		entityDataRequest.getSchoolsForTerm({term: newTerm}).success(function(data, status) {
			if(status == 200 && typeof data.error == 'undefined') {
				$scope.schools = data;
			} else if(data.error) {
				// TODO: Better error checking
				alert(data.msg);
			}
		});
	});
});