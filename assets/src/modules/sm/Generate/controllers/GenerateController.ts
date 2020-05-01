angular.module('sm').controller('GenerateController', function ($scope, globalKbdShortcuts, $http, $filter, localStorage, uiDayFactory) {
  // Check if we are forking a schedule
  if (localStorage.hasKey('forkSchedule')) {
    // Get the schedule from sessions storage
    const forkSchedule = localStorage.getItem('forkSchedule')
    if (forkSchedule != null) {
      // Clear it so we don't fork again
      localStorage.setItem('forkSchedule', null)

      const days = uiDayFactory()

      // Init state, but save UI settings
      const savedUI = $scope.state.ui
      $scope.initState()
      $scope.state.ui = savedUI

      // Reload, then null term
      if ($scope.state.ui.temp_savedScheduleTerm) {
        $scope.state.requestOptions.term = +$scope.state.ui.temp_savedScheduleTerm
        $scope.state.ui.temp_savedScheduleTerm = null
      }

      for (let i = forkSchedule.length; i--;) {
        const course: Course = forkSchedule[i]

        // If it's a real course
        if (course.courseNum !== 'non') {
          $scope.courseCart.create.fromExistingScheduleCourse(course)
        } else {
          // Make a non-course item
          const nonCourse = {
            title: course.title,
            days: [days[parseInt(course.times[0].day)]],
            startTime: parseInt(course.times[0].start),
            endTime: parseInt(course.times[0].end)
          }
          let mergedNonCourse: boolean = false

          // Try to merge this non course with other similar ones
          for (let n = 0, l = $scope.state.nonCourses.length; n < l; n++) {
            const otherNonCourse = $scope.state.nonCourses[n]
            if (otherNonCourse.title === nonCourse.title &&
              otherNonCourse.startTime === nonCourse.startTime &&
              otherNonCourse.endTime === nonCourse.endTime) {
              otherNonCourse.days = otherNonCourse.days.concat(nonCourse.days)
              mergedNonCourse = true
              break
            }
          }

          if (!mergedNonCourse) {
            $scope.state.nonCourses.push(nonCourse)
          }
        }
      }
    }
  }

  // Decorate some course helpers for our dynamic items directive
  $scope.courses_helpers = {
    add: $scope.courseCart.create.blankCourse,
    remove: function (index: number) {
      $scope.courseCart.remove.byIndex(index - 1)
      if ($scope.state.courses.length === 0) {
        $scope.courses_helpers.add()
      }
    }
  }

  $scope.ensureCorrectEndDay = function () {
    if ($scope.state.drawOptions.startDay > $scope.state.drawOptions.endDay) {
      $scope.state.drawOptions.endDay = $scope.state.drawOptions.startDay
    }
  }
  $scope.ensureCorrectEndTime = function () {
    if ($scope.state.drawOptions.startTime >= $scope.state.drawOptions.endTime) {
      $scope.state.drawOptions.endTime = $scope.state.drawOptions.startTime + 60
    }
  }

  $scope.numberOfPages = function () {
    return Math.ceil($scope.state.schedules.length / $scope.state.displayOptions.pageSize)
  }

  $scope.scrollToSchedules = function () {
    // I know this is bad, but I'm lazy
    setTimeout(function () {
      $('input:focus').blur()
      $('html, body').animate({
        scrollTop: $('#master_schedule_results').offset().top - 65
      }, 500)
    }, 100)
  }

  $scope.generationStatus = 'D'

  // Overwrite app-level generateController
  $scope.generateSchedules = function () {
    $scope.generationStatus = 'L'

    const requestData = {
      term: $scope.state.requestOptions.term,
      courseCount: $scope.state.courses.length,
      nonCourseCount: $scope.state.nonCourses.length,
      noCourseCount: $scope.state.noCourses.length
    }

    // Set the actual number of courses being sent
    let actualCourseIndex: number = 1

    // Loop through the course cart
    for (let courseIndex = 0; courseIndex < $scope.state.courses.length; courseIndex++) {
      // Set up our variables
      const course = $scope.state.courses[courseIndex]
      const fieldName = 'courses' + (actualCourseIndex) + 'Opt[]'
      requestData['courses' + actualCourseIndex] = course.search
      requestData[fieldName] = []
      let sectionCount: number = 0

      // Add selected sections to the request
      for (let sectionIndex = 0; sectionIndex < course.sections.length; sectionIndex++) {
        if (course.sections[sectionIndex].selected) {
          requestData[fieldName].push(course.sections[sectionIndex].id)
          sectionCount++
        }
      }

      // If no sections are selected, remove the course info and decrease the actual course index
      if (sectionCount === 0) {
        requestData.courseCount--
        delete requestData['courses' + actualCourseIndex]
        delete requestData[fieldName]
      } else {
        actualCourseIndex++
      }
    }

    // Set the request data for the non courses
    for (let nonCourseIndex = 0; nonCourseIndex < $scope.state.nonCourses.length; nonCourseIndex++) {
      const nonCourse = $scope.state.nonCourses[nonCourseIndex]
      const index: number = (nonCourseIndex + 1)
      const fieldName = 'nonCourse'
      requestData[fieldName + 'Title' + index] = nonCourse.title
      requestData[fieldName + 'StartTime' + index] = nonCourse.startTime
      requestData[fieldName + 'EndTime' + index] = nonCourse.endTime
      requestData[fieldName + 'Days' + index + '[]'] = nonCourse.days
    }

    // Set the request data for the no courses stuff
    for (let noCourseIndex = 0; noCourseIndex < $scope.state.noCourses.length; noCourseIndex++) {
      const noCourse = $scope.state.noCourses[noCourseIndex]
      const index = (noCourseIndex + 1)
      const fieldName = 'noCourse'
      requestData[fieldName + 'StartTime' + index] = noCourse.startTime
      requestData[fieldName + 'EndTime' + index] = noCourse.endTime
      requestData[fieldName + 'Days' + index + '[]'] = noCourse.days
    }

    // Actually make the request
    $http.post('/generate/getMatchingSchedules', $.param(requestData))
      .success(function (data, status, headers, config) {
        ga('send', 'event', 'generate', 'schedule')
        $scope.generationStatus = 'D'

        // If no errors happened
        if (!data.error && !data.errors) {
          // Check if any schedules were generated
          if (data.schedules === undefined || data.schedules == null || data.schedules.length === 0) {
            $scope.resultError = 'There are no matching schedules!'
          } else {
            // Otherwise reset page, scroll to schedules and clear errors
            $scope.state.displayOptions.currentPage = 0
            $scope.scrollToSchedules()

            for (let count = 0; count < data.schedules.length; count++) {
              data.schedules[count][0].initialIndex = count
            }

            $scope.state.schedules = data.schedules
            $scope.resultError = ''
          }
        } else if (!data.error && data.errors) {
          // Display errors
          $scope.resultError = data.errors.reduce(function (totals, error) {
            return totals + ', ' + error.msg
          }, '')
          console.log('Schedule Generation Errors:', data)
        } else {
          // Display errors
          $scope.resultError = data.msg
          console.log('Schedule Generation Error:', data)
        }
      })
      .error(function (data, status, headers, config) {
        $scope.generationStatus = 'D'
        // Display errors
        $scope.resultError = 'Fatal Error: An internal server error occurred'
        console.log('Fatal Schedule Generation Error:', data)
      })
  }

  // Bind keyboard shortcuts
  globalKbdShortcuts.bindCtrlEnter($scope.generateSchedules)

  // Bind arrow key pagination
  globalKbdShortcuts.bindPagination(function () {
    if (this.keyCode === 39 && $scope.state.displayOptions.currentPage + 1 < $scope.numberOfPages()) {
      $scope.state.displayOptions.currentPage++
      $scope.scrollToSchedules()
    } else if (this.keyCode === 37 && $scope.state.displayOptions.currentPage - 1 >= 0) {
      $scope.state.displayOptions.currentPage--
      $scope.scrollToSchedules()
    }
  })

  // If the previous page set to generate schedules
  if ($scope.state.ui.action_generateSchedules) {
    $scope.state.ui.action_generateSchedules = false
    $scope.generateSchedules()
  }

  $scope.resetGenerate = function () {
    $scope.state.courses = $scope.state.courses.filter(function (course) {
      return !course.fromSelect
    })

    $scope.state.nonCourses.length = 0
    $scope.state.noCourses.length = 0

    // Let the lower controllers add a course
    $scope.$broadcast('checkForEmptyCourses')
  }
})
