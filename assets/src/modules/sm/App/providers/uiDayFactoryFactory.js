angular.module('sm').factory('uiDayFactory', function () {
  var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

  return function () {
    return days
  }
})
