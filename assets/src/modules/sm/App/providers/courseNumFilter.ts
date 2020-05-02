angular.module('sm').filter('formatTime', function () {
  return function (minutes) {
    minutes = minutes % 1440

    // Figure out how many hours
    let hours = Math.floor(minutes / 60)

    // Figure out how many minutes
    let remMinutes: string | number = minutes % 60

    // Correct for AM/PM
    let ampm
    if (hours >= 12) {
      ampm = 'pm'
      hours -= 12
    } else {
      ampm = 'am'
    }

    // Correct for 0 hour
    if (hours === 0) {
      hours = 12
    }

    // Correct minutes less than 10 min
    if (remMinutes < 10) {
      remMinutes = '0' + remMinutes
    }
    // Put it together
    return hours + ':' + remMinutes + ampm
  }
})
