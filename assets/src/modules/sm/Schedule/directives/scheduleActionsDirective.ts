/**
 * Several endpoint abstractions for the schedules
 */
angular.module('sm').directive('scheduleActions', function ($http, $q, shareServiceInfo, openPopup, localStorage, $state, $timeout) {
  const serializer = new XMLSerializer()

  function scheduleActions (scope, elm) {
    const getSavedInfo = function () {
      // See if we already have saved info
      if (scope.saveInfo) {
        const defferred = $q.defer()
        defferred.resolve(scope.saveInfo)
        return defferred.promise
      }
      // If not create it
      const schedule = angular.copy(scope.schedule)
      scope.status = 'L'

      // Create the request params as all strings with correct keys
      const params = {
        data: JSON.stringify({
          startday: '' + scope.state.drawOptions.startDay,
          endday: '' + scope.state.drawOptions.endDay,
          starttime: '' + scope.state.drawOptions.startTime,
          endtime: '' + scope.state.drawOptions.endTime,
          building: '' + scope.state.drawOptions.bldgStyle,
          term: '' + scope.state.requestOptions.term,
          schedule: schedule
        }),
        svg: serializer.serializeToString(elm.find('svg').get(0))
      }

      // Post the schedule and return a promise
      return $http.post('/schedule/new', $.param(params), {
        requestType: 'json'
      })
        .then(function (request) {
          if (request.status === 200 && typeof request.data.error === 'undefined') {
            // save the saveInfo and return it
            scope.saveInfo = request.data
            scope.status = 'D'
            return request.data
          } else {
            return $q.reject('Save Error:' + request.data.msg)
          }
        })
    }

    scope.scheduleActions = {

      save: function (saveType) {
        if (saveType === 'create') {
          ga('send', 'event', 'schedule', 'save')
          window.DD_RUM &&
          window.DD_RUM.addAction('Schedule', {
            type: 'Save'
          })
          getSavedInfo().then(function (data) {
            scope.notification = 'This schedule can be accessed at ' +
              '<a href="' + data.url + '" target="_blank">' +
              data.url + '</a><br><em>This schedule will be removed' +
              ' after 3 months of inactivity</em>'
          }, function (error) {
            console.log(error)
            scope.notification = error
          })
        } else {
          ga('send', 'event', 'schedule', 'fork')
          window.DD_RUM &&
          window.DD_RUM.addAction('Schedule', {
            type: 'Fork'
          })

          localStorage.setItem('forkSchedule', scope.schedule)
          $state.go('generate')
        }
      },

      shareToService: function ($event, serviceName, newWindow) {
        ga('send', 'event', 'schedule', 'share', serviceName)
        window.DD_RUM &&
        window.DD_RUM.addAction('Schedule', {
          type: 'Share',
          serviceName: serviceName
        })
        $event.preventDefault()
        scope.status = 'L'
        if (serviceName && serviceName in shareServiceInfo) {
          const service = shareServiceInfo[serviceName]

          // Create a popup in click context to workaround blockers
          const popup = openPopup(newWindow)

          getSavedInfo().then(function (data) {
            scope.status = 'D'
            popup.location = service(data.url)
          })
        }
      },

      shareToEmail: function ($event) {
        ga('send', 'event', 'schedule', 'share', 'email')
        window.DD_RUM &&
        window.DD_RUM.addAction('Schedule', {
          type: 'Share',
          subtype: 'Email'
        })
        $event.preventDefault()

        getSavedInfo().then(function (data) {
          const body = 'Check out my schedule at: ' + data.url

          // Open a mailto link
          window.location.href = 'mailto:?body=' +
            encodeURIComponent(body)
        })
      },

      shareToDirectLink: function ($event) {
        ga('send', 'event', 'schedule', 'share', 'link')
        window.DD_RUM &&
        window.DD_RUM.addAction('Schedule', {
          type: 'Share',
          subtype: 'Link'
        })
        $event.preventDefault()

        scope.scheduleActions.save('create')
      },

      downloadiCal: function ($event) {
        ga('send', 'event', 'schedule', 'download', 'iCal')
        window.DD_RUM &&
        window.DD_RUM.addAction('Schedule', {
          type: 'Download',
          subtype: 'iCal'
        })
        $event.preventDefault()

        getSavedInfo().then(function (data) {
          window.location.href = data.url + '/ical'
        })
      },

      downloadImage: function ($event) {
        ga('send', 'event', 'schedule', 'download', 'image')
        window.DD_RUM &&
        window.DD_RUM.addAction('Schedule', {
          type: 'Download',
          subtype: 'Image'
        })
        $event.preventDefault()

        const popup = openPopup(true)

        getSavedInfo().then(function (data) {
          popup.location = ('http://' + window.location.hostname +
            '/img/schedules/' + parseInt(data.id, 16) + '.png')
        })
      },

      print: function () {
        ga('send', 'event', 'schedule', 'print')
        window.DD_RUM &&
        window.DD_RUM.addAction('Schedule', {
          type: 'Print'
        })

        const reloadSchedule = angular.copy(scope.state.drawOptions)
        reloadSchedule.term = scope.state.requestOptions.term
        reloadSchedule.courses = scope.schedule

        const popup = openPopup(920, 800)

        popup.localStorage.setItem('reloadSchedule', angular.toJson(reloadSchedule))
        popup.document.title = 'My Schedule'
        popup.location = 'http://' + window.location.hostname + '/schedule/render/print'
      },

      hide: function () {
        ga('send', 'event', 'schedule', 'hide')
        window.DD_RUM &&
        window.DD_RUM.addAction('Schedule', {
          type: 'Hide'
        })
        const appstate = scope.$parent.$parent.state
        const pageStartIndex = appstate.displayOptions.currentPage * appstate.displayOptions.pageSize

        appstate.schedules.splice(pageStartIndex + scope.$index, 1)
      }
    }
  };

  return {

    /**
     * Save a schedule, given the respective parameters
     */
    link: {
      pre: scheduleActions
    }
  }
})
