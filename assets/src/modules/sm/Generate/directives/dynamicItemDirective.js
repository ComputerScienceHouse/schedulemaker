angular.module('sm').directive("dynamicItem", function($timeout){
  return {
    restrict: "A",
    require: '^dynamicItems',
    link: { pre: function(scope, elm, attrs, dynamicItems) {
    		scope.$watch('$index', function(newVal) {
    			scope.index =  newVal + 1;
    	        if(scope.index == 1) {   
    	            $timeout(function() {
    	            	elm.addClass('no-repeat-item-animation');
    	                elm.find("input.searchField:first").focus();
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
                elm.find("input.searchField:first").focus();
            }, 0, false);
    	}
    }
  };
});