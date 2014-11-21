angular.module('sm').directive('courseDetailPopover', function($http, $filter) {
	var RMPUrl = $filter('RMPUrl'),
		parseTimes = $filter('parseSectionTimes'),
		formatTime = $filter('formatTime');

	function getTimesHTML(times) {
		if(!times) {
			return '';
		}
		var parsedTimes = parseTimes(times, true);
		var HTML = '<div style="font-size: small">';
		for (var timeIndex = 0; timeIndex < parsedTimes.length; timeIndex++) {
			var time = parsedTimes[timeIndex];
			HTML += time.days + ' <span style="white-space: nowrap">' + formatTime(time.start) + '-' + formatTime(time.end) + '</span> <span style="font-style: italic; white-space: nowrap">Location: ' + time.location + '</span>';
			if(timeIndex < parsedTimes.length - 1) {
				HTML += '<br>';
			}
		}
		HTML += '</div>';

		return HTML;
	}
	return {
		restrict: 'A',
		scope: {
			sectionId: '=courseDetailPopover'
		},
		link: function(scope, elm) {
			if(scope.sectionId != '') {
				var loaded = false,
					opened = false,
					$body = $("body");

				function hidePopoverOnBodyClick() {
					setTimeout(function() {

						$body.off('click.hidepopovers');
						$body.on('click.hidepopovers', function () {
							elm.popover('destroy');
							loaded = false;
							$body.off('click.hidepopovers');
							opened = false;
						});
					}, 100);
				}

				elm.on('click', function() {
					if(!loaded) {
						loaded = true;
						$http.post('/entity/courseForSection',
							$.param({
								id: scope.sectionId
							})
						).success(function(data) {
							elm.popover({
								html:true,
								trigger:'click',

								title: data.courseNum,
								content: '<div class="well-sm pull-right" style=" background-color: #ddd;" title="Other students enrolled as of 6AM today">' + data.curenroll + '/' + data.maxenroll + ' <i class="fa fa-user"></i></div><p>' + data.title + '<br><span class="label label-default popover-white">' + RMPUrl(data.instructor) + '</span></p><p>' + getTimesHTML(data.times) + '</p><p>' + data.description + '</p>',
								container: '#container'
							});
							elm.popover('show');
							opened = true;
							hidePopoverOnBodyClick();
						}).error(function() {
								loaded = false;
							});
					} else {
						//elm.popover('toggle');
						opened = !opened;
						if(opened) {
							//hidePopoverOnBodyClick();
						}
					}
				});
			}
		}
	};
});