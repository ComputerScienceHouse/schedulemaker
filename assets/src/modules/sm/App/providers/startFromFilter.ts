angular.module('sm').filter('startFrom', function () {
  return function (input, start: number) {
    start = +start // parse to int
    return input.slice(start)
  }
})
