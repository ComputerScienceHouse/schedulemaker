/* eslint-disable standard/no-callback-literal */

// Get the version info
const pkg = require('./package.json')
if (!pkg.version) {
  console.error('No version information in package.json.')
  proccess.exit()
}

const assetsRoot = {
  src: 'assets/src/',
  dist: 'assets/dist/',
  dest: 'assets/prod/'
}

// Set up core routes
const modulesRoot = {
  src: assetsRoot.src + 'modules/',
  dist: assetsRoot.dist + 'modules/',
  dest: assetsRoot.dest + pkg.version + '/modules/'
}

const assetModuleList = {
  sm: ['App', 'Schedule', 'Generate', 'Browse', 'Search', 'Help', 'Index']
}

const assetTypes = {
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

const paths = {}
const distPaths = {}

const getPaths = (rootPath, pathsDict) => {
  for (const moduleName in assetModuleList) {
    subModuleList = assetModuleList[moduleName]

    pathsDict[moduleName] = {}
    for (const assetType in assetTypes) {
      const assetOpts = assetTypes[assetType]

      pathsDict[moduleName][assetType] = []

      assetOpts.paths.forEach(function (assetPath) {
        pathsDict[moduleName][assetType].push(rootPath + moduleName + '/**/' + assetPath + assetOpts.selector)
      })
    }
  }
}

const doFor = function (assetType, cb) {
  const streamResults = []
  for (const moduleName in assetModuleList) {
    streamResults.push(cb({
      src: paths[moduleName][assetType],
      dist: distPaths[moduleName][assetType],
      dest: modulesRoot.dest + moduleName + '/'
    }))
  }
  return streamResults
}

getPaths(modulesRoot.src, paths)

// Import required plugins
const gulp = require('gulp')
const htmlmin = require('gulp-htmlmin')
const ngAnnotate = require('gulp-ng-annotate')
const uglify = require('gulp-uglify')
const concat = require('gulp-concat')
const rename = require('gulp-rename')
const sourcemaps = require('gulp-sourcemaps')
const replace = require('gulp-replace')
const es = require('event-stream')
const minifyCSS = require('gulp-minify-css')
const template = require('gulp-template')
const ts = require('gulp-typescript')
const del = require('del')
const vinylPaths = require('vinyl-paths')

const tsProject = ts.createProject('tsconfig.json')

// Define Tasks
gulp.task('templates', function (done) {
  const mapped = doFor('templates', function (templatePaths) {
    return gulp.src(templatePaths.src)
      .pipe(htmlmin({
        collapseWhitespace: true,
        caseSensitive: true,
        keepClosingSlash: true
      }))
      .pipe(rename({ suffix: '.min' }))
      .pipe(gulp.dest(templatePaths.dest))
  })

  done()
  return es.concat.apply(null, mapped)
})

gulp.task('compile', function () {
  const tsOut = tsProject.src()
    .pipe(tsProject())
    .js.pipe(gulp.dest(modulesRoot.dist + 'sm/'))
  getPaths(modulesRoot.dist, distPaths)
  return tsOut
})

gulp.task('scripts', function (done) {
  const mapped = doFor('scripts', function (scriptPaths) {
    return gulp.src(scriptPaths.dist)
      .pipe(template({ modulePath: scriptPaths.dest }))
      .pipe(ngAnnotate())
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

  done()
  return es.concat.apply(null, mapped)
})

gulp.task('styles', function (done) {
  const mapped = doFor('styles', function (stylePaths) {
    return gulp.src(stylePaths.src)
      .pipe(concat('dist.css'))
      .pipe(gulp.dest(stylePaths.dest))
      .pipe(minifyCSS({ advanced: false, processImport: true, keepSpecialComments: 0 }))
      .pipe(rename({ suffix: '.min' }))
      .pipe(gulp.dest(stylePaths.dest))
  })

  done()
  return es.concat.apply(null, mapped)
})

gulp.task('watch', function () {
  doFor('templates', function (templatePaths) {
    gulp.watch(templatePaths.src, gulp.series('templates')).on('error', function () {
    })
  })
  doFor('scripts', function (scriptPaths) {
    gulp.watch(scriptPaths.dist, gulp.series('scripts')).on('error', function () {
    })
  })
  doFor('styles', function (stylesPaths) {
    gulp.watch(stylesPaths.src, gulp.series('styles')).on('error', function () {
    })
  })
})

gulp.task('clean', function () {
  return gulp.src(modulesRoot.dest, { read: false, allowEmpty: true })
    .pipe(vinylPaths(del))
})

gulp.task('cleanAll', function () {
  return gulp.src([assetsRoot.dest + '/*', '!' + assetsRoot.dest + '.gitkeep'], { read: false, allowEmpty: true })
    .pipe(vinylPaths(del))
})

gulp.task('build', gulp.series('clean', 'compile', gulp.parallel('scripts', 'templates', 'styles')))

gulp.task('default', gulp.series('build'))
