angular.module('sm').filter('translateDay', function () {
  return function (day: number) {
    // Modulo it to make sure we get the correct days
    day = day % 7

    // Now switch on the different days
    switch (day) {
      case 0:
        return 'Sun'
      case 1:
        return 'Mon'
      case 2:
        return 'Tue'
      case 3:
        return 'Wed'
      case 4:
        return 'Thu'
      case 5:
        return 'Fri'
      case 6:
        return 'Sat'
      default:
        return null
    }
  }
})
