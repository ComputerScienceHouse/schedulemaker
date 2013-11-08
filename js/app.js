var app = angular.module( 'sm', ['ngAnimate', 'ngSanitize'] );

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

app.controller( "AppCtrl", function( $scope) {
    $("button").addClass("btn btn-default");
    $("input[type='text']").addClass("form-control");
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
  $scope.$watch('term', function(newVal) {
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

app.directive("scheduleCourse", function(){
	  return {
	    restrict: "C",
	    template: '\
	                <div dynamicItem class="form-group" ng-class="{\'has-error\':item.results[0].isError == true}">\
	                    <label class="col-sm-3 col-xs-12 control-label" for="courses{{index}}">Course {{index}}:</label>\
	                    <div class="col-sm-7 col-xs-9">\
	    					<input tabindex="{{index}}" id="courses{{index}}" class="form-control" ng-model="item.search" type="text" name="courses{{index}}" maxlength="17" placeholder="DEPT-CRS-SECT" />\
	                    </div>\
	                    <div class="col-sm-2 col-xs-3">\
	                        <button type="button" ng-class="{\'btn-danger\':delHover}" ng-mouseenter="delHover = true" ng-mouseleave="delHover = false" class="btn btn-default" ng-click="remove()">&times;</button>\
	                    </div>\
	                </div>\
	            '
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
		    		elm.append($compile('<div ng-repeat="item in dynamicItems" dynamic-item class="repeat-item '+scope.useClass+'"></div>')(scope));
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
	        var input = elm.find('input');
	        
	        var doKeystrokeAnalysis = function(e) {
	            if(e.keyCode == 13) {
	                if(dynamicItems.items.length == scope.index) {
	                	dynamicItems.add();
                        $timeout(function() {
                            elm.next().find("input").focus();
                        }, 0, false);
	                } else {
	                    elm.next().find("input").focus();
	                }
	            } else if(e.keyCode == 27) {
	                e.preventDefault();
	                if(scope.index > 1) {
                    	elm.prev().find("input").focus();
	                } else {
	                	var parent = elm.parent();
	                	$timeout(function() {
                            parent.find("input:first").focus();
                        }, 0, false);
	                }
                    scope.remove();  
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
  }
});
