angular.module('sm').factory('localStorage', function ($window) {
  var localStorage = $window.localStorage

  return {
    setItem: function (key: string, value) {
      if (localStorage) {
        if (value != null) {
          localStorage.setItem(key, angular.toJson(value))
        } else {
          localStorage.setItem(key, null)
        }
      } else {
        return false
      }
    },
    getItem: function (key: string) {
      if (localStorage) {
        return angular.fromJson(localStorage.getItem(key))
      } else {
        return false
      }
    },
    hasKey: function (key: string) {
      if (localStorage) {
        return localStorage.hasOwnProperty(key)
      } else {
        return false
      }
    },
    clear: function () {
      if (localStorage) {
        return localStorage.clear()
      } else {
        return false
      }
    }
  }
})
