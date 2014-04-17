
/**
 * Initialize the main sm module
 */
angular.module('sm', ['ngAnimate', 'ngSanitize', 'ui.router'])
/**
 * Core Config code
 */
.config(function($stateProvider, $urlRouterProvider, $locationProvider) {
	
	$locationProvider.html5Mode(true);
	
	$urlRouterProvider.otherwise("/404");
	
	$stateProvider
	.state('index', {
		url: '/',
		templateUrl: '/assets/prod/templates/index.html'
	})
	.state('404', {
		url: '/404',
		templateUrl: '/assets/prod/templates/404.html'
	})
	.state('generate', {
		url: '/generate',
		templateUrl: '/assets/prod/templates/generate.html',
		controller: 'GenerateController'
	})
	.state('browse', {
		url: '/browse',
		templateUrl: '/assets/prod/templates/browse.html',
		controller: 'BrowseController',
	})
	.state('search', {
		url: '/search',
		templateUrl: '/assets/prod/templates/search.html',
		controller: 'SearchController'
	})
	.state('help', {
		url: '/help',
		templateUrl: '/assets/prod/templates/help.html'
	})
	.state('status', {
		url: '/status',
		templateUrl: '/assets/prod/templates/status.html',
		controller: 'StatusController'
	}).state('schedule', {
		url: '/schedule/:id',
		templateUrl: '/assets/prod/templates/schedule.html',
		resolve: {
			parsedSchedule: function($stateParams, reloadSchedule) {
				return reloadSchedule($stateParams);
			}
		},
		abstract: true,
		controller: 'ScheduleController'
	}).state('schedule.view', {
		url: '',
		templateUrl: '/assets/prod/templates/schedule.view.html',
		controller: 'ScheduleViewController',
	}).state('schedule.print', {
		url: '/print',
		templateUrl: '/assets/prod/templates/schedule.print.html',
		controller: 'SchedulePrintController'
	});
})
/**
 * Core run-time code
 */
.run(function($rootScope, $window) {
	$rootScope.$on('$stateChangeSuccess', function(evt) {
		$($window).scrollTop(0);
	});
});