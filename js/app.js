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
	    template: '\
	                <div dynamicItem class="form-group" ng-class="{noInput:noInput}">\
	                    <label class="col-sm-3 col-xs-12 control-label" for="courses{{index}}">Course {{index}}:</label>\
	                    <div class="col-sm-7 col-xs-9">\
	    					<input tabindex="{{index}}" id="courses{{index}}" class="form-control" ng-model="item.search" type="text" name="courses{{index}}" maxlength="17" placeholder="DPMT-CRS-SECT" />\
	                    </div>\
	                    <div class="col-sm-2 col-xs-3">\
	                        <button type="button" class="btn btn-danger" ng-click="remove()">&times;</button>\
	                    </div>\
	                </div>\
	            '
	  };
});
app.directive("dynamicItems", function($timeout){
	  return {
	    restrict: "A",
	    scope: {
	    	'dynamicItems': '=',
	    	'useClass':'@',
	    	'onAdd':'&',
	    	'onRemove':'&'
	    },
	    template: '<div ng-repeat="item in dynamicItems" dynamic-item class="scheduleCourse"></div>',
	    controller: function($scope) {
	    	this.items = $scope.dynamicItems;
	    	this.add = $scope.onAdd;
	    	this.remove = $scope.onRemove;
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
    		})

	    	if(scope.index == 2) {
	    		scope.noInput = true;
	    	} else {
	    		scope.noInput = false;
	    	}
	    	
	        scope.remove = function() {
	        	console.log('removing' + scope.index)
                dynamicItems.remove(scope.index);
	            if(scope.index == '1' && dynamicItems.items.length <=1) {
	            	dynamicItems.add();
	            }
	        };
    	}, post: function(scope, elm, attrs, dynamicItems) {
	        var input = elm.find('input');
	        
	        if(scope.index == 2) {
		        input.focus(function(e) {
		        	scope.$apply(function() {
		        		scope.noInput = false;
		        	});
		        });
	        }
	        
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
  }
});
