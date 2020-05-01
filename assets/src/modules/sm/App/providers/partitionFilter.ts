angular.module('sm').filter('partition', function ($cacheFactory) {
  const arrayCache = $cacheFactory('partition')
  return function (arr, size) {
    const parts = []
    const jsonArr = JSON.stringify(arr)
    for (let i = 0; i < arr.length; i += size) {
      parts.push(arr.slice(i, i + size))
    }
    const cachedParts = arrayCache.get(jsonArr)
    if (JSON.stringify(cachedParts) === JSON.stringify(parts)) {
      return cachedParts
    }
    arrayCache.put(jsonArr, parts)

    return parts
  }
})
