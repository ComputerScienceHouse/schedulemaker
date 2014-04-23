/**
 * Initialize the main sm module
 */
angular.module('sm', ['ngAnimate', 'ngSanitize', 'ui.router'])
/**
 * Core Config code
 */
.config(function($stateProvider, $urlRouterProvider, $locationProvider, $httpProvider) {
	
	$httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
	
	$locationProvider.html5Mode(true);
	
	$urlRouterProvider.otherwise("/404");
	
	var tplBase = '/<%=modulePath%>';
	
	var tplPath = function(submodule, name) {
		return tplBase + submodule + '/templates/' + name + '.min.html';
	};
	
	$stateProvider
	.state('index', {
		url: '/',
		templateUrl: tplPath('Index', 'index')
	})
	.state('404', {
		url: '/404',
		templateUrl: tplPath('App', '404')
	})
	.state('generate', {
		url: '/generate',
		templateUrl: tplPath('Generate', 'generate'),
		controller: 'GenerateController'
	})
	.state('browse', {
		url: '/browse',
		templateUrl: tplPath('Browse', 'browse'),
		controller: 'BrowseController',
	})
	.state('search', {
		url: '/search',
		templateUrl: tplPath('Search', 'search'),
		controller: 'SearchController'
	})
	.state('help', {
		url: '/help',
		templateUrl: tplPath('App', 'help')
	})
	.state('status', {
		url: '/status',
		templateUrl: tplPath('Status', 'status'),
		controller: 'StatusController'
	}).state('schedule', {
		url: '/schedule/:id',
		templateUrl: tplPath('Schedule', 'schedule'),
		resolve: {
			parsedSchedule: ['$stateParams', 'reloadSchedule', function($stateParams, reloadSchedule) {
				return reloadSchedule($stateParams);
			}]
		},
		'abstract': true,
		controller: 'ScheduleController'
	}).state('schedule.view', {
		url: '',
		templateUrl: tplPath('Schedule', 'schedule.view'),
		controller: 'ScheduleViewController',
	}).state('schedule.print', {
		url: '/print',
		templateUrl: tplPath('Schedule', 'schedule.print'),
		controller: 'SchedulePrintController'
	});
})
/**
 * Core run-time code
 */
.run(function($rootScope, $window) {
	$rootScope.$on('$stateChangeSuccess', function(evt) {
		ga('send', 'pageview');
		$($window).scrollTop(0);
	});
	
	//IE 10 MOBILE FIX
	if (navigator.userAgent.match(/IEMobile\/10\.0/)) {
		var msViewportStyle = document.createElement("style")
		msViewportStyle.appendChild(
			document.createTextNode(
				"@-ms-viewport{width:auto!important}"
			)
		)
		document.getElementsByTagName("head")[0].appendChild(msViewportStyle)
	}
});
