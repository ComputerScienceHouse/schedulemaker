angular.module('sm').controller('StatusController', function ($scope, $http) {
  $scope.logs = []

  $http.get('/status')
    .success(function (data, status, headers, config) {
      if (status === 200 && !data.error) {
        $scope.logs = data
      } else {
        // TODO: Better error checking
        alert($scope.error)
      }
    })

  $scope.timeConvert = function (UnixTimestamp) {
    const a = new Date(+UnixTimestamp * 1000)
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    const year = a.getFullYear()
    const month = months[a.getMonth()]
    const date = a.getDate()
    const hour = a.getHours()
    let min: number | string = a.getMinutes()
    let sec: number | string = a.getSeconds()
    if (sec <= 10) sec = '0' + sec
    if (min <= 10) min = '0' + min
    const time = month + ' ' + date + ' ' + year + ' ' + hour + ':' + min + ':' + sec
    return time
  }
})
