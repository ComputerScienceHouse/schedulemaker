angular.module('sm').controller('ScheduleViewController', function ($scope, $location, $stateParams) {
  var id = $stateParams.id
  $scope.saveInfo = {
    url: $location.absUrl(),
    id: id
  }
})
