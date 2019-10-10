angular.module('sm').filter('partition', function ($cacheFactory) {
  var arrayCache = $cacheFactory('partition')
  return function (arr, size) {
    var parts = []; var cachedParts
    var jsonArr = JSON.stringify(arr)
    for (var i = 0; i < arr.length; i += size) {
      parts.push(arr.slice(i, i + size))
    }
    cachedParts = arrayCache.get(jsonArr)
    if (JSON.stringify(cachedParts) === JSON.stringify(parts)) {
      return cachedParts
    }
    arrayCache.put(jsonArr, parts)

    return parts
  }
})
