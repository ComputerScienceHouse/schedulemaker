angular.module('sm').directive("dowSelectFields", function (uiDayFactory) {
  return {
    restrict: 'A',
    scope: {
      select: '=dowSelectFields'
    },
    template: '<div class="btn-group btn-group-dow"><button type="button" ng-repeat="day in days" ng-click="toggle(day)" class="btn btn-default btn-dow" ng-class="{\'btn-success\':isSelected(day)}" ng-bind="day.substring(0 ,2)"></button></div>',
    link: {
      pre: function (scope) {
        scope.days = uiDayFactory();
        scope.isSelected = function (day) {
          return scope.select.indexOf(day) != -1;
        };
        scope.toggle = function (day) {
          var index = scope.select.indexOf(day);
          if (index == -1) {
            scope.select.push(day);
          } else {
            scope.select.splice(index, 1);
          }
        };
      }
    }
  };
});
