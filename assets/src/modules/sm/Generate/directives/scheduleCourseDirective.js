angular.module('sm').directive("scheduleCourse", function(){
	  return {
	    restrict: "C",
	    templateUrl: '/<%=modulePath%>Generate/templates/courseselect.min.html',
	  };
});