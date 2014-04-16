// Set up variables

var root = {
	src: 'assets/src/',
	dest: 'assets/build/'
};

var paths = {
	templates: {
		src: root.src + 'templates/*.html',
		dest: root.dest + 'templates'
	}
};

// Import required plugins
var gulp = require('gulp');
var htmlmin = require('gulp-htmlmin');


// Define Tasks
gulp.task('templates', function() {
	return gulp.src(paths.templates.src)
		.pipe(htmlmin({
			collapseWhitespace: true,
			caseSensitive: true,
			keepClosingSlash: true
		}))
		.pipe(gulp.dest(paths.templates.dest));
});

gulp.task('watch', function() {
	gulp.watch(paths.templates.src, ['templates']);
});

gulp.task('build', ['templates']);
gulp.task('default', ['build']);
