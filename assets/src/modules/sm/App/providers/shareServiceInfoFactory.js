angular.module('sm').factory('shareServiceInfo', function() {
	
	// Define the services and their common functions
	return {
		googlep: function(url) {
			return 'https://plus.google.com/share?url=' + encodeURIComponent(url);
		},
		twitter: function(url) {
				return 'http://twitter.com/share?url=' + encodeURIComponent(url) + '&text=My%20Class%20Schedule';
		},
		facebook: function(url) {
			return 'http://www.facebook.com/sharer.php?u=' + encodeURIComponent(url);
		}
	}
});