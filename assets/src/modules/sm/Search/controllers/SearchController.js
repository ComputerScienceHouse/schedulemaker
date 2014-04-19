/**
 * The controller holding all the logic for the search page
 */
angular.module('sm').controller('SearchController', function($scope, $http, entityDataRequest, globalKbdShortcuts) {
	
	var defaultOptions = {
		college: {id: "any", code: "any", number: null, title: "Any College"},
		department: {id: "any", code: "any", number: null, title: "Any Department"}
	};
	
	$scope.searchResults = [];
	
	$scope.search =  {
		params: {},
		options: {
			colleges: [defaultOptions.college],
			departments: [defaultOptions.department]
		}
	};
	
	// Init the search parmeters without changing their object identity
	($scope.initSearch = function() {
			var sP = $scope.search.params;
			sP.term = $scope.state.requestOptions.term;
			sP.college = "any";
			sP.department = "any";
			sP.level = "any";
			sP.credits = "";
			sP.professor = "";
			sP.daysAny = true;
			sP.days = [];
			sP.timesAny = true;
			sP.times = {
				'morn': false,
				'aftn': false,
				'even': false
			};
			sP.online = true;
			sP.honors = true;
			sP.offCampus = true;
			
			sP.title = "";
			sP.description = "";
	})();
	
	
	var reloadSchoolsForTerm = function(newTerm, oldTerm) {		
		
		if(newTerm == oldTerm) return;
		
		// Set the new term in our params
		$scope.search.params.term = newTerm;
		
		// Reset our selected options
		$scope.search.params.college = "any";
		$scope.search.params.department = "any";
		
		// Get a list of schools for the term
		entityDataRequest.getSchoolsForTerm({term: newTerm})
		.success(function(data, status) {
			if(status == 200 && typeof data.error == 'undefined') {
				
				// Push the default to the top and set it as the option list
				data.unshift(defaultOptions.college);
				$scope.search.options.colleges = data;
			} else if(data.error) {
				
				// TODO: Better error checking
				alert(data.msg);
			}
		});
	};
	reloadSchoolsForTerm($scope.state.requestOptions.term, '');
	
	// Listen for term changes
	$scope.$watch('state.requestOptions.term', reloadSchoolsForTerm);
	
	// Reload the departments when a college is selected
	$scope.$watch('search.params.college', function(newCollege) {
		
		if(newCollege != "" && newCollege !="any") {
			
			// Reset selected department
			$scope.search.params.department = "any";
			
			// Get a list of departments
			entityDataRequest.getDepartmentsForSchool({
				term: $scope.search.params.term, 
				param: newCollege
			}).success(function(data, status) {
				
				if(status == 200 && typeof data.error == 'undefined') {
					
					// Push the default to the top and set it as the option list
					data.departments.unshift(defaultOptions.department);
					$scope.search.options.departments = data.departments;
				} else if(data.error) {
					
					// TODO: Better error checking
					alert(data.msg);
				}
			});
		} else if ($scope.search.options.departments.length > 1) {
			
			// Reset if there were more than one options already out
			$scope.search.options.departments = [defaultOptions.department];
		}
	});
	
	// 'D'one loading
	$scope.searchStatus = "D";
	
	$scope.findMatches = function() {
		
		// Only search if a current search is not in progress 
		if($scope.searchStatus == "L") return;
		
		
		// 'L'oading
		$scope.searchStatus = "L";
		
		var params = angular.copy($scope.search.params);
	
		// Remove uneeded data
		if(params.timesAny == true) {
			delete params['times'];
		} else {
			var times = [];
			for(var time in params.times) {
				if(params.times[time] == true) {
					times.push(time);
				}
			}
			
			if(times.length == 0) {
				delete params['times'];
			} else {
				params.times = times;
			}
		}
		delete params['timesAny'];
		
		if(params.daysAny == true || params.days.length == 0) {
			delete params['days'];
		}
		delete params['daysAny'];
		
		
		$http.post('/search/find', $.param(params))
		.success(function(data, status) {	
			// 'D'one loading
			$scope.searchStatus = "D";
			
			if(status == 200 && typeof data.error == 'undefined') {
				
				// Set the results
				$scope.searchResults = data;
				
				// Reset to the first page and scroll
				$scope.searchPagination.currentPage = 0;
				$scope.scrollToResults();
				
				// Remove any errors if they were present
				if($scope.resultError) {
					$scope.resultError = null;
				}
			} else if(data.error) {
				
				$scope.resultError = data.msg;
				
				// Clear result
				$scope.searchResults = [];
			}
		});
	};
	
	$scope.searchPagination = {
		pageSize: 10,
		currentPage: 0
	};
	
	$scope.numberOfPages = function() {
		return Math.ceil($scope.searchResults.length / $scope.searchPagination.pageSize);
	};
	
	$scope.$watch('searchPagination.pageSize', function(newSize, oldSize) {
		if(newSize != oldSize) {
			var numPages  = $scope.numberOfPages();
			if($scope.searchPagination.currentPage > numPages) {
				$scope.searchPagination.currentPage = numPages - 1;
			}
		} 
	});
	
	$scope.scrollToResults = function() {
		
		// Again, I know this is bad, but I'm lazy
		setTimeout(function() {
			$('input:focus').blur();
			$('html, body').animate({
		        scrollTop: $("#search_results").offset().top - 65
		    }, 500);
		}, 100);
	};
	
	globalKbdShortcuts.bindCtrlEnter($scope.findMatches);
    globalKbdShortcuts.bindPagination(function() {
    	if (this.keyCode == 39 && $scope.searchPagination.currentPage + 1 < $scope.numberOfPages()) {
    		$scope.searchPagination.currentPage++;
    		$scope.scrollToResults();
    	} else if(this.keyCode == 37 && $scope.searchPagination.currentPage - 1 >= 0) {
    		$scope.searchPagination.currentPage--;
    		$scope.scrollToResults();
    	}
    });
});