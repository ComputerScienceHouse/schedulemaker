/**
 * Return either the code or number if it set
 */
angular.module('sm').filter('codeOrNumber', function() {
	return function(input) {
		return (!!input.code)? input.code: input.number;
	};
});