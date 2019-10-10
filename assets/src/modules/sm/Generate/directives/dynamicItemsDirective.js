angular.module('sm').directive('dynamicItems', function ($compile, $timeout, globalKbdShortcuts) {
  return {
    restrict: 'A',
    scope: {
      dynamicItems: '=',
      useClass: '@',
      helpers: '=',
      colors: '='
    },
    controller: function ($scope) {
      this.items = $scope.dynamicItems
      this.add = $scope.helpers.add
      this.remove = $scope.helpers.remove
    },
    compile: function (telm, tattrs) {
      return {
        pre: function (scope, elm, attrs) {
          scope.$parent.$on('addedCourse', function () {
            $timeout(function () {
              elm.find('input.searchField:last').focus()
            }, 0, false)
          })
          elm.append($compile('<div class="' + scope.useClass + ' repeat-item" ng-repeat="item in dynamicItems" dynamic-item ng-if="item.fromSelect"></div>')(scope))
        },
        post: function (scope, elm, attrs) {
          globalKbdShortcuts.bindSelectCourses(function () {
            if (elm.find('input.searchField:focus').length === 0) {
              $('html, body').animate({
                scrollTop: 0
              }, 500, null, function () {
                elm.find('input.searchField:first').focus()
              })
            }
          })
        }
      }
    }
  }
})
