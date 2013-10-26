var app = angular.module( 'sm', [] )

app.controller( "AppCtrl", function( $scope) {
    $("button").addClass("btn btn-default")
    $("input[type='text']").addClass("form-control");
});

app.controller( "MainMenuCtrl", function( $scope) {
  $scope.path = window.location.pathname;
});

app.controller( "scheduleCoursesCtrl", function( $scope) {
  $scope.courses = [];
  $scope.addCourse = function() {
    
    $scope.courses.push({
        index: $scope.courses.length + 1,
        search: '',
        results: []
    });
  };
  $scope.removeCourse = function(index) {
        $scope.courses.splice(index - 1, 1);
  };
  $scope.addCourse();
  $scope.addCourse();
});

app.directive("scheduleCourse", function($timeout){
  return {
    restrict: "C",
    scope: {
        course:'=',
        coursesindex:'='
    },
    template: '\
                <div class="form-group" ng-class="{noInput:noInput}">\
                    <label class="col-sm-3 col-xs-12 control-label" for="courses{{index}}">Course {{index}}:</label>\
                    <div class="col-sm-7 col-xs-9">\
    					<input tabindex="{{index}}" id="courses{{index}}" class="form-control" ng-model="course.search" type="text" name="courses{{index}}" maxlength="17" placeholder="DPMT-CRS-SECT" />\
                    </div>\
                    <div class="col-sm-2 col-xs-3">\
                        <button type="button" class="btn btn-danger" ng-click="remove()">&times;</button>\
                    </div>\
                </div>\
            ',
    controller: function($scope) {
        $scope.$watch('coursesindex', function(newVal, oldVal) {
            $scope.index = newVal + 1;
        });
        
    	if($scope.coursesindex == 1) {
    		$scope.noInput = true;
    	} else {
    		$scope.noInput = false;
    	}
    	
        $scope.remove = function() {
                $scope.$parent.removeCourse($scope.index);
            if($scope.index == '1' && $scope.$parent.courses.length <=1) {
            	$scope.$parent.addCourse();
            }
           // });
        };
    },
    link: function(scope, elm, attrs) {
        var input = elm.find('input');
        
        if(scope.coursesindex == 1) {
	        input.focus(function(e) {
	        	scope.$apply(function() {
	        		scope.noInput = false;
	        	});
	        });
        }
        
        var doKeystrokeAnalysis = function(e) {
            if(e.keyCode == 13) {
                if(scope.$parent.courses.length == scope.index) {
                    scope.$parent.$apply(function() {
                        scope.$parent.addCourse();
                        $timeout(function() {
                            elm.next().find("input").focus();
                        }, 0, false);
                                            
                    });
                } else {
                    elm.next().find("input").focus();
                }
            } else if(e.keyCode == 27) {
                e.preventDefault();
                    elm.prev().find("input").focus();
                    scope.remove();  
            }
        };
        
        input.keypress(function(e) {
        	scope.$apply(doKeystrokeAnalysis(e));
        });
        if(scope.coursesindex == 0) {   
            $timeout(function() {
                elm.find("input").focus();
            }, 0, false);
        }
    }
  }
});
