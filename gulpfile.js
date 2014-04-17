// Set up core routes
var modulesRoot = {
	src: 'assets/src/modules',
	dest: 'assets/prod/modules'
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
var uglify = require('gulp-uglify');


// Define Tasks
gulp.task('templates', function() {

	doFor('templates', function(templatePaths) {
		gulp.src(templatePaths.src)
		.pipe(htmlmin({
			collapseWhitespace: true,
			caseSensitive: true,
			keepClosingSlash: true
		}))
		.pipe(gulp.dest(templatePaths.dest));
	});
});

// Define Tasks
gulp.task('js', function() {
	doFor('js', function(jsPaths) {
		gulp.src(templatePaths.src)
		.pipe(htmlmin({
			collapseWhitespace: true,
			caseSensitive: true,
			keepClosingSlash: true
		}))
		.pipe(gulp.dest(jsPaths.dest));
	});
});

gulp.task('watch', function() {
	gulp.watch(paths.templates.src, ['templates']);
});

gulp.task('build', ['templates']);
gulp.task('default', ['build']);
