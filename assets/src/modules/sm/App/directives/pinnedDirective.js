angular.module('sm').directive('pinned', function () {
  return {
    restrict: 'A',
    link: function (scope, elm, attrs) {
      var $window = $(window)
      var sizer = elm.parent().parent().find('.pinned-sizer')
      var $footer = $('footer.main')
      var fO; var sO
      var updateHeight = function () {
        fO = $window.height() - $footer.offset().top - $footer.outerHeight()
        sO = sizer.height()
        elm.css('height', (fO > 0) ? (sO - fO) : (sO))
      }
      setTimeout(function () {
        updateHeight()
        $(window).on('resize', updateHeight)
      }, 100)

      if (typeof scope.schools !== 'undefined') {
        scope.$watch('schools', function () {
          setTimeout(updateHeight, 200)
        })
      }

      elm.addClass('pinned')
    }
  }
})
