//For now, not a service
angular.module('sm').filter('RMPUrl', function() {
	return function(input) {
		if(input && input != "TBA") {
			var EscapedName = encodeURIComponent(input);
			return '<a target="_blank" href="http://www.ratemyprofessors.com/search.jsp?queryBy=teacherName&queryoption=HEADER&query=' + EscapedName + '&facetSearch=true&schoolName=rochester+institute+of+technology">' + input + '</a>';
		} else {
			return '<a href="#">' + input + '</a>';
		}
	}
});