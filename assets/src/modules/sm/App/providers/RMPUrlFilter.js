//For now, not a service
angular.module('sm').filter('RMPUrl', function() {
	return function(input) {
		if(input && input != "TBA") {
			var nameParts = input.split(" "),
			lastName = nameParts[nameParts.length - 1];
			return '<a target="_blank" href="http://www.ratemyprofessors.com/search.jsp?queryBy=teacherName&queryoption=HEADER&query=' + lastName + '&facetSearch=true&schoolName=rochester+institute+of+technology">' + input + '</a>';
		} else {
			return '<a href="#">' + input + '</a>';
		}
	}
});