angular.module('sm').directive("selectTerm", function () {
    return {
        restrict: 'A',
        template: '<select class="form-control" ng-options="term.value as term.name group by term.group for term in termList" ng-model="state.requestOptions.term"></select>',
    };
});
