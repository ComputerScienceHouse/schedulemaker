angular.module('sm').directive('svgTextLine', function () {
  return {
    link: function (scope, elm, attrs) {
      let text = attrs.svgTextLine
      const adjust = (scope.print) ? 1 : 0
      const cutoff = 25 + (adjust * -7)

      if (scope.grid.days.length > 3) {
        if (text.length > 14) {
          const element = elm.get(0)
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
