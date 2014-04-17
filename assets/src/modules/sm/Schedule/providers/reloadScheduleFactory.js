angular.module('sm').factory('reloadSchedule', function($http, $q, localStorage) {
	
	/**
	 * Set the correct drawOptions and term as well as a global schedule var
	 * for displaying any single schedule alone
	 */
	
	var getEmptySchedule = function() {
		return {
			schedule: []
		};
	};
	
	return function($stateParams) {
		
		var deferred = $q.defer();
		
		// Check if
		if($stateParams.hasOwnProperty('id') && $stateParams.id != 'render') {

			// We need to get the schedule
			$http.get('/api/schedule/' + $stateParams.id, null, {
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
				}
			}).then(function(response) {
				if(response.status == 200 && !response.data.error) {
					deferred.resolve(response.data);
				} else {
					deferred.resolve(response.data);
				}
			}, function() {
				deferred.resolve(getEmptySchedule());
			});
			
		} else if(localStorage.hasKey('reloadSchedule')) {
			
			//Get the schedule from sessions storage
			var reloadSchedule = localStorage.getItem('reloadSchedule');
			console.log('herer', reloadSchedule);
			// If it's actually there
			if(reloadSchedule != null) {
				deferred.resolve(reloadSchedule);
				localStorage.setItem("reloadSchedule", null);
			} else {
				deferred.resolve(getEmptySchedule());
			}
		} else {
			deferred.resolve(getEmptySchedule());
		}
		
		return deferred.promise;
	};
});