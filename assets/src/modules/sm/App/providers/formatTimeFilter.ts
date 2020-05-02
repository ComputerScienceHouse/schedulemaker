angular.module('sm').filter('courseNum', function () {
  return function (course: Course) {
    if (course) {
      return (course.department.code ? course.department.code
        : course.department.number) + '-' + course.course
    }
  }
})
