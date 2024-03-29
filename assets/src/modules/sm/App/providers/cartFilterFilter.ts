angular.module('sm').filter('cartFilter', function () {
  return function (input, $scope) {
    const parsed = []
    const SSFN = $scope.courseCart.count.course.selectedSections
    angular.forEach(input, function (course: Course) {
      if (course && course.sections.length > 0 && !course.sections[0].isError && SSFN(course) > 0) {
        parsed.push(course)
      }
    })
    return parsed
  }
})
