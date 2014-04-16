// Set up variables

var root = {
	src: 'assets/src/',
	dest: 'assets/prod/'
};

var paths = {
	templates: {
		src: root.src + 'templates/*.html',
		dest: root.dest + 'templates'
	},
	js: {
		sm: {
			src: root.src + 'js/sm/**/*.js',
			dest: root.dest + 'js/sm/app.js'
		}
	}
};

// Import required plugins
var gulp = require('gulp');
var htmlmin = require('gulp-htmlmin');
var uglify = require('gulp-uglify');


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

// Define Tasks
gulp.task('js', function() {
	return gulp.src(paths.templates.src)
		.pipe(ngmin({
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
