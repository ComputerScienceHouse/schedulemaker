angular.module('sm').directive('paginationControls', function () {
    return {
        restrict: 'A',
        scope: {
            displayOptions: '=paginationControls',
            totalLength: '=paginationLength',
            paginationCallback: '&'
        },
        template: '<button title="Shortcut: Ctrl + Left" class="btn btn-default" ng-disabled="displayOptions.currentPage == 0" ng-click="displayOptions.currentPage=displayOptions.currentPage-1">Previous</button>' +
            ' {{displayOptions.currentPage+1}}/{{numberOfPages()}} ' +
            '<button title="Shortcut: Ctrl + Right" class="btn btn-default" ng-disabled="displayOptions.currentPage >= totalLength/displayOptions.pageSize - 1" ng-click="displayOptions.currentPage=displayOptions.currentPage+1">Next</button>',
        link: {
            pre: function (scope) {
                scope.numberOfPages = function () {
                    var numPages = Math.ceil(scope.totalLength / scope.displayOptions.pageSize);
                    if (scope.displayOptions.currentPage == numPages) {
                        scope.displayOptions.currentPage = numPages - 1;
                    }
                    return numPages;
                };
            },
            post: function (scope, elm, attrs) {
                if (scope.paginationCallback) {
                    elm.find('button').click(function () {
                        scope.paginationCallback();
                    });
                }
            }
        }
    };
});
