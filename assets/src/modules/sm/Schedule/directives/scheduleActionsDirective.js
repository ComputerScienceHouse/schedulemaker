/**
 * Several endpoint abstractions for the schedules
 */
angular.module('sm').directive('scheduleActions', function($http, $q, shareServiceInfo, openPopup, localStorage, $state) {
	
	var serializer = new XMLSerializer();
	
	function scheduleActions(scope, elm) {
		
		var getSavedInfo = function() {

			// See if we already have saved info
			if(scope.saveInfo) {
				var defferred = $q.defer();
				defferred.resolve(scope.saveInfo);
				return defferred.promise;
			}
			// If not create it
			var schedule = angular.copy(scope.schedule);
			
			
			// Create the request params as all strings with correct keys
			var params = {
				data: JSON.stringify({
					startday:  '' + scope.state.drawOptions.startDay,
					endday:    '' + scope.state.drawOptions.endDay,
					starttime: '' + scope.state.drawOptions.startTime,
					endtime:   '' + scope.state.drawOptions.endTime,
					building:  '' + scope.state.drawOptions.bldgStyle,
					term:      '' + scope.state.requestOptions.term,
					schedule:  schedule,
				}),
				svg: serializer.serializeToString(elm.find("svg").get(0)),
				action: "saveSchedule"
			};
			
			
			// Post the schedule and return a promise
			return $http.post('/js/scheduleAjax.php', $.param(params), {
				requestType:'json',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded'
				}, 
				withCredentials: true
			}).then(function(request) {
				if(request.status == 200 && typeof request.data.error == 'undefined') {
					
					// save the saveInfo and return it
					scope.saveInfo = request.data;
					
					return request.data;
				} else {
					
					return $q.reject("Save Error:" + request.data.msg);
				}
			});
		};

		scope.scheduleActions = {
			
			save: function(saveType) {
				
				if(saveType == "create") {
					getSavedInfo().then(function(data) {
						scope.notification = "This schedule can be accessed at " +
						"<a href=\""+ data.url + "\" target=\"_blank\">"
						+ data.url + "</a><br><em>This schedule will be removed" +
						" after 3 months of inactivity</em>";
					},  function(error) {
						console.log(error);
						scope.notification = error;
					});
				} else {
					
					localStorage.setItem('forkSchedule', scope.schedule);
					
					$state.go("generate");
				}
			},
			
			shareToService: function($event, serviceName, newWindow) {
				
				$event.preventDefault();
				scope.status = "L";
				if(serviceName && serviceName in shareServiceInfo) {
					
					var service = shareServiceInfo[serviceName];
					
					// Create a popup in click context to workaround blockers
					var popup = openPopup(newWindow);
					
					getSavedInfo().then(function(data) {
						scope.status = "D";
						popup.location = service(data.url);
					});
				} 
			},
			
			shareToEmail: function($event) {
				
				$event.preventDefault();
				
				getSavedInfo().then(function(data) {
					
					var body = "Check out my schedule at: " + data.url;
					
					//Open a mailto link
					window.location.href= "mailto:?body=" + 
					encodeURIComponent(body);
				});
			},
			
			shareToDirectLink: function($event) {
				
				$event.preventDefault();
				
				scope.scheduleActions.save('create');
			},
			
			downloadiCal: function($event) {
				
				$event.preventDefault();
				
				getSavedInfo().then(function(data) {

					window.location.href= data.url + "/ical";
				});
			},
			
			downloadImage: function($event) {
				
				$event.preventDefault();
				
				var popup = openPopup(true);
				
				getSavedInfo().then(function(data) {

					popup.location = ("http://" + window.location.hostname +
					'/img/schedules/' + parseInt(data.id, 16) + '.png');
				});
			},
			
			print: function() {
				
				
				var reloadSchedule = angular.copy(scope.state.drawOptions);
				reloadSchedule.term = scope.state.requestOptions.term,
				reloadSchedule.courses = scope.schedule;
				
				var popup = openPopup(780, 600);
				
				popup.localStorage.setItem('reloadSchedule', angular.toJson(reloadSchedule));
				popup.document.title = "My Schedule";
				popup.location = "http://" + window.location.hostname + '/schedule/render/print';	
			}
		}
	};
	
	
	return {
		
		/**
		 * Save a schedule, given the respective parameters
		 */
		link: {
			pre: scheduleActions
		}
	};
});