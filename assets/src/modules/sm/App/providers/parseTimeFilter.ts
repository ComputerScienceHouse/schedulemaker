angular.module('sm').filter('parseTime', function () {
  return function (rawTime) {
    const matchedTime = rawTime.match(/([0-9]|1[0-2]):([0-9]{2})(am|pm)/)
    if (matchedTime) {
      if (matchedTime[3] === 'am' && parseInt(matchedTime[1]) === 12) {
        return parseInt(matchedTime[2])
      } else if (matchedTime[3] === 'pm') {
        matchedTime[1] = parseInt(matchedTime[1]) + 12
      }
      return (parseInt(matchedTime[1]) * 60) + parseInt(matchedTime[2])
    } else {
      return false
    }
  }
})
