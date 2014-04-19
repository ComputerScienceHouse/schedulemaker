angular.module('sm').directive("loadingButton", function(uiDayFactory) {
	
	var template = '<i class="fa fa-spin fa-refresh" ></i> ';
	
	return {
		restrict: 'A',
		scope: {
			status: '=loadingButton',
			text: '@loadingText'
		},
		link: function(scope, elm) {
			var prevHTML = elm.html();
			scope.$watch('status', function(newLoading, prevLoading) {
				if(newLoading != prevLoading) {
					if(newLoading == 'L') {
						elm.html(template + scope.text);
						elm.attr("disabled", true);
					} else {
						elm.html(prevHTML);
						elm.attr("disabled", false);
					}
				}
			});
		}
	};
});