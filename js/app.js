var app = angular.module( 'sm', ['ngAnimate'] );

app.filter('RMPUrl', function() {
	return function(input) {
		if(input) {
			var nameParts = input.split(" "),
			lastName = nameParts[nameParts.length - 1];
			return 'http://www.ratemyprofessors.com/SelectTeacher.jsp?searchName='+lastName+'&search_submit1=Search&sid=807';
		} else {
			return '#';
		}
	}
});

app.controller( "AppCtrl", function( $scope) {
    $("button").addClass("btn btn-default");
    $("input[type='text']").addClass("form-control");
});

app.controller( "MainMenuCtrl", function( $scope) {
  $scope.path = window.location.pathname;
});

app.controller( "scheduleCoursesCtrl", function( $scope, $http) {
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
  $scope.search = function(course) {
	    $http.post('./js/scheduleAjax.php',$.param({
    		'action'     : 'getCourseOpts',
            'course'     : course.search,
            'term'       : $scope.term,
            'ignoreFull' : $scope.ignoreFull
	    }), {
	    	requestType:'json',
	    	headers: {
	            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
	        }
	    }).success(function(data, status, headers, config) {
	    	course.results = data;
	    // this callback will be called asynchronously
	    // when the response is available
	    }).
	    error(function(data, status, headers, config) {
	    // called asynchronously if an error occurs
	    // or server returns response with an error status.
	    });
  };
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
		console.log('s:'+newCourse.search);
		if(newCourse.search != oldCourse.search && newCourse.search.length > 3) {
			$scope.search(newCourse);
		}
	}
  }, true);
});

app.directive("scheduleCourse", function(){
	  return {
	    restrict: "C",
	    template: '\
	                <div dynamicItem class="form-group">\
	                    <label class="col-sm-3 col-xs-12 control-label" for="courses{{index}}">Course {{index}}:</label>\
	                    <div class="col-sm-7 col-xs-9">\
	    					<input tabindex="{{index}}" id="courses{{index}}" class="form-control" ng-model="item.search" type="text" name="courses{{index}}" maxlength="17" placeholder="DPMT-CRS-SECT" />\
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
