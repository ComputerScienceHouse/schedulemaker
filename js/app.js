var app = angular.module( 'sm', ['ngAnimate', 'ngSanitize'] );
//For now, not a service
app.filter('RMPUrl', function() {
	return function(input) {
		if(input && input != "TBA") {
			var nameParts = input.split(" "),
			lastName = nameParts[nameParts.length - 1];
			return '<a target="_blank" href="http://www.ratemyprofessors.com/SelectTeacher.jsp?searchName=' + lastName + '&search_submit1=Search&sid=807">' + input + '</a>';
		} else {
			return '<a href="#">' + input + '</a>';
		}
	}
});
app.filter('formatTime', function() {
	return function(minutes) {
		minutes = minutes % 1440;

		// Figure out how many hours
		var hours = Math.floor(minutes / 60);

		// Figure out how many minutes
		var remMinutes = minutes % 60;

		// Correct for AM/PM
		var ampm;
		if(hours >= 12) {
		    ampm = "pm";
		    hours -= 12
		} else {
		    ampm = "am";
		}

		// Correct for 0 hour
		if(hours == 0) {
		    hours = 12;
		}

		// Correct minutes less than 10 min
		if(remMinutes < 10) {
		    remMinutes = "0" + remMinutes;
		}
		// Put it together
		return hours + ":" + remMinutes + ampm;
	}
});

app.filter('startFrom', function() {
    return function(input, start) {
        start = +start; //parse to int
        return input.slice(start);
    }
});

app.filter('partition', function($cacheFactory) {
	var arrayCache = $cacheFactory('partition')
	return function(arr, size) {
		var parts = [], cachedParts,
		jsonArr = JSON.stringify(arr);
		for (var i=0; i < arr.length; i += size) {
			parts.push(arr.slice(i, i + size));        
		}
		cachedParts = arrayCache.get(jsonArr); 
		if (JSON.stringify(cachedParts) === JSON.stringify(parts)) {
			return cachedParts;
		}
		arrayCache.put(jsonArr, parts);

		return parts;
	}; 
});

app.filter("parseTime", function() {
	return function(rawTime) {
		var matchedTime = rawTime.match(/([0-9]|1[0-2]):([0-9]{2})(am|pm)/);
		if(matchedTime) {
		    if(matchedTime[3] == 'am' && parseInt(matchedTime[1]) == 12) {
		        return parseInt(matchedTime[2]);
		    } else if(matchedTime[3] == 'pm') {
		    	matchedTime[1] = parseInt(matchedTime[1]) + 12;
		    }
		    return (parseInt(matchedTime[1]) * 60) + parseInt(matchedTime[2]);
		} else {
			return false;
		}
	};
});

app.filter("courseNum", function() {
	return function(course) {
		if(course) {
			return (course.department.code? course.department.code:
				course.department.number) + "-" + course.course;
		}
	};
});

app.factory('debounce', ['$timeout', function ($timeout) {
    return function(fn, timeout, apply){
        timeout = angular.isUndefined(timeout) ? 0 : timeout;
        apply = angular.isUndefined(apply) ? true : apply; // !!default is true! most suitable to my experience
        var nthCall = 0;
        return function(){ // intercepting fn
            var that = this;
            var argz = arguments;
            nthCall++;
            var later = (function(version){
                return function(){
                    if (version === nthCall){
                        return fn.apply(that, argz);
                    }
                };
            })(nthCall);
            return $timeout(later, timeout, apply);
        };
    };
}]);

app.factory("sessionStorage", function($window) {
	
	var sessionStorage = $window.sessionStorage;
	
	return {
		setItem: function(key, value) {
			if(sessionStorage) {
				setTimeout(function() {
					sessionStorage.setItem(key, angular.toJson(value));
				}, 10);
			} else {
				return false;
			}
		},
		getItem: function(key) {
			if(sessionStorage) {
				return angular.fromJson(sessionStorage.getItem(key));
			} else {
				return false;
			}
		},
		clear: function() {
			if(sessionStorage) {
				return sessionStorage.clear();
			} else {
				return false;
			}
		},
	};
});

app.controller("AppCtrl", function($scope, sessionStorage, debounce, $window, $filter) {
	
	$scope.initState = function() {
		$scope.state = {};
		$scope.state.courses = [];
		$scope.state.courseMap = {};
		$scope.state.nonCourses = [];
		$scope.state.noCourses = [];
		$scope.state.schedules =[];
		$scope.state.drawOptions = {
			start_time: 480,
			end_time: 1320,
			start_day: 1,
			end_day: 6,
			building_style: 'code'
		};

		$scope.state.displayOptions = {
			currentPage: 0,
			pageSize: 3,
			fullscreen: false
		};

		$scope.state.requestOptions = {
			term: +$scope.defaultTerm,
			ignoreFull: false
		};
		
		$scope.state.ui = {
			betaHide: false
		};
	};
	$scope.resetState = function() {
		$scope.initState();
		$window.location.reload();
	};
	
	var storedState = sessionStorage.getItem('state');
	if(storedState != null) {
		$scope.state = storedState;
		
		
	} else {
		$scope.initState();
		
	}
	
	$scope.saveState = debounce(function() {
		console.log('saving state');
		sessionStorage.setItem('state', $scope.state);
	}, 2000, false);
	
	
	$window.onbeforeunload = function() {
		
		// Force save on close
		sessionStorage.setItem('state', $scope.state);
	};
	
	var courseNumFilter = $filter('courseNum');
	
	// Course cart tools for non-generate pages.
	$scope.courseCart = {
		nextId: 0,
		init: function() {
			// Reset id if loaded from state
			if($scope.state.courses.length > 0) {
				$scope.courseCart.nextId = $scope.state.courses[$scope.state.courses.length - 1].id + 1;
			}
		},
		count: {
			all: {
				selectedSections: function() {
					var count = 0;
					for(var i = 0; i < $scope.state.courses.length; i++) {
						count += $scope.courseCart.count.course.
							selectedSections($scope.state.courses[i]);
					}
					return count;
				},
			},
			course: {
				selectedSections: function(course) {
					var count = 0;
					for(var i = 0; i < course.sections.length; i++) {
						if(course.sections[i].selected) count++;
					}
					return count;
				}
			}
		},
		selection: {
			all: {
				
				/**
				 * Unselects everything in the cart
				 */
				unselect: function() {
					for(var i = 0; i < $scope.state.courses.length; i++) {
						for(var s = 0; 
							s < $scope.state.courses[i].sections.length; s++) {
							$scope.state.courses[i].sections[s].selected = 
								false;
						}
					}
				}
			},
			section: {
				
				/**
				 * Toggle the selection status of a section, but check if its
				 * course is in the cart already, if not, add it.
				 * 
				 * @param course {Object} The course the section belongs to
				 * @param section {Object} The section to toggle
				 */
				toggleByCourse: function(course, section) {
					if(!$scope.courseCart.contains.course(course)) {
						$scope.courseCart.create.fromExistingCourse(course);
					} else {
						if(course.selected && section.selected) {
							course.selected = false;
						}
					}
					section.selected = !section.selected;
				},
				
				/**
				 * Toggle the selected status of a selection
				 * 
				 * Pre-condition: The section is already in the cart
				 * 
				 * @param section {Object} The section to toggle
				 */
				toggle: function(section) {
					section.selected = !section.selected;
				},
				
				/**
				 * Checks if section is selected, but first checks if course is
				 * in the cart -- if not, add it.
				 * 
				 * @param course {Object} The course the section belongs to
				 * @param section {Object} The section to toggle
				 */
				isByCourse: function(course, section) {
					return $scope.courseCart.contains.course(course) && 
						section.selected;
				}
			},
			course: {
				/**
				 * Toggles the current course's sections selcted state 
				 */
				toggle: function(course) {
					
					// If new this load or not
					var Ecourse = $scope.courseCart.ensure.course(course);

					course.selected = !$scope.courseCart.selection.course
						.toggleAllSections(Ecourse);
				},
				
				is: function(course) {
					if($scope.courseCart.contains.course(course)) {
						return $scope.courseCart.selection.course
						.allSections($scope.courseCart.ensure.course(course));
					} else {
						return false;
					}
				},
				
				/**
				 * Toggles all sections in the course
				 * 
				 * Pre-condition: the course exists in the cart
				 */
				toggleAllSections: function(course) {
					var setTo = !$scope.courseCart.selection.course
						.allSections(course);
					course.sections.forEach(function(section) {
						section.selected = setTo;
					});
					
					return setTo;
				},
				
				/**
				 * Returns true if all sections are selected
				 */
				allSections: function(course) {
					return course.sections.reduce(
						function(total, section) { 
							return total && section.selected;
					});
				},
				
				/**
				 * Unselects all sections
				 */
				unselect: function(course) {
					course.sections.forEach(function(section) {
						section.selected = false;
					});
				}
			}
		},
		
		ensure: {
			/**
			 * Gets the course for the selected id
			 * 
			 *  @param id {String} The course id
			 */
			course: function(course) {
				
				if($scope.courseCart.contains.course(course)) {
					var foundCourse = false;
					for(var i = 0; i < $scope.state.courses.length; i++) {
						if(course.id == $scope.state.courses[i].id) {
							foundCourse = $scope.state.courses[i];
							break;
						}
					}
					return foundCourse;
				} else {
					
					if($scope.state.courses.indexOf(course) != -1) {
						return course;
					} else {
						return $scope.courseCart.create
							.fromExistingCourse(course);
					}
					
					return false;
				}
			}
		},
		
		contains: {
			
			/**
			 * Checks if the provided course is in the cart
			 * @param course {Object} The course to check
			 * @returns {Boolean} The course is in the cart?
			 */
			course: function(course) {
				return $scope.state.courseMap.hasOwnProperty(course.id);
			}
		},
		remove: {
			
			/**
			 * Remove a course completely by index
			 * @param index The index to remove
			 */
			byIndex: function(index) {
				$scope.state.courses.splice(index, 1);
			}
		},
		add: {
			sectionToCourse: function(section, course) {
				//TODO: impliment this
			},
			
			/**
			 * Add a pre-created course to the cart
			 * @param course An already formatted course ready for adding
			 * @returns {Object} The created course
			 */
			courseToCart: function(course) {
				$scope.state.courses.push(course);
				$scope.$broadcast('addedCourse');
				
				return course;
			}
		},
		create: {
			
			/**
			 * Creates and adds a new blank course and the adds it to the cart
			 * 
			 * @returns {Object} The newly created course
			 */
			blankCourse: function() {
				 return $scope.courseCart.add.courseToCart(
					 $scope.courseCart.getBlankCourse(true));
			},
			
			/**
			 * Creates and adds a pre-existing course from the database to the
			 * cart
			 * 
			 * @param existingCourse {Object} A course from the database
			 * @returns {Object} The newly created course
			 */
			fromExistingCourse: function(course) {
				var mockCourse = $scope.courseCart.getBlankCourse(false);
				
				course.fromSelect = false;
				course.search = courseNumFilter(course);
				$scope.state.courseMap[course.id] = true;
				return $scope.courseCart.add.courseToCart(course);
			}
		},
		
		/**
		 * Returns a POJO with a correct id and other features
		 * 
		 * @param fromSelect
		 * @returns {Object} A new course
		 */
		getBlankCourse: function(fromSelect) {
			return {
				id: ++$scope.courseCart.nextId,
				search: '',
				sections: [],
				status: 'D',
				fromSelect: fromSelect
			};
		}
			
	};
	$scope.courseCart.init();
	
	$scope.ui = {
		optionLists: {
			days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
			times: {
				keys: [0, 60, 120, 180, 240, 300, 360, 420, 480, 540, 600, 660, 720, 780, 840, 900, 960, 1020, 1080, 1140, 1200, 1260, 1320, 1380, 1440],
				values: {
					'': 'Choose',
					0: '12:00am',
					60: '1:00am',
					120: '2:00am',
					180: '3:00am',
					240: '4:00am',
					300: '5:00am',
					360: '6:00am',
					420: '7:00am',
					480: '8:00am',
					540: '9:00am',
					600: '10:00am',
					660: '11:00am',
					720: '12:00pm',
					780: '1:00pm',
					840: '2:00pm',
					900: '3:00pm',
					960: '4:00pm',
					1020: '5:00pm',
					1080: '6:00pm',
					1140: '7:00pm',
					1200: '8:00pm',
					1260: '9:00pm',
					1320: '10:00pm',
					1380: '11:00pm',
					1440: '12:00am'
				}
			}
		},
		colors: ["#B97D9C",
		         "#629E6D",
		         "#D47F55",
		         "#8C9AC3",
		         "#D07A7B",
		         "#808591",
		         "#4D9F9E",
		         "#A37758",
		         "#87944F",
		         "#B29144",
		        ]
	};
});

app.controller("GenerateCtrl", function($scope, globalKbdShortcuts, $http, $filter) {
	
	$scope.courses_helpers = {
		add: $scope.courseCart.create.blankCourse,
		remove: function(index) {
			$scope.courseCart.remove.byIndex(index - 1);
			if($scope.state.courses.length == 0) {
				$scope.courses_helpers.add();
			}
		},
	};

	$scope.colorSearch = function(search) {
		var foundColor = null;
		$scope.state.courses.forEach(function(e) {
			if(e.search.search(search) >= 0) {
				foundColor =  e.color;
			}
		});
		return foundColor;
	};
	$scope.ensureCorrectEndDay = function() {
		if($scope.state.drawOptions.start_day > $scope.state.drawOptions.end_day) {
			$scope.state.drawOptions.end_day = $scope.state.drawOptions.start_day;
		}
	};
	$scope.ensureCorrectEndTime = function() {
		if($scope.state.drawOptions.start_time >= $scope.state.drawOptions.end_time) {
			$scope.state.drawOptions.end_time = $scope.state.drawOptions.start_time + 60;
		}
	};
	/*days: {
		0: "Sunday",
		1: "Monday",
		2: "Tuesday",
		3: "Wednesday",
		4: "Thursday",
		5: "Friday",
		6: "Saturday"
	}*/
	
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
    $scope.generateSchedules = function() {
    	var requestData = {
    		'action': 'getMatchingSchedules',
    		'term': $scope.state.requestOptions.term,
    		'courseCount': $scope.state.courses.length,
    		'nonCourseCount': $scope.state.nonCourses.length,
    		'noCourseCount': $scope.state.noCourses.length
    	};
    	
    	for(var courseIndex = 0; courseIndex < $scope.state.courses.length; courseIndex++) {
    		var course = $scope.state.courses[courseIndex];
    		var fieldName = 'courses' + (courseIndex + 1) + 'Opt[]';
    		requestData['courses' + (courseIndex + 1)] = course.search;
    		requestData[fieldName] = [];
    		for(var sectionIndex = 0; sectionIndex < course.sections.length; sectionIndex++) {
    			if(course.sections[sectionIndex].selected) {
    				requestData[fieldName].push(course.sections[sectionIndex].id);
    			}
    		}
    		
    	}
    	
    	var formatTime = $filter('formatTime');
    	
    	for(var nonCourseIndex = 0; nonCourseIndex < $scope.state.nonCourses.length; nonCourseIndex++) {
    		var nonCourse = $scope.state.nonCourses[nonCourseIndex];
    		var index = (nonCourseIndex + 1);
    		var fieldName = 'nonCourse';
    		requestData[fieldName + 'Title' + index] = nonCourse.title;
    		requestData[fieldName + 'StartTime' + index] = formatTime(nonCourse.start_time); // This is dumb
    		requestData[fieldName + 'EndTime' + index] = formatTime(nonCourse.end_time);
    		requestData[fieldName + 'Days' + index + '[]'] = nonCourse.days;
    	}
    	
    	for(var noCourseIndex = 0; noCourseIndex < $scope.state.noCourses.length; noCourseIndex++) {
    		var noCourse = $scope.state.noCourses[noCourseIndex];
    		var index = (noCourseIndex + 1);
    		var fieldName = 'noCourse';
    		requestData[fieldName + 'StartTime' + index] = formatTime(noCourse.start_time); // This is still dumb
    		requestData[fieldName + 'EndTime' + index] = formatTime(noCourse.end_time);
    		requestData[fieldName + 'Days' + index + '[]'] = noCourse.days;
    	}
    	
    	$http.post('./js/scheduleAjax.php',$.param(requestData), {
	    	requestType:'json',
	    	headers: {
	            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
	        }
	    }).success(function(data, status, headers, config) {
	    	if(!data.error) {
	    		for(var i = 0; i < data.schedules.length; i++) {
	    			data.schedules[i].number = i + 1;
		    	}
		    	$scope.state.schedules = data.schedules;
		    	$scope.state.displayOptions.currentPage = 0;
		    	$scope.scrollToSchedules();
	    	} else {
	    		alert('There will be better error handling, but this happened: \n'+data.msg);
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	course.status = 'D';
	    // Most likely typed too fast
	    });
    	
    	/*
    	// Serialize the form and store it if it changed
        var form = $("#scheduleForm");
    	if(serialForm != form.serialize()) {
    		serialForm = form.serialize();
    		
    		// Clear out the schedules and errors
    		$("#schedules").find("> :not(:first-child)").remove();

    		// Now we need to submit all the data to the ajax caller
    		$.post("./js/scheduleAjax.php", $('#scheduleForm').serialize(), function(data) {
                var scheduleDiv = $("#schedules");

    			// If there was a single, non-recoverable error, show it and die
    			if(data.error != null && data.error != undefined) {
    				$("<div>").attr("id", "errorDiv")
    						.addClass("scheduleError")
    						.html("<b>Fatal Error: </b>" + data.msg)
    						.appendTo(scheduleDiv);
    				scheduleDiv.slideDown();
    				return;
    			}

    			// Store the data for pagination later
    			$scope.state.schedules = data.schedules;
    			if(data.errors != null && data.errors != undefined) {
    				errorDiv = $("<div id='errorDiv' class='scheduleWarning'>");
    				var errorHTML = "<div class='subheader'><h3>Schedule Generator Warnings</h3><input id='errorControl' type='button' value='Collapse' onClick='collapseErrors();' /></div>";
    				errorHTML = "<div class='subheader'><h3>Schedule Generator Warnings</h3><input id='errorControl' type='button' value='Collapse' onClick='collapseErrors();' /></div>";
    				errorHTML += "<div id='errorContents'>";
    				for(var i = 0; i < data.errors.length; i++) {
    					errorHTML += data.errors[i].msg + "<br />";
    				}
    				errorHTML += "</div>";
    				errorDiv.html(errorHTML);
    				$('#schedules').append(errorDiv);
    			}
    			// If there are no matching schedules, display an error
    			if(data.schedules == undefined || data.schedules == null || data.schedules.length == 0) {
    				var errorDiv = $("<div id='errorDiv' class='scheduleError'>").html("There are no matching schedules!");
    				scheduleDiv.append(errorDiv);
    				scheduleDiv.slideDown();
    				return;
    			}
    			// Unhide the schedules page
    			scheduleDiv.show();
    			$scope.$broadcast('generatedSchedules');
    			
    			$scope.scrollToSchedules();
    			
    		}).error( function() {
                var scheduleDiv = $("#schedules");
    			var errorDiv = $("<div>");
    			errorDiv.attr("id", "errorDiv");
    			errorDiv.addClass("scheduleError");
    			errorDiv.html("Fatal Error: An internal server error occurred");
    			errorDiv.appendTo(scheduleDiv);
    			scheduleDiv.slideDown();
    		});
    		
    	}*/
    };
    globalKbdShortcuts.bindGenerateSchedules($scope.generateSchedules);
    globalKbdShortcuts.bindPagination(function() {
    	if (this.keyCode == 39 && $scope.state.displayOptions.currentPage + 1 < $scope.numberOfPages()) {
    		$scope.state.displayOptions.currentPage++;
    		$scope.scrollToSchedules();
    	} else if(this.keyCode == 37 && $scope.state.displayOptions.currentPage - 1 >= 0) {
    		$scope.state.displayOptions.currentPage--;
    		$scope.scrollToSchedules();
    	}
    });
});

app.controller( "MainMenuCtrl", function( $scope) {
  $scope.path = window.location.pathname;
});

app.controller( "scheduleCoursesCtrl", function( $scope, $http, $q, $timeout) {
  if($scope.state.courses.length == 0) {
	  $scope.courses_helpers.add();  
  }
  var canceler = {};
  $scope.search = function(course) {
	  if (canceler.hasOwnProperty(course.id)) {
		  canceler[course.id].resolve();
	  }
      canceler[course.id] = $q.defer();
      course.status = 'L';
	    var searchRequest = $http.post('./js/scheduleAjax.php',$.param({
    		'action'     : 'getCourseOpts',
            'course'     : course.search,
            'term'       : $scope.state.requestOptions.term,
            'ignoreFull' : $scope.state.requestOptions.ignoreFull
	    }), {
	    	requestType:'json',
	    	headers: {
	            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
	        }, 
	        timeout: canceler[course.id].promise
	    }).success(function(d, status, headers, config) {
	    	course.status = 'D';
	    	if(!d.error) {
		    	for(var c = 0; c < d.length; ++c) {
		    		
		            d[c].isError = false;
		            d[c].selected = true;
		            
		        }
		    	course.sections = d;
	    	} else {
	    		course.sections = [{isError:true,error:d}];
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    	course.status = 'D';
	    // Most likely typed too fast
	    });
  };
  $scope.$watch('state.requestOptions', function(newRO, oldRO) {
	  if(angular.equals(newRO, oldRO)) {
		  return;
	  }
	for(var i = 0, l = $scope.state.courses.length; i < l; i++) {
		var course = $scope.state.courses[i];
		if(course.search.length > 3) {
			$scope.search(course);
		}
	  }
  }, true);
  $scope.$watch('state.displayOptions.pageSize', function(newPS, oldPS) {
	  if(newPS === oldPS) {
		  return;
	  }
	  if($scope.state.displayOptions.currentPage + 1 > $scope.numberOfPages()) {
		  $scope.state.displayOptions.currentPage = $scope.numberOfPages() - 1;
	  }
  });
  $scope.$watch('state.courses', function(newCourses, oldCourses) {
	for(var i = 0, l = newCourses.length; i < l; i++){
		var newCourse = newCourses[i],
			oldCourse = oldCourses.filter(function (filterCourse) {
				return filterCourse.id === newCourse.id;
			})[0];
		if(typeof oldCourse === 'undefined') {
			oldCourse = {
				search: '',
				sections: []
			};
		}
		if(newCourse.search != oldCourse.search && newCourse.search.length > 5) {
			$scope.search(newCourse);
		} else if(newCourse.search != oldCourse.search) {
			newCourse.sections = [];
			if (canceler.hasOwnProperty(newCourse.id)) {
				canceler[newCourse.id].resolve();
				newCourse.status = 'D';
			}
		}
	}
  }, true);
});

app.controller('nonCourseItemsCtrl', function($scope) {
	
	$scope.addNonC = function() {
		$scope.state.nonCourses.push({
			title: '',
			start_time: '',
			end_time: '',
			days: []
		});
	};
	
	$scope.removeNonC = function(index) {
		$scope.state.nonCourses.splice(index, 1);
	};
	
	$scope.ensureCorrectEndTime = function(index) {
		if($scope.state.nonCourses[index].start_time >= $scope.state.nonCourses[index].end_time) {
			$scope.state.nonCourses[index].end_time = $scope.state.nonCourses[index].start_time + 60;
		}
	};
});

app.controller('noCourseItemsCtrl', function($scope) {
	
	$scope.addNoC = function() {
		$scope.state.noCourses.push({
			start_time: '',
			end_time: '',
			days: []
		});
	};
	
	$scope.removeNoC = function(index) {
		$scope.state.noCourses.splice(index, 1);
	};
	
	$scope.ensureCorrectEndTime = function(index) {
		if($scope.state.noCourses[index].start_time >= $scope.state.noCourses[index].end_time) {
			$scope.state.noCourses[index].end_time = $scope.state.noCourses[index].start_time + 60;
		}
	};
});


app.directive('professorLookup', function($http) {
	return {
		restrict: 'A',
		scope: {
			professorLookup:'='
		},
		template: '{{professorLookup}}',
		link: {
			pre: function(scope, elm, attrs) {
				
			},
			post: function(scope, elm, attrs) {
				if(scope.professorLookup != '' && scope.professorLookup != 'TBA') {
					scope.stats = 'none';
					elm.on('click', function() {
						var nameParts = scope.professorLookup.split(" "),
						lastName = nameParts[nameParts.length - 1];
						if(scope.stats == 'none') {
							$http({
								method:'GET',
								url:'js/rmp.php?professor='+lastName,
								headers: {
									'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
								}, 
								withCredentials: true
							}).success(function(data, status, headers, config) {
								var parser = new DOMParser();
								var doc = parser.parseFromString(data,"text/html");
								var entry = doc.querySelectorAll('#ratingTable .entry')[0];
								var getStat = function(selector) {
									return entry.querySelectorAll(selector)[0].innerHTML;
								};
								var getUrl = function() {
									return 'http://www.ratemyprofessors.com/ShowRatings.jsp?tid=' + entry.querySelectorAll('.profName a')[0].href.split('?tid=')[1];
								};
								var ratingColor = function(score) {
									score = parseFloat(score);
									if(score >= 4) {
										return '#18BC9C';
									} else if(score >= 3) {
										return '#F39C12';
									} else {
										return '#E74C3C';
									}
								}
								scope.stats = {
									name: getStat('.profName a'),
									url: getUrl(),
									dept: getStat('.profDept'),
									numRatings: getStat('.profRatings'),
									rating: getStat('.profAvg'),
									easiness: getStat('.profEasy'),
								};
								elm.popover({
									html:true,
									trigger:'manual',
									placement:'auto left',
									title: '<a target="_blank" href="'+scope.stats.url+'">'+scope.stats.name+' - '+scope.stats.dept+'</a>',
									content: '<div class="row"><div class="col-xs-6 rmp-rating"><h2 style="background-color:'+ratingColor(scope.stats.rating)+'">'+scope.stats.rating+'</h2>Average Rating</div><div class="col-xs-6 rmp-rating"><h2 style="background-color:'+ratingColor(scope.stats.easiness)+'">'+scope.stats.easiness+'</h2>Easiness</div></div><div style="text-align:center">Based on '+scope.stats.numRatings+' ratings<br><a target="_blank" href="http://www.ratemyprofessors.com/SelectTeacher.jsp?searchName='+lastName+'&search_submit1=Search&sid=807">Not the right professor?</a><br><small>&copy; 2013 <a target="_blank" href="http://www.ratemyprofessors.com">RateMyProfessors.com</a></small></div>'
								});
								elm.popover('show');
								
						    });
						} else {
							elm.popover('toggle');
						}
					});
				}
			}
		}
	};
});

app.factory('uiDayFactory', function() {
	var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
	
	return function() {
		return days;
	};
});
app.directive("courseCart", function() {
	return {
		restrict: 'A',
		templateUrl: '/js/templates/cart.html'
	};
});
app.directive("dowSelectFields", function(uiDayFactory) {
	return {
		restrict: 'A',
		scope: {
			select: '=dowSelectFields'
		},
		template: '<div class="btn-group btn-group-dow"><button type="button" ng-repeat="day in days" ng-click="toggle(day)" class="btn btn-default btn-dow" ng-class="{\'btn-success\':isSelected(day)}" ng-bind="day.substring(0 ,2)"></button></div>',
		link: {
			pre: function(scope) {
				scope.days = uiDayFactory();
				scope.isSelected = function(day) {
					return scope.select.indexOf(day) != -1;
				};
				scope.toggle = function(day) {
					var index = scope.select.indexOf(day);
					if(index == -1) {
						scope.select.push(day);
					} else {
						scope.select.splice(index, 1);
					}
				};
			}
		}
	};
});

app.directive("scheduleCourse", function(){
	  return {
	    restrict: "C",
	    templateUrl: './js/templates/courseselect.html',
	  };
});
app.directive("dynamicItems", function($compile,$timeout, globalKbdShortcuts){
	  return {
	    restrict: "A",
	    scope: {
	    	'dynamicItems': '=',
	    	'useClass':'@',
	    	'helpers':'=',
	    	'colors': '='
	    },
	    controller: function($scope) {
	    	this.items = $scope.dynamicItems;
	    	this.add = $scope.helpers.add;
	    	this.remove = $scope.helpers.remove;
	    },
	    compile: function(telm, tattrs) {
	    	return {
	    		pre: function(scope, elm, attrs) {
                    scope.$parent.$on('addedCourse',function() {
                        $timeout(function() {
                            elm.find('input.searchField:last').focus();
                        }, 0, false);
                    });
		    		elm.append($compile('<div class="'+scope.useClass+' repeat-item" ng-repeat="item in dynamicItems" dynamic-item ng-if="item.fromSelect"></div>')(scope));
	    		},
	    		post: function(scope, elm, attrs) {
	    			globalKbdShortcuts.bindSelectCourses(function() {
	    				if(elm.find("input.searchField:focus").length == 0) {
	            			$('html, body').animate({
	            		        scrollTop:0
	            		    }, 500, null, function() {
	            		    	elm.find('input.searchField:first').focus();
	            		    });
	    				}
	    			
	    			});
	    		}
	    	};
	    }
	  };
});

app.directive("dynamicItem", function($timeout){
  return {
    restrict: "A",
    require: '^dynamicItems',
    link: { pre: function(scope, elm, attrs, dynamicItems) {
    		scope.$watch('$index', function(newVal) {
    			scope.index =  newVal + 1;
    	        if(scope.index == 1) {   
    	            $timeout(function() {
    	            	elm.addClass('no-repeat-item-animation');
    	                elm.find("input.searchField").focus();
    	            }, 0, false);
    	        }
    		});
	    	
	        scope.remove = function() {
            	if(scope.index == 1 && dynamicItems.items.length == 1) {
            		elm.removeClass('no-repeat-item-animation');
            	}
            	dynamicItems.remove(scope.index);
	        };
    	}, post: function(scope, elm, attrs, dynamicItems) {
	        var ident = 'input.searchField',
	        input = elm.find(ident);
	        var doKeystrokeAnalysis = function(e) {
	        	kbdResult = true;
	            if(e.keyCode == 13 && !e.ctrlKey) {
	                if(dynamicItems.items.length == scope.index) {
	                	dynamicItems.add();
                        $timeout(function() {
                            elm.next().find(ident).focus();
                        }, 0, false);
	                } else {
	                    elm.next().find(ident).focus();
	                }
	            } else if(e.keyCode == 27) {
	                e.preventDefault();
	                if(scope.index > 1) {
                    	elm.prev().find(ident).focus();
	                } else {
	                	var parent = elm.parent();
	                	$timeout(function() {
	                		parent.find(ident+":first").focus();
                        }, 0, false);
	                }
                    scope.remove();  
	            } else if(e.keyCode == 38 && e.ctrlKey && !e.altKey) {
	                e.preventDefault();
	                if(scope.index > 1) {
                    	elm.prev().find(ident).focus();
	                } 
	            } else if(e.keyCode == 40 && e.ctrlKey &&! e.altKey) {
	                if(scope.index < dynamicItems.items.length) {
                    	elm.next().find(ident).focus();
                    	e.preventDefault();
	                } 
	            } else if(e.keyCode == 38 && e.ctrlKey && e.altKey) {
	            	scope.showResults = false;
	            	kbdResult = false;
	            } else if(e.keyCode == 40 && e.ctrlKey && e.altKey) {
	                scope.showResults = !scope.showResults;
	                kbdResult = false;
	            } else if (e.ctrlKey && e.altKey && e.keyCode > 48 && e.keyCode < 57) {
	            	if(scope.item.sections.length > 0) {
		            	var index = e.keyCode - 49;
		            	var resultElm = scope.item.sections[index];
		            	if(resultElm) {
		            		scope.item.sections[index].selected = !scope.item.sections[index].selected;
		            	}
	            	}
	            } else if (e.ctrlKey && e.altKey && e.keyCode == 65) {
	            	if(scope.item.sections.length > 0) {
	            		var total = 0;
	            		for(var i = 0; i < scope.item.sections.length; i++) {
	            			if(scope.item.sections[i].selected) {
	            				total++;
	            			}
	            		}
	            		if(total == scope.item.sections.length) {
	            			var target = false;
	            		} else {
	            			var target = true;
	            		}
	            		for(var i = 0; i < scope.item.sections.length; i++) {
	            			scope.item.sections[i].selected = target;
	            		}
	            	}
	            }
	        };
	        
            input.blur(function(e) {
                e.preventDefault();
            });
            
	        input.keydown(function(e) {
	        	scope.$apply(doKeystrokeAnalysis(e));
	        	return kbdResult;
	        });
            $timeout(function() {
                elm.find("input.searchField").focus();
            }, 0, false);
    	}
    }
  };
});

app.directive('schedulePagination', function() {
	return {
		restrict: 'A',
		scope: {
			displayOptions: '=schedulePagination',
			totalLength: '=totalLength',
			schedulePaginationCallback: '&'
		},
		template: '<button class="btn btn-default" ng-disabled="displayOptions.currentPage == 0" ng-click="displayOptions.currentPage=displayOptions.currentPage-1">Previous</button>' +
				  ' {{displayOptions.currentPage+1}}/{{numberOfPages()}} ' +
		          '<button class="btn btn-default" ng-disabled="displayOptions.currentPage >= totalLength/displayOptions.pageSize - 1" ng-click="displayOptions.currentPage=displayOptions.currentPage+1">Next</button>',
		link: {
			pre: function(scope) {
				scope.numberOfPages = function() {
					return Math.ceil(scope.totalLength / scope.displayOptions.pageSize);
				};
			},
			post: function(scope, elm, attrs) {
				if(scope.schedulePaginationCallback) {
					elm.find('button').click(function() {
						scope.schedulePaginationCallback();
					});
				}
			}
		}
	};
});

app.directive('pinned', function() {
	return {
		restrict: 'A',
		link: function(scope, elm, attrs) {
			elm.affix();
		}
	};
});

app.directive('schedule', function($timeout, $filter) {
	function Schedule(scope) {
		this.scope = scope;
		this.drawOptions = {
			parsedTime: {}
		};
		this.courseDrawIndex = 0;
	}
	Schedule.prototype.init = function() {
		
		this.drawOptions.parsedTime.start = parseInt(this.scope.state.drawOptions.start_time);
		this.drawOptions.parsedTime.end = parseInt(this.scope.state.drawOptions.end_time);
		if(!this.drawOptions.parsedTime.start || !this.drawOptions.parsedTime.end) return false;
        
		this.scope.hiddenCourses = [];
		this.scope.onlineCourses = [];
		this.scope.scheduleItems = [];
		
		return true;
	};
	Schedule.prototype.drawGrid = function() {
		
		var hourArray = [];
        for(var time = this.drawOptions.parsedTime.start; time < this.drawOptions.parsedTime.end; time += 60) {
    		// Calculate the label
    		var hourLabel = Math.floor(time / 60);
    		if(hourLabel > 12) { hourLabel -= 12; }
    		else if(hourLabel == 0) { hourLabel = 12; }

    		if(time >= 720) { ap = " PM"; } else { ap = " AM"; }	
    		
    		hourArray.push(String(hourLabel) + ap);
    	}

		// Generate grid
        var numDays = this.scope.state.drawOptions.end_day - this.scope.state.drawOptions.start_day + 1;
		// Set up grid
		var rawHeight = (hourArray.length * 40),
		globalOpts = {
			height: rawHeight + 25,
			hoursWidth: 5
		},
		rawDayWidth = 100 / numDays,
		dayPadding = 1,
		dayOpts = {
			num: numDays,
			rawWidth: rawDayWidth,
			width: (rawDayWidth - (globalOpts.hoursWidth / numDays) - (2 * dayPadding)) + '%',
			padding: dayPadding,
			height: rawHeight
		};
		
		var dayArray = [];
		//Generate days
		
		var dayIndex = this.scope.state.drawOptions.start_day;
		for(var i=0; i < numDays; i++) {
			var offset = globalOpts.hoursWidth + ( 2 * dayOpts.padding) + ((dayOpts.rawWidth - dayOpts.padding) * i);
			dayArray.push({
				name: this.scope.ui.optionLists.days[dayIndex],
				offset: offset + '%',
			});
			dayIndex++;
		}
		
        
		//Set the this.scope variable
		this.scope.grid = {
			hours: hourArray,
			days: dayArray,
			opts: {
				height: globalOpts.height,
				hoursWidth: globalOpts.hoursWidth,
				daysWidth: dayOpts.width,
				daysHeight: dayOpts.height,
				pixelAlignment:''
			}
		};
		return true;
	};
	
	Schedule.prototype.drawCourse = function(course, index) {
		var grid = this.scope.grid;
		var startTime = this.drawOptions.parsedTime.start;
		var endTime = this.drawOptions.parsedTime.end;
		
		// Using the old logic here because it works just as good as anything
		
		for(var t = 0; t < course.times.length; t++) {
			// Make it easier for the developer
			var time = course.times[t];
			// Skip times that aren't part of the displayed days
			if(time.day < this.scope.state.drawOptions.start_day || time.day > this.scope.state.drawOptions.end_day) {
				if($.inArray(course.courseNum, this.scope.hiddenCourses) == -1) {
					this.scope.hiddenCourses.push(course.courseNum);
				}
				continue;
			}
			
			var courseStart = time.start,
			courseEnd = time.end,
			shorten = 0;

			// Skip times that aren't part of the displayed hours
			if(courseStart < startTime || courseStart > endTime || courseEnd > endTime) {
				// Shorten up the boxes of times that extend into
				// the visible spectrum
				if(courseStart < startTime && courseEnd > startTime) {
					courseStart = startTime;
					shorten = -1;
				} else if(courseEnd > endTime && courseStart < endTime) {
					courseEnd = endTime;
					shorten = 1;
				} else {
					// The course is completely hidden
					if($.inArray(course.courseNum, this.scope.hiddenCourses) == -1) {
						this.scope.hiddenCourses.push(course.courseNum);
					}
					continue;
				}
			}
			
			// Calculate the height
			var timeHeight = parseInt(courseEnd) - parseInt(courseStart);
			timeHeight = timeHeight / 30;
			timeHeight = Math.ceil(timeHeight);
			timeHeight = (timeHeight * 20);

			// Calculate the top offset
			var timeTop = parseInt(courseStart) - startTime;
			timeTop = timeTop / 30;
			timeTop = Math.floor(timeTop);
			timeTop = timeTop * 20;
			timeTop += 19;					// Offset for the header
			
			if(course.courseNum != 'non') {
				var location = ((this.scope.state.drawOptions.building_style == 'code') ? time.bldg.code : time.bldg.number) + "-" + time.room,
				instructor = course.instructor,
				courseNum = course.courseNum;
			} else {
				var location = '',
				instructor = '',
				courseNum = '';
			}
			this.scope.scheduleItems.push({
				title:course.title,
				content: {
				    location: location ,
				    courseNum: courseNum,
				    instructor: instructor
				},
				boundry: {
					x: grid.days[time.day - this.scope.state.drawOptions.start_day].offset,
					y: timeTop,
					shorten: shorten,
					width: grid.opts.daysWidth,
					height:timeHeight
				},
				color: this.scope.ui.colors[this.courseDrawIndex % 10]
			});
			
		}
	};
	
	Schedule.prototype.drawCourses = function() {
		this.courseDrawIndex = 0;
		for(var coursesIndex = 0, coursesLength = this.scope.schedule.length; coursesIndex < coursesLength; coursesIndex++) {
			var course = this.scope.schedule[coursesIndex];
			this.courseDrawIndex++;
			if(course.online) {
				this.scope.onlineCourses.push(course);
			} else if(course.times != undefined) {
				this.drawCourse(course);
			}
		}
	};
	
	Schedule.prototype.draw = function() {
		this.drawGrid()
		this.drawCourses();
	};

	return {
		restrict: 'A',
		templateUrl: './js/templates/schedule.html',
		link: {
			pre: function(scope, elm, attrs) {
				scope.scheduleController = new Schedule(scope);
				scope.itemEnter = function($event) {
					$target = $($event.target);
					$scope = $target.scope();
					if($scope.item.boundry.height < 70 && $scope.item.courseNum) {
						$scope.item.boundry.orig_height = $scope.item.boundry.height;
						$scope.item.boundry.height = 70;
					}
				};
				scope.itemLeave = function($event) {
					$target = $($event.target);
					$scope = $target.scope();
					if($scope.item.boundry.orig_height) {
						$scope.item.boundry.height = $scope.item.boundry.orig_height;
					}
				};
			},
			post: function(scope, elm) {
				scope.$watchCollection('state.drawOptions', function() {	
					if(scope.scheduleController.init()) {
						// Only redraw if valid options
						scope.scheduleController.draw();
						
						// Fix the pixel issues after DOM updates
						$timeout(function() {
							var offset = elm.find("svg").offset(),
							vert = 1 - parseFloat('0.'+('' + offset.top).split('.')[1]);
							horz = 1 - parseFloat('0.'+('' + offset.left).split('.')[1]);
							scope.grid.opts.pixelAlignment ='translate('+horz+','+vert+')';
						},0,true);
					}
				});
			}
		}
	};
});

app.directive('svgTextLine', function() {
	return {
		link: function(scope, elm, attrs) {
			var text = attrs.svgTextLine;
			if(scope.grid.days.length > 3) {
				if(text.length > 16) {
					element = elm.get(0);
					element.setAttribute("textLength", (parseFloat(scope.grid.opts.daysWidth) + 1 )+ "%");
					element.setAttribute("lengthAdjust", "spacingAndGlyphs");
				}
				if(text.length > 25) {
					text = text.slice(0, 22) + '...';
				}
			}
			elm.text(text);
		}
	};
});
app.factory('globalKbdShortcuts', function($rootScope) {
	var globalKbdShortcuts = {
		'bindGenerateSchedules': function(callback) {
			Mousetrap.bind('mod+enter', function(e) {
			    $rootScope.$apply(callback);
			    return true;
			});
			
			// Only allow to bind once, so mock function after first use
			this.bindGenerateSchedules = function() {};
		},
		'bindPagination': function(callback) {
			Mousetrap.bind('mod+right', function(e) {
			    $rootScope.$apply(callback.apply(e));
			    return true;
			});
			Mousetrap.bind('mod+left', function(e) {
			    $rootScope.$apply(callback.apply(e));
			    return true;
			});
			
			// Only allow to bind once, so mock function after first use
			this.bindPagination = function() {};
		},
		'bindSelectCourses': function(callback) {
			Mousetrap.bind('mod+down', function(e) {
			    callback();
			    return false;
			});
			
			// Only allow to bind once, so mock function after first use
			this.bindSelectCourses = function() {};
		},
	};
	return globalKbdShortcuts;
});


app.filter('parseSectionTimes', function($filter) {
	var translateDay = $filter('translateDay');
	return function(times, byLocation) {
		if(typeof times != 'object') return times;
		var parsedTimes = [];
		for(var e = 0; e < times.length; ++e) {
            // Search the existing list of times to see if a match exists
            var found = false;
            var time = times[e];
            
            if(byLocation) {
        		time.location =  time.building.code
                + ": " + time.building.number + " "
                + "-" + time.room;
        	} else {
        		time.location = false;
        	}
            
            for(var f = 0; f < parsedTimes.length; ++f) {
                if(parsedTimes[f].start == time.start && parsedTimes[f].end == time.end && parsedTimes[f].location == time.location) {
                    found = f;
                }
            }

            // If a match was found, add the day to it, otherwise add a new time
            if(found !== false) {
            	parsedTimes[found].days += ", " + translateDay(time.day);
            } else {
            	parsedTimes.push({
                    start: time.start,
                    end:   time.end,
                    days:  translateDay(time.day),
                    location: time.location
                });
            }
        }
		return parsedTimes;
	};
});

app.filter('translateDay', function() {
	return function(day) {
    // Modulo it to make sure we get the correct days
    day = day % 7;

    // Now switch on the different days
    switch(day) {
        case 0:
            return "Sun";
        case 1:
            return "Mon";
        case 2:
            return "Tue";
        case 3:
            return "Wed";
        case 4:
            return "Thu";
        case 5:
            return "Fri";
        case 6:
            return "Sat";
        default:
            return null;
    }
}
});


// BROWSE PAGE
app.controller("BrowseCtrl", function($scope, browseRequest) {
	$scope.schools = [];
	
	/*$scope.toggleDisplay = function(type, $event) {
		var scope = angular.element($event.target).scope();
		if(typeof scope.expanded == 'undefined') {
			scope.expanded = true;
		} else {
			scope.expanded = !scope.expanded;
		}
	};*/
	$scope.cart = {
		items: {},
		sortOrder: [],
		courses: {},
		length: 0,
		sectionInCart: function(section) {
			return  $scope.cart.items.hasOwnProperty(section.id);
		},
		courseInCart: function(course) {
			if(!$scope.cart.courses.hasOwnProperty(course.id)) {
				return false;
			} else {
				var isInCart = true;
				angular.forEach(course.sections, function(section) {
					isInCart = isInCart && $scope.cart.sectionInCart(section);
				});
				if(isInCart) {
					return true;
				} else {
					delete $scope.cart.courses[course.id];
					return false;
				}
			}
		},
		addSection: function(section) {
			$scope.cart.length++;
			$scope.cart.items[section.id] = section;
			$scope.cart.sortOrder.push(section.id);
			section.selected = true;
		},
		removeSection: function(section) {
			$scope.cart.length--;
			delete $scope.cart.items[section.id];
			$scope.cart.sortOrder.splice($scope.cart.sortOrder.indexOf(section.id), 1);
			section.selected = false;
		},
		toggleSection: function(section) {
			if(!$scope.cart.sectionInCart(section)) {
				$scope.cart.addSection(section);
			} else {
				$scope.cart.removeSection(section);
				if($scope.cart.courses.hasOwnProperty(section.courseId)) {
					$scope.cart.courses[section.courseId].selected = false;
					delete $scope.cart.courses[section.courseId];
				}
			}
		},
		toggleCourse: function(course) {
			if($scope.cart.courseInCart(course)) {
				angular.forEach(course.sections, function(section) {
					if($scope.cart.sectionInCart(section)) {
						$scope.cart.removeSection(section);
					}
				});
				delete $scope.cart.courses[course.id];
			} else {
				angular.forEach(course.sections, function(section) {
					if(!$scope.cart.sectionInCart(section)) {
						$scope.cart.addSection(section);
					}
				});
				$scope.cart.courses[course.id] = course;
			}
			course.selected = !course.selected;
		},
		listItems: function() {
			var items = [];
			for(var i = 0, length = $scope.cart.sortOrder.length; i < length; i++) {
				items.push($scope.cart.items[$scope.cart.sortOrder[i]]);
			}
			return items;
		}
	};
	
	$scope.$watch('state.requestOptions.term', function(newTerm) {
		browseRequest.getSchoolsForTerm({term:newTerm}).success(function(data, status) {
			if(status == 200 && typeof data.error == 'undefined') {
				$scope.schools = data;
			} else if(data.error) {
				// TODO: Better error checking
				alert(data.msg);
			}
		});
	});
});


app.directive('browseList', function($http, browseRequest) {
	var hierarchy = ['school', 'department', 'course', 'section'];
	var capitalize = function(string) {
		return string.charAt(0).toUpperCase() + string.slice(1);
	};
	
	return {
		restrict: 'A',
		link: {
			pre: function(scope, elm, attrs) {
				var hIndex = hierarchy.indexOf(attrs.browseList);
				if(hIndex == -1) {
					throw "browseList mode does not exist";
					return;
				}
				var itemName = hierarchy[hIndex],
				childrenName = hierarchy[hIndex + 1] + 's',
				browseRequestMethodName = 'get' + capitalize(childrenName) + 'For' + capitalize(itemName);
				scope[itemName][childrenName] = [];
				scope[itemName].ui = {
					expanded: false,
					buttonClass: 'fa-plus',
					toggleDisplay: function() {
						
						scope[itemName].ui.expanded = !scope[itemName].ui.expanded;
						
						if(scope[itemName].ui.expanded && scope[itemName][childrenName].length == 0) {
							scope[itemName].ui.loading = true;
							scope[itemName].ui.buttonClass = 'fa-refresh fa-spin';
							if(itemName == "course") {
								if(scope.courseCart.contains.course(scope.course)) {
									var sections = [];
									for(var i = 0; i < scope.state.courses.length; i++) {
										if(scope.course.id == scope.state.courses[i].id) {
											sections = scope.state.courses[i].sections;
											break;
										}
									}
									if(sections.length > 0) {
										scope.course.sections = sections;
										scope[itemName].ui.buttonClass = 'fa-minus';
										return;
									}
								}
							}
							browseRequest[browseRequestMethodName]({term: scope.state.requestOptions.term, param: scope[itemName].id}).success(function(data, status) {
								if(status == 200 && typeof data.error == 'undefined') {
									scope[itemName][childrenName] = data[childrenName];
								} else if(data.error) {
									// TODO: Better error checking
									alert(data.msg);
								}
								scope[itemName].ui.buttonClass = 'fa-minus';
							});
						} else if(scope[itemName].ui.expanded) {
							scope[itemName].ui.buttonClass = 'fa-minus';
						} else {
							scope[itemName].ui.buttonClass = 'fa-plus';
						}
					}
				};
			}
		}
	};
});

		
app.factory('browseRequest', function($http) {
	var browseRequest = function(params, callback) {
		return $http.post('js/browseAjax.php', $.param(params), {
			requestType:'json',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			}, 
			withCredentials: true
		});
	};
	return {
		getSchoolsForTerm: function(opts) {
			return browseRequest({
				action:'getSchoolsForTerm',
				term: opts.term
			});
		},
		getDepartmentsForSchool: function(opts) {
			return browseRequest({
				action:'getDepartments',
				term: opts.term,
				school: opts.param
			});
		},
		getCoursesForDepartment: function(opts) {
			return browseRequest({
				action:'getCourses',
				term: opts.term,
				department: opts.param
			});
		},
		getSectionsForCourse: function(opts) {
			return browseRequest({
				action:'getSections',
				course: opts.param
			});
		}
	};
});

