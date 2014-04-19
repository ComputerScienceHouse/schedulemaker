angular.module('sm').directive("navCloseOnMobile", function() {
		return {
			restrict: 'A',
			link: function(scope, elm) {
				var nav = $(elm);
				$(elm).find('li').click(function() {
					 $('.navbar-collapse.in').collapse('hide');
				});
			} 
		};
	});
