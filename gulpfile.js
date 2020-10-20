// Get the version info
var pkg = require('./package.json')
if (!pkg.version) {
  console.error('No version information in package.json.')
  proccess.exit()
}

var assetsRoot = {
  src: 'assets/src/',
  dist: 'assets/dist/',
  dest: 'assets/prod/'
}

// Set up core routes
var modulesRoot = {
  src: assetsRoot.src + 'modules/',
  dist: assetsRoot.dist + 'modules/',
  dest: assetsRoot.dest + pkg.version + '/modules/'
}

var assetModuleList = {
  sm: ['App', 'Schedule', 'Generate', 'Browse', 'Search', 'Help', 'Index']
}

var assetTypes = {
  scripts: {
    paths: [
      '',
      'providers/',
      'directives/',
      'controllers/'
    ],
    selector: '*.js'
  },
  styles: {
    paths: [
      '',
      'styles/'
    ],
    selector: '*.css'
  },
  templates: {
    paths: [
      '',
      'templates/'
    ],
    selector: '*.html'
  }
}

var paths = {}
var distPaths = {}

const getPaths = (rootPath, pathsDict) => {
  for (var moduleName in assetModuleList) {
    subModuleList = assetModuleList[moduleName]

    pathsDict[moduleName] = {}
    for (var assetType in assetTypes) {
      var assetOpts = assetTypes[assetType]

      pathsDict[moduleName][assetType] = []

      assetOpts.paths.forEach(function (assetPath) {
        pathsDict[moduleName][assetType].push(rootPath + moduleName + '/**/' + assetPath + assetOpts.selector)
      })
    }
  }
}

var doFor = function (assetType, cb) {
  var streamResults = []
  for (var moduleName in assetModuleList) {
    streamResults.push(cb({
      src: paths[moduleName][assetType],
      dist: distPaths[moduleName][assetType],
      dest: modulesRoot.dest + moduleName + '/'
    }))
  }
  return streamResults
}

getPaths(modulesRoot.src, paths)

var fs = require('fs')
var dump = function (tvar) {
  fs.writeFileSync('./dump.json', JSON.stringify(tvar))
}

// Import required plugins
var gulp = require('gulp')
var htmlmin = require('gulp-htmlmin')
var ngmin = require('gulp-ngmin')
var uglify = require('gulp-uglify')
var clean = require('gulp-clean')
var concat = require('gulp-concat')
var rename = require('gulp-rename')
var sourcemaps = require('gulp-sourcemaps')
var replace = require('gulp-replace')
var es = require('event-stream')
var minifyCSS = require('gulp-minify-css')
var template = require('gulp-template')
var ts = require('gulp-typescript')

var tsProject = ts.createProject('tsconfig.json')

// Define Tasks
gulp.task('templates', function () {
  var mapped = doFor('templates', function (templatePaths) {
    return gulp.src(templatePaths.src)
      .pipe(htmlmin({
        collapseWhitespace: true,
        caseSensitive: true,
        keepClosingSlash: true
      }))
      .pipe(rename({ suffix: '.min' }))
      .pipe(gulp.dest(templatePaths.dest))
  })

  return es.concat.apply(null, mapped)
})

gulp.task('compile', function () {
  const tsOut = tsProject.src()
    .pipe(tsProject())
    .js.pipe(gulp.dest(modulesRoot.dist + 'sm/'))
  getPaths(modulesRoot.dist, distPaths)
  return tsOut
})

gulp.task('scripts', function () {
  var mapped = doFor('scripts', function (scriptPaths) {
    return gulp.src(scriptPaths.dist)
      .pipe(template({ modulePath: scriptPaths.dest }))
      .pipe(ngmin())
      .pipe(concat('dist.js'))
      .pipe(gulp.dest(scriptPaths.dest))
      .pipe(sourcemaps.init())
      .pipe(rename({ suffix: '.min' }))
      .pipe(uglify({ outSourceMap: 'dist.min.js' }))
      .pipe(sourcemaps.write({ inline: false, includeContent: false }))
    // HACK UNTIL GRUNT-UGLIFY HANDLES SOURCEMAPS CORRECTLY
      .pipe(replace('{"version":3,"file":"dist.min.js","sources":["dist.min.js"]', '{"version":3,"file":"dist.min.js","sources":["dist.js"]'))
      .pipe(gulp.dest(scriptPaths.dest))
  })

  return es.concat.apply(null, mapped)
})

gulp.task('styles', function () {
  var mapped = doFor('styles', function (stylePaths) {
    return gulp.src(stylePaths.src)
      .pipe(concat('dist.css'))
      .pipe(gulp.dest(stylePaths.dest))
      .pipe(minifyCSS())
      .pipe(rename({ suffix: '.min' }))
      .pipe(gulp.dest(stylePaths.dest))
  })

  return es.concat.apply(null, mapped)
})

gulp.task('watch', function () {
  doFor('templates', function (templatePaths) {
    gulp.watch(templatePaths.src, ['templates']).on('error', function () {
    })
  })
  doFor('scripts', function (scriptPaths) {
    gulp.watch(scriptPaths.dist, ['scripts']).on('error', function () {
    })
  })
  doFor('styles', function (stylesPaths) {
    gulp.watch(stylesPaths.src, ['styles']).on('error', function () {
    })
  })
})

gulp.task('clean', function () {
  return gulp.src(modulesRoot.dest, { read: false })
    .pipe(clean())
})

gulp.task('cleanAll', function () {
  return gulp.src([assetsRoot.dest + '/*', '!' + assetsRoot.dest + '.gitkeep'], { read: false })
    .pipe(clean())
})

gulp.task('build', ['clean', 'compile'], function () {
  return gulp.start('scripts', 'templates', 'styles')
})

gulp.task('default', ['build'])
