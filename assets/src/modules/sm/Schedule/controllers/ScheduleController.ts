angular.module('sm').controller('ScheduleController', function ($scope, parsedSchedule) {
  if (!parsedSchedule.error) {
    if (parsedSchedule.hasOwnProperty('courses')) {
      $scope.schedule = parsedSchedule.courses
    } else if (parsedSchedule.hasOwnProperty('schedule')) {
      $scope.schedule = parsedSchedule.schedule
    } else {
      $scope.schedule = []
    }
  } else {
    $scope.schedule = []
  }

  if ($scope.schedule.length > 0) {
    $scope.overrideDrawOptions = {}

    // Set the correct draw options
    for (var key in $scope.state.drawOptions) {
      var overrideValue = parsedSchedule[key]
      if (typeof overrideValue === 'undefined' || overrideValue === null) {
        overrideValue = $scope.state.drawOptions[key]
      }
      $scope.overrideDrawOptions[key] = overrideValue
    }

    // Set image property
    if (parsedSchedule.hasOwnProperty('image')) {
      $scope.imageSupport = parsedSchedule.image
    } else {
      $scope.imageSupport = true
    }

    // Set the correct term,
    $scope.state.ui.temp_savedScheduleTerm = parsedSchedule.term
  }

  $scope.$on('$destroy', function () {
    $scope.imageSupport = true
    $scope.overrideDrawOptions = null
  })
})
