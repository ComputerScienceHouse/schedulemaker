angular.module('sm').directive('navCloseOnMobile', function () {
  return {
    restrict: 'A',
    link: function (scope, elm) {
      const nav = $(elm)
      $(elm).find('li').click(function () {
        ($('.navbar-collapse.in') as any).collapse('hide')
      })
    }
  }
})
