// Set up core routes
var modulesRoot = {
	src: 'assets/src/modules/',
	dest: 'assets/prod/modules/'
};

var assetModuleList = {
	sm: ['App', 'Schedule', 'Generate', 'Browse', 'Search', 'Help', 'Index']
};

var assetTypes = {
	js: {
		paths: [
	        '',
	        'providers/',
	        'directives/',
	        'controllers/',
        ],
        selector: '*.js'
	},
	styles: {
		paths: [
	        '',
	        'styles/',
        ],
        selector: '*.css'
	},
	templates: {
		paths: [
	        '',
	        'templates/',
        ],
        selector: '*.html'
	},
};

var paths = {};


for(var moduleName in assetModuleList) {
	subModuleList = assetModuleList[moduleName];
	
	paths[moduleName] = {};
	for(var assetType in assetTypes) {
		
		var assetOpts = assetTypes[assetType];
		
		paths[moduleName][assetType] = [];
		
		assetOpts.paths.forEach(function(assetPath) {
			paths[moduleName][assetType].push(modulesRoot.src + moduleName + '/**/' + assetPath + assetOpts.selector);
		});
	}
}

var doFor = function(assetType, cb) {
	for(var moduleName in assetModuleList) {
		cb({
			src: paths[moduleName][assetType],
			dest: modulesRoot.dest + moduleName + '/'
		});
	}
};

var fs = require('fs');
var dump = function(tvar) {
	fs.writeFileSync("./dump.json", JSON.stringify(tvar)); 
}



// Import required plugins
var gulp = require('gulp');
var htmlmin = require('gulp-htmlmin');
var ngmin = require('gulp-ngmin');
var uglify = require('gulp-uglify');
var clean = require('gulp-clean');
var concat = require('gulp-concat');
var rename = require('gulp-rename');

// Define Tasks
gulp.task('templates', function() {

	doFor('templates', function(templatePaths) {
		gulp.src(templatePaths.src)
		.pipe(htmlmin({
			collapseWhitespace: true,
			caseSensitive: true,
			keepClosingSlash: true
		}))
		.pipe(rename({suffix: '.min'}))
		.pipe(gulp.dest(templatePaths.dest));
	});
});

// Define Tasks
gulp.task('js', function() {
	doFor('js', function(jsPaths) {
		gulp.src(jsPaths.src)
		.pipe(ngmin())
		.pipe(uglify({outSourceMap: true}))
		//.pipe(rename({suffix: '.min'}))
		//.pipe(gulp.dest(jsPaths.dest))
		//.pipe(concat('dist.min.js'))
		.pipe(gulp.dest(jsPaths.dest));
	});
});

gulp.task('watch', function() {
	
	doFor('templates', function(templatePaths) {
		gulp.watch(templatePaths, ['templates']);
	});
	
	doFor('js', function(jsPaths) {
		gulp.watch(jsPaths, ['js']);
	});
	
});

gulp.task('clean', function() {
  return gulp.src(modulesRoot.dest, {read: false})
    .pipe(clean());
});

gulp.task('build', /*['clean'],*/ function() {
	gulp.start('templates', 'js');
});

gulp.task('default', ['build']);
