angular.module('sm').controller("GenerateController", function($scope, globalKbdShortcuts, $http, $filter, localStorage, uiDayFactory) {
	
	
	//Check if we are forking a schedule
	if(localStorage.hasKey('forkSchedule')){
		
		// Get the schedule from sessions storage
		var forkSchedule = localStorage.getItem('forkSchedule');
		if(forkSchedule != null) {
			
			// Clear it so we don't fork again
			localStorage.setItem('forkSchedule', null);
			
			var days = uiDayFactory();
			
			// Init state
			$scope.initState();
			
			for(var i = forkSchedule.length; i--;) {
				var course = forkSchedule[i];
				
				// If it's a real course
				if(course.courseNum != 'non') {
					$scope.courseCart.create.fromExistingScheduleCourse(course);
				} else {
					
					// Make a non-course item
					var nonCourse = {
						title: course.title,
						days: [days[parseInt(course.times[0].day)]],
						startTime: parseInt(course.times[0].start),
						endTime: parseInt(course.times[0].end)
					};
					var mergedNonCourse = false;
					
					// Try to merge this non course with other similar ones
					for(var n = 0, l = $scope.state.nonCourses.length; n < l; n++) {
						var otherNonCourse = $scope.state.nonCourses[n];
						if(otherNonCourse.title == nonCourse.title &&
						   otherNonCourse.startTime == nonCourse.startTime &&
						   otherNonCourse.endTime == nonCourse.endTime) {
							otherNonCourse.days = otherNonCourse.days.concat(nonCourse.days);
							mergedNonCourse = true;
							break;
						}
					}
					
					if(!mergedNonCourse) {
						$scope.state.nonCourses.push(nonCourse);
					}
				}
				
			}
			
		}
		
	}
	
	// Decorate some course helpers for our dynamic items directive
	$scope.courses_helpers = {
		add: $scope.courseCart.create.blankCourse,
		remove: function(index) {
			$scope.courseCart.remove.byIndex(index - 1);
			if($scope.state.courses.length == 0) {
				$scope.courses_helpers.add();
			}
		},
	};

	$scope.ensureCorrectEndDay = function() {
		if($scope.state.drawOptions.startDay > $scope.state.drawOptions.endDay) {
			$scope.state.drawOptions.endDay = $scope.state.drawOptions.startDay;
		}
	};
	$scope.ensureCorrectEndTime = function() {
		if($scope.state.drawOptions.startTime >= $scope.state.drawOptions.endTime) {
			$scope.state.drawOptions.endTime = $scope.state.drawOptions.startTime + 60;
		}
	};
	
	$scope.numberOfPages = function() {
		return Math.ceil($scope.state.schedules.length / $scope.state.displayOptions.pageSize);
	};
	
	$scope.scrollToSchedules = function() {
		
		// I know this is bad, but I'm lazy
		setTimeout(function() {
			$('input:focus').blur();
			$('html, body').animate({
		        scrollTop: $("#master_schedule_results").offset().top - 65
		    }, 500);
		}, 100);
	};
	
	$scope.generationStatus = 'D';
	
	// Overwrite app-level generateController
    $scope.generateSchedules = function() {
    	
    	$scope.generationStatus = 'L';
    	
    	var requestData = {
    		'action': 'getMatchingSchedules',
    		'term': $scope.state.requestOptions.term,
    		'courseCount': $scope.state.courses.length,
    		'nonCourseCount': $scope.state.nonCourses.length,
    		'noCourseCount': $scope.state.noCourses.length
    	};
    	
    	// Set the actual number of courses being sent
    	var actualCourseIndex = 1;
    	
    	// Loop through the course cart
    	for(var courseIndex = 0; courseIndex < $scope.state.courses.length; courseIndex++) {
    		
    		// Set up our variables
    		var course = $scope.state.courses[courseIndex];
    		var fieldName = 'courses' + (actualCourseIndex) + 'Opt[]';
    		requestData['courses' + actualCourseIndex] = course.search;
    		requestData[fieldName] = [];
    		var sectionCount = 0;
    		
    		// Add selected sections to the request
    		for(var sectionIndex = 0; sectionIndex < course.sections.length; sectionIndex++) {
    			if(course.sections[sectionIndex].selected) {
    				requestData[fieldName].push(course.sections[sectionIndex].id);
    				sectionCount++;
    			}
    		}
    		
    		// If no sections are selected, remove the course info and decrease the actual course index
    		if(sectionCount == 0) {
    			requestData.courseCount--;
    			delete requestData['courses' + actualCourseIndex];
    			delete requestData[fieldName];
    		} else {
    			actualCourseIndex++;
    		}
    		
    	}
    	
    	// Set the request data for the non courses
    	for(var nonCourseIndex = 0; nonCourseIndex < $scope.state.nonCourses.length; nonCourseIndex++) {
    		var nonCourse = $scope.state.nonCourses[nonCourseIndex];
    		var index = (nonCourseIndex + 1);
    		var fieldName = 'nonCourse';
    		requestData[fieldName + 'Title' + index] = nonCourse.title;
    		requestData[fieldName + 'StartTime' + index] = nonCourse.startTime;
    		requestData[fieldName + 'EndTime' + index] = nonCourse.endTime;
    		requestData[fieldName + 'Days' + index + '[]'] = nonCourse.days;
    	}

    	// Set the request data for the no courses stuff
    	for(var noCourseIndex = 0; noCourseIndex < $scope.state.noCourses.length; noCourseIndex++) {
    		var noCourse = $scope.state.noCourses[noCourseIndex];
    		var index = (noCourseIndex + 1);
    		var fieldName = 'noCourse';
    		requestData[fieldName + 'StartTime' + index] = noCourse.startTime
    		requestData[fieldName + 'EndTime' + index] = noCourse.endTime;
    		requestData[fieldName + 'Days' + index + '[]'] = noCourse.days;
    	}
    	
    	// Actually make the request
    	$http.post('/js/scheduleAjax.php',$.param(requestData), {
	    	requestType:'json',
	    	headers: {
	            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
	        }
	    }).success(function(data, status, headers, config) {
	    	$scope.generationStatus = 'D';
	    	
	    	// If no errors happened
	    	if(!data.error && !data.errors) {
	    		
	    		// Check if any schedules were generated
		    	if(data.schedules == undefined || data.schedules == null || data.schedules.length == 0) {
		    		$scope.resultError = 'There are no matching schedules!';
		    	} else {
		    		
		    		// Otherwise reset page, scroll to schedules and clear errors
			    	$scope.state.displayOptions.currentPage = 0;
			    	$scope.scrollToSchedules();
			    	$scope.state.schedules = data.schedules;
			    	$scope.resultError =  '';
		    	}

	    	} else if(!data.error && data.errors) {
	    		
	    		// Display errors
	    		$scope.resultError = data.errors.reduce(function(totals, error){return totals + ', ' + error.msg;}, '');
	    		console.log("Schedule Generation Errors:", data);
	    	} else {
	    		
	    		// Display errors
	    		$scope.resultError = data.msg;
	    		console.log("Schedule Generation Error:", data);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	$scope.generationStatus = 'D';
	    	// Display errors
	    	$scope.resultError =  'Fatal Error: An internal server error occurred';
	    	console.log("Fatal Schedule Generation Error:", data);
	    });
    };
    
    // Bind keyboard shortcuts
    globalKbdShortcuts.bindCtrlEnter($scope.generateSchedules);
    
    // Bind arrow key pagination
    globalKbdShortcuts.bindPagination(function() {
    	if (this.keyCode == 39 && $scope.state.displayOptions.currentPage + 1 < $scope.numberOfPages()) {
    		$scope.state.displayOptions.currentPage++;
    		$scope.scrollToSchedules();
    	} else if(this.keyCode == 37 && $scope.state.displayOptions.currentPage - 1 >= 0) {
    		$scope.state.displayOptions.currentPage--;
    		$scope.scrollToSchedules();
    	}
    });
    
    // If the previous page set to generate schedules
    if($scope.state.ui.action_generateSchedules) {
    	$scope.state.ui.action_generateSchedules = false;
    	$scope.generateSchedules();
    }
    
    $scope.resetGenerate = function() {
    	
    };
});
