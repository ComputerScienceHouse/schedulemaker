angular.module('sm').directive('svgTextLine', function () {
  return {
    link: function (scope, elm, attrs) {
      var text = attrs.svgTextLine
      var adjust = (scope.print) ? 1 : 0
      var cutoff = 25 + (adjust * -7)

      if (scope.grid.days.length > 3) {
        if (text.length > 14) {
          var element
          element = elm.get(0)
          element.setAttribute('textLength', (parseFloat(scope.grid.opts.daysWidth) - 1) + '%')
          element.setAttribute('lengthAdjust', 'spacingAndGlyphs')
        }
        if (text.length > cutoff) {
          text = text.slice(0, cutoff - 3) + '...'
        }
      }
      elm.text(text)
    }
  }
})
