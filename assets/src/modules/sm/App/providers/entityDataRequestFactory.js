angular.module('sm').factory('entityDataRequest', function($http) {
	var entityDataRequest = function(params, callback) {
		return $http.post('/entity/' + params.action, $.param(params));
	};
	return {
		getSchoolsForTerm: function(opts) {
			return entityDataRequest({
				action:'getSchoolsForTerm',
				term: opts.term
			});
		},
		getDepartmentsForSchool: function(opts) {
			return entityDataRequest({
				action:'getDepartments',
				term: opts.term,
				school: opts.param
			});
		},
		getCoursesForDepartment: function(opts) {
			return entityDataRequest({
				action:'getCourses',
				term: opts.term,
				department: opts.param
			});
		},
		getSectionsForCourse: function(opts) {
			return entityDataRequest({
				action:'getSections',
				course: opts.param
			});
		}
	};
});