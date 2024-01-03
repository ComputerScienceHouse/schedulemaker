// For now, not a service
angular.module('sm').filter('RMPUrl', function () {
  return function (input: string) {
    if (input && input !== 'TBA') {
      const EscapedName = encodeURIComponent(input)
      return (
        '<a target="_blank" href="https://www.ratemyprofessors.com/search/professors/807?q=' +
        EscapedName +
        '">' +
        input +
        '</a>'
      )
    } else {
      return '<a href="#">' + input + '</a>'
    }
  }
})
