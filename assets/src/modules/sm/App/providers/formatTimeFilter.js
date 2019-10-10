angular.module('sm').filter("courseNum", function () {
    return function (course) {
        if (course) {
            return (course.department.code ? course.department.code :
                course.department.number) + "-" + course.course;
        }
    };
});
