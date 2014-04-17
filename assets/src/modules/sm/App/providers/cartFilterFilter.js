angular.module('sm').filter('cartFilter', function() {
	return function(input) {
		var parsed = [];
		var SSFN = this.courseCart.count.course.selectedSections;
		angular.forEach(input, function(course) {
			if(course && course.sections.length > 0 && !course.sections[0].isError && SSFN(course) > 0) {
				parsed.push(course);
			}
		});		
		return parsed;
	};
});