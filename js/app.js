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
  console.log('here1');
});

app.directive("scheduleCourse", function($timeout){
  return {
    restrict: "C",
    scope: {
        course:'=',
        coursesindex:'='
    },
    template: '\
                <div class="form-group">\
                    <label class="col-md-3 col-sm-12 control-label" for="courses{{index}}">Course {{index}}:</label>\
                    <div class="col-md-7 col-xs-9">\
                        <input tabindex="{{index}}" id="courses{{index}}" class="form-control" value="{{course.search}}" type="text" name="courses{{index}}" maxlength="17" placeholder="XXXX-XXX-XXXX" />\
                    </div>\
                    <div class="col-md-2 col-xs-3">\
                        <button ng-disabled="course.index == \'1\'" type="button" class="btn btn-danger" ng-click="remove()">&times;</button>\
                    </div>\
                </div>\
            ',
    controller: function($scope) {
        $scope.$watch('coursesindex', function(newVal, oldVal) {
            $scope.index = newVal + 1;
        });
        $scope.remove = function() {
            if($scope.index != '1') {
                $scope.$parent.removeCourse($scope.index);
            } else {
                alert("You cannot delete the first course.");
            }
           // });
        };
    },
    link: function(scope, elm, attrs) {
    	console.log('here');
        var input = elm.find('input');
        input.keypress(function(e) {
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
                scope.$apply(function() {
                    elm.prev().find("input").focus();
                    scope.remove();  
                });
            }
        });
        if(scope.coursesindex == 0) {   
            $timeout(function() {
                elm.find("input").focus();
            }, 0, false);
        }
    }
  }
});
