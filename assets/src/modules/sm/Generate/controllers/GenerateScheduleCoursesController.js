angular.module('sm').controller('GenerateScheduleCoursesController', function ($scope, $http, $q, $timeout) {
  // Check if a course needs to be added
  var checkEmptyCourses = function () {
    if ($scope.state.courses.length === 0 || $scope.courseCart.count.all.coursesFromSelect() === 0) {
      $scope.courses_helpers.add()
    }
  }
  checkEmptyCourses()
  $scope.$on('checkForEmptyCourses', checkEmptyCourses)

  // Create a way to cancel repeated searches
  var canceler = {}
  $scope.search = function (course) {
    // Check if the course id already has an ajax request and end it.
    if (canceler.hasOwnProperty(course.id)) {
      canceler[course.id].resolve()
    }

    // Create a new request
    canceler[course.id] = $q.defer()

    // Set the course to loading status
    course.status = 'L'

    // Create the new search request
    var searchRequest = $http.post('/generate/getCourseOpts', $.param({
      course: course.search,
      term: $scope.state.requestOptions.term,
      ignoreFull: $scope.state.requestOptions.ignoreFull
    }), {
      // Here is where the request gets canceled from above
      timeout: canceler[course.id].promise
    }).success(function (data, status, headers, config) {
      // Set loading status to done
      course.status = 'D'

      // If there has been no error
      if (!data.error) {
        // set isError and selected to their defaults
        for (var c = 0; c < data.length; ++c) {
          data[c].isError = false
          data[c].selected = true
        }

        // Set the data to course's sections
        course.sections = data
      } else {
        // Make a faux-result with isError being true
        course.sections = [{ isError: true, error: data }]
      }
    })
      .error(function (data, status, headers, config) {
        // Most likely typed too fast, ignore and set status to done.
        course.status = 'D'
      })
  }

  // Listen for changes in request options
  $scope.$watch('state.requestOptions', function (newRO, oldRO) {
    if (angular.equals(newRO, oldRO)) {
      return
    }
    for (var i = 0, l = $scope.state.courses.length; i < l; i++) {
      var course = $scope.state.courses[i]

      // Only re-search if the search field was valid anyways
      if (course.search.length > 3) {
        $scope.search(course)
      }
    }
  }, true)

  // Reset the page size if the new size leaves the current page out of range
  $scope.$watch('state.displayOptions.pageSize', function (newPS, oldPS) {
    if (newPS === oldPS) {
      return
    }
    if ($scope.state.displayOptions.currentPage + 1 > $scope.numberOfPages()) {
      $scope.state.displayOptions.currentPage = $scope.numberOfPages() - 1
    }
  })

  // Watch for changes in the course cart
  $scope.$watch('state.courses', function (newCourses, oldCourses) {
    for (var i = 0, l = newCourses.length; i < l; i++) {
      var newCourse = newCourses[i]

      // find the old course that the new one came from
      var oldCourse = oldCourses.filter(function (filterCourse) {
        return filterCourse.id === newCourse.id
      })[0]

      // It's a new course, so mock an old one for comparisons sake
      if (typeof oldCourse === 'undefined') {
        oldCourse = {
          search: '',
          sections: []
        }
      }

      // Check to see if the search field changed, or was valid
      if (newCourse.search !== oldCourse.search && newCourse.search.length > 3) {
        // Find the new results!
        $scope.search(newCourse)
      } else if (newCourse.search !== oldCourse.search) {
        // The search field has been changed to be too short, remove sections
        newCourse.sections = []
        if (canceler.hasOwnProperty(newCourse.id)) {
          canceler[newCourse.id].resolve()
          newCourse.status = 'D'
        }
      }
    }
  }, true)
})
