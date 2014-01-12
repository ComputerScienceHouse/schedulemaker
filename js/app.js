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

app.controller( "AppCtrl", function( $scope) {
	$scope.schedules =[];
	$scope.options = {
		start_time:'8:00am',
		end_time:'10:00pm',
		start_day: 1,
		end_day: 6,
		building_style: 'code',
		fullscreen: false
	};
	$scope.ui = {
		days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
	};
	
	$scope.ensureCorrectEndDay = function() {
		if($scope.options.start_day > $scope.options.end_day) {
			$scope.options.end_day = $scope.options.start_day;
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
	
    $scope.generateSchedules = function() {
    	
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
    			$scope.schedules = data.schedules;
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
    			/*
    			// If we're showing all schedules on one page, then do that
                var schedPerPage = $("#schedPerPage");
    			if(schedPerPage.val() == 'all') {
    				SCHEDPERPAGE = schedules.length;
    			} else {
    				SCHEDPERPAGE = parseInt(schedPerPage.val());
    			}
    			
    			// How many pages of schedules are there
    			pages = Math.ceil(schedules.length / SCHEDPERPAGE);
    			curPage = 0;

    			// Generate a subset of the schedules for display
    			data.schedules = schedules.slice(0, SCHEDPERPAGE);

    			

    			// If there were recoverable errors, show them
    			// NOTE: the php side determines whether to send errors based on verbose value

    			// Grab the advanced options for the schedule
    			startday  = parseInt($("#scheduleStartDay").val());
    			endday    = parseInt($("#scheduleEndDay").val());

    			// Determine the height and width of the schedule based on start/end time/day
    			schedHeight = (Math.floor((endtime - starttime) / 30) * 20) + 20;
    			schedWidth  = ((endday - startday) * 100) + 200;		// +200 b/c we always show at least ONE day

    			// Now we draw the schedules
    			//drawPage(0, false);

    			// Add next/previous page controls
    			var pagination = $("<div>").addClass("schedulePagination");
    			var pageinfo = schedules.length + " Schedules Generated (Page <span class='curpage'>" + (curPage + 1) + "</span> of " + pages + ")";
    			pagination.html(pageinfo);
    			if(pages > 1) {
    				var prev = $("<input>").attr("type", "button")
    							.attr("value", "<- Previous")
    							.attr("onClick", "getPrevPage();")
    							.addClass("prevbutton")
    							.css("display", "none");
    				var next = $("<input>").attr("type", "button")
    							.attr("value", "Next ->")
    							.attr("onClick", "getNextPage();")
    							.addClass("nextbutton");
    				pagination.append(prev);
    				pagination.append(next);
    			}
    			pagination.insertAfter('#matchingSchedules');
    			pagination2 = pagination.clone();
    			pagination2.appendTo('#schedules');
    			*/
    			
    		}).error( function() {
                var scheduleDiv = $("#schedules");
    			var errorDiv = $("<div>");
    			errorDiv.attr("id", "errorDiv");
    			errorDiv.addClass("scheduleError");
    			errorDiv.html("Fatal Error: An internal server error occurred");
    			errorDiv.appendTo(scheduleDiv);
    			scheduleDiv.slideDown();
    		});
    	}
    };
});

app.controller( "MainMenuCtrl", function( $scope) {
  $scope.path = window.location.pathname;
});

app.controller( "scheduleCoursesCtrl", function( $scope, $http, $q) {
  $scope.courses = [];
  var id = 0;
  $scope.courses_helpers = {
	  add: function() {
		  id = id + 1
	    $scope.courses.push({
	    	id: id,
	        search: '',
	        results: []
	    });
        $scope.$broadcast('addedCourse');
	  },
	  remove: function(index) {
	        $scope.courses.splice(index - 1, 1);
	  }
  };
  $scope.courses_helpers.add();
  var canceler;
  $scope.search = function(course) {
	  if (canceler) canceler.resolve();
      canceler = $q.defer();
	    $http.post('./js/scheduleAjax.php',$.param({
    		'action'     : 'getCourseOpts',
            'course'     : course.search,
            'term'       : $scope.term,
            'ignoreFull' : $scope.ignoreFull
	    }), {
	    	requestType:'json',
	    	headers: {
	            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
	        }, 
	        timeout: canceler.promise
	    }).success(function(d, status, headers, config) {
	    	if(!d.error) {
		    	for(var c = 0; c < d.length; ++c) {
		            var times = [];
		            var coursei = d[c];
	
		            // Iterate over the times for the course
		            if(coursei.times == undefined) { continue; }
		            for(var e = 0; e < coursei.times.length; ++e) {
		                // Search the existing list of times to see if a match exists
		                var found = false;
		                var time = coursei.times[e];
		                for(var f = 0; f < times.length; ++f) {
		                    if(times[f].start == time.start && times[f].end == time.end) {
		                        found = f;
		                    }
		                }
	
		                // If a match was found, add the day to it, otherwise add a new time
		                if(found !== false) {
		                    times[found].days += ", " + translateDay(time.day);
		                } else {
		                    times.push({
		                        start: time.start,
		                        end:   time.end,
		                        days:  translateDay(time.day)
		                    });
		                }
		            }
		            d[c].isError = false;
		            // Replace the current list of times with the newly constructed one
		            d[c].oldTimes = d[c].times;
		            d[c].times = times;
		            
		        }
		    	course.results = d;
	    	} else {
	    		course.results = [{isError:true,error:d}];
	    	}
	    }).
	    error(function(data, status, headers, config) {
	    // Most likely typed too fast
	    });
  };
  $scope.$watchCollection('[term, ignoreFull]', function() {
	  for(var i = 0, l = $scope.courses.length; i < l; i++) {
		  var course = $scope.courses[i];
		  if(course.search.length > 3)
			  $scope.search(course);
	  }
  });
  $scope.$watch('courses', function(newCourses, oldCourses) {
	for(var i = 0, l = newCourses.length; i < l; i++){
		var newCourse = newCourses[i],
			oldCourse = oldCourses.filter(function (filterCourse) {
				return filterCourse.id === newCourse.id;
			})[0];
		if(typeof oldCourse === 'undefined') {
			oldCourse = {
				search: '',
				results: []
			};
		}
		if(newCourse.search != oldCourse.search && newCourse.search.length > 5) {
			$scope.search(newCourse);
		} else if(newCourse.search != oldCourse.search) {
			newCourse.results = [];
		}
	}
  }, true);
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

app.directive("scheduleCourse", function(){
	  return {
	    restrict: "C",
	    templateUrl: './js/templates/courseselect.html',
	  };
});
app.directive("dynamicItems", function($compile,$timeout){
	  return {
	    restrict: "A",
	    scope: {
	    	'dynamicItems': '=',
	    	'useClass':'@',
	    	'helpers':'=',
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
                            elm.find('input:last').focus();
                        }, 0, false);
                    });
		    		elm.append($compile('<div class="'+scope.useClass+' repeat-item" ng-repeat="item in dynamicItems" dynamic-item></div>')(scope));
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
    		});
	    	
	        scope.remove = function() {
	            if(scope.index == 1 && dynamicItems.items.length == 1) {
	            	dynamicItems.remove(scope.index);
	            	dynamicItems.add();
	            } else {
	            	if(scope.index == 1) {
	            		elm.removeClass('no-repeat-item-animation');
	            	}
	            	dynamicItems.remove(scope.index);
	            }
	        };
    	}, post: function(scope, elm, attrs, dynamicItems) {
	        var ident = 'input.searchField',
	        input = elm.find(ident);
	        
	        var doKeystrokeAnalysis = function(e) {
	            if(e.keyCode == 13) {
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
	            } else if(e.keyCode == 38) {
	                e.preventDefault();
	                if(scope.index > 1) {
                    	elm.prev().find(ident).focus();
	                } 
	            } else if(e.keyCode == 40) {
	                if(scope.index < dynamicItems.items.length) {
                    	elm.next().find(ident).focus();
                    	e.preventDefault();
	                } 
	            }
	        };
	        
            input.blur(function(e) {
                e.preventDefault();
            });
            
	        input.keydown(function(e) {
	        	scope.$apply(doKeystrokeAnalysis(e));
	        });
	        if(scope.$index == 0) {   
	            $timeout(function() {
	            	elm.addClass('no-repeat-item-animation');
	                elm.find("input").focus();
	            }, 0, false);
	        }
    	}
    }
  };
});

app.directive('scheduleOptions', function() {
	return {
		restrict: 'A',
		link: function(scope, elm, attrs) {
		}
	};
});

app.directive('schedule', function($timeout) {
	function Schedule(scope) {
		this.scope = scope;
		this.colors = ['247, 134, 134',
		              '243, 220, 146',
		              '243, 243, 170',
		              '220, 247, 133',
		              '186, 238, 243'
		              ];
	}
	Schedule.prototype.draw = function() {
		var scope = this.scope;
		
		var start = scope.options.start_time.match(/([0-9]+):([0-9]{2})(am|pm)/);
        var end = scope.options.end_time.match(/([0-9]+):([0-9]{2})(am|pm)/);
        console.log("herere: ", start, ", end:", end);
        if(start[3] == 'am' && parseInt(start[1]) == 12) {
            starttime = parseInt(start[2]);
        } else if(start[3] == 'pm') {
            start[1] = parseInt(start[1]) + 12;
        }
        starttime = (parseInt(start[1]) * 60) + parseInt(start[2]);
        if(end[3] == 'am' && parseInt(end[1]) == 12) {
            starttime = parseInt(end[2]);
        } else if(end[3] == 'pm') {
            end[1] = parseInt(end[1]) + 12;
        }
        endtime = (parseInt(end[1]) * 60) + parseInt(end[2]);
        if(starttime >= endtime) {
            data.error = true;
            data.msg = "Schedule start and end times are incompatible.";
        }
		
        var hourArray = [];
        for(var time = starttime; time < endtime; time += 60) {
    		// Calculate the label
    		var hourLabel = Math.floor(time / 60);
    		if(hourLabel > 12) { hourLabel -= 12; }
    		else if(hourLabel == 0) { hourLabel = 12; }

    		if(time >= 720) { ap = " PM"; } else { ap = " AM"; }	
    		
    		hourArray.push(String(hourLabel) + ap);
    	}

		// Generate grid
        var numDays = scope.options.end_day - scope.options.start_day + 1;
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
		
		var dayIndex = scope.options.start_day;
		for(var i=0; i < numDays; i++) {
			var offset = globalOpts.hoursWidth + ( 2 * dayOpts.padding) + ((dayOpts.rawWidth - dayOpts.padding) * i);
			dayArray.push({
				name: scope.ui.days[dayIndex],
				offset: offset + '%',
			});
			dayIndex++;
		}
		
        
		//Set the scope variable
		scope.grid = {
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
	};

	return {
		restrict: 'A',
		templateUrl: './js/templates/schedule.html',
		link: {
			pre: function(scope, elm, attrs) {
				scope.scheduleController = new Schedule(scope);
				
			},
			post: function(scope, elm) {
				scope.$watchCollection('options', function() {			
					scope.scheduleController.draw();
					
					// Fix the pixel issues after DOM updates
					$timeout(function() {
						var offset = elm.find("svg").offset(),
						vert = 1 - parseFloat('0.'+('' + offset.top).split('.')[1]);
						horz = 1 - parseFloat('0.'+('' + offset.left).split('.')[1]);
						scope.grid.opts.pixelAlignment ='translate('+horz+','+vert+')';
					},0,true);
				});
			}
		}
	};
});
