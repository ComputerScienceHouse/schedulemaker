angular.module('sm').filter('courseNum', function () {
  return function (course: Course) {
    if (course) {
      const coursePrefix = course.department.code ? course.department.code : course.department.number
      return coursePrefix + '-' + course.course
    }
  }
})
