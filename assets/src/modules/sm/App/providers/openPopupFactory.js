angular.module('sm').factory('openPopup', function ($window) {
  /**
   * A utility to get top/left with a given width and height
   */
  var getPosition = function (width, height) {
    // Set defaults if either not set
    if (!width || !height) {
      width = 550
      height = 450
    }

    // Return an object and calculate correct position
    return {
      width: width,
      height: height,
      top: Math.round((screen.height / 2) - (height / 2)),
      left: Math.round((screen.width / 2) - (width / 2))
    }
  }

  return function (width, height) {
    var settings = ['about:blank']

    if (width !== true) {
      var pos = getPosition(width, height)
      settings.push('Loading...')
      settings.push('left=' + pos.left +
        ',top=' + pos.top +
        ',width=' + pos.width +
        ',height=' + pos.height +
        ',personalbar=0,toolbar=0,scrollbars=1,resizable=1')
    } else {
      settings.push('_blank')
    }

    return $window.open.apply($window, settings)
  }
})
