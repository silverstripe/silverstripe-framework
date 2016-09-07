const packageJson = require('./package.json');
const autoprefixer = require('autoprefixer');
const babelify = require('babelify'); // eslint-disable-line no-unused-vars
const browserify = require('browserify');
const eventStream = require('event-stream');
const glob = require('glob');
const gulp = require('gulp');
const coffee = require('gulp-coffee');
const concat = require('gulp-concat');
const merge = require('merge-stream');
const order = require('gulp-order');
const babel = require('gulp-babel');
const diff = require('gulp-diff');
const gulpif = require('gulp-if');
const notify = require('gulp-notify');
const postcss = require('gulp-postcss');
const sass = require('gulp-sass');
const sourcemaps = require('gulp-sourcemaps');
const uglify = require('gulp-uglify');
const gulpUtil = require('gulp-util');
const path = require('path');
const source = require('vinyl-source-stream');
const buffer = require('vinyl-buffer');
const semver = require('semver');
const sprity = require('sprity');
const watchify = require('watchify');
const flatten = require('gulp-flatten');

const isDev = typeof process.env.npm_config_development !== 'undefined';

process.env.NODE_ENV = isDev ? 'development' : 'production';

const PATHS = {
  MODULES: './node_modules',
  ADMIN: './admin',
  ADMIN_IMAGES: './admin/client/dist/images',
  ADMIN_CSS_SRC: './admin/client/src/styles',
  ADMIN_CSS_DIST: './admin/client/dist/styles',
  ADMIN_THIRDPARTY: './admin/thirdparty',
  ADMIN_JS_SRC: './admin/client/src',
  ADMIN_JS_DIST: './admin/client/dist/js',
  ADMIN_SPRITES_SRC: './admin/client/src/sprites',
  ADMIN_SPRITES_DIST: './admin/client/dist/images/sprites',
  FRAMEWORK: '.',
  FRAMEWORK_CSS_SRC: './client/src/styles',
  FRAMEWORK_CSS_DIST: './client/dist/styles',
  FRAMEWORK_THIRDPARTY: './thirdparty',
  INSTALL_CSS_SRC: './dev/install/client/src/styles',
  INSTALL_CSS_DIST: './dev/install/client/dist/styles',
  FRAMEWORK_JS_SRC: './client/src',
  FRAMEWORK_JS_DIST: './client/dist/js',
};

// Map of *.scss locations to their compile target folders
const scssFolders = {
  [PATHS.FRAMEWORK_CSS_SRC]: PATHS.FRAMEWORK_CSS_DIST,
  [PATHS.ADMIN_CSS_SRC]: PATHS.ADMIN_CSS_DIST,
  [PATHS.INSTALL_CSS_SRC]: PATHS.INSTALL_CSS_DIST,
};

const browserifyOptions = {
  debug: true,
  paths: [PATHS.ADMIN_JS_SRC, PATHS.FRAMEWORK_JS_SRC],
};

const babelifyOptions = {
  presets: ['es2015', 'es2015-ie', 'react'],
  plugins: ['transform-object-assign', 'transform-object-rest-spread'],
  ignore: /(node_modules|thirdparty)/,
  comments: false,
};

const uglifyOptions = {
  mangle: false,
};

// Used for autoprefixing css properties (same as Bootstrap Aplha.2 defaults)
const supportedBrowsers = [
  'Chrome >= 35',
  'Firefox >= 31',
  'Edge >= 12',
  'Explorer >= 9',
  'iOS >= 8',
  'Safari >= 8',
  'Android 2.3',
  'Android >= 4',
  'Opera >= 12',
];

const blueimpFileUploadConfig = {
  src: `${PATHS.MODULES}/blueimp-file-upload`,
  dest: `${PATHS.FRAMEWORK_THIRDPARTY}/jquery-fileupload`,
  files: [
    '/cors/jquery.postmessage-transport.js',
    '/cors/jquery.xdr-transport.js',
    '/jquery.fileupload-ui.js',
    '/jquery.fileupload.js',
    '/jquery.iframe-transport.js',
  ],
};

const blueimpLoadImageConfig = {
  src: `${PATHS.MODULES}/blueimp-load-image`,
  dest: `${PATHS.FRAMEWORK_THIRDPARTY}/javascript-loadimage`,
  files: ['/load-image.js'],
};

const blueimpTmplConfig = {
  src: `${PATHS.MODULES}/blueimp-tmpl`,
  dest: `${PATHS.FRAMEWORK_THIRDPARTY}/javascript-templates`,
  files: ['/tmpl.js'],
};

const jquerySizesConfig = {
  src: `${PATHS.MODULES}/jquery-sizes`,
  dest: `${PATHS.ADMIN_THIRDPARTY}/jsizes`,
  files: ['/lib/jquery.sizes.js'],
};

const tinymceConfig = {
  src: `${PATHS.MODULES}/tinymce`,
  dest: `${PATHS.FRAMEWORK_THIRDPARTY}/tinymce`,
  files: [
    '/tinymce.min.js', // Exclude unminified file to keep repository size down
    '/jquery.tinymce.min.js',
    '/themes/**',
    '/skins/**',
    '/plugins/**',
  ],
};

/**
 * Copies files from a source directory to a destination directory.
 *
 * @param object libConfig
 * @param string libConfig.src - The source directory
 * @param string libConfig.dest - The destination directory
 * @param array libConfig.files - The list of files to copy from the source to
 *                                the destination directory
 */
function copyFiles(libConfig) {
  libConfig.files.forEach((file) => {
    const dir = path.parse(file).dir;

    gulp.src(libConfig.src + file)
      .pipe(gulp.dest(libConfig.dest + dir));
  });
}

/**
 * Diffs files in a source directory against a destination directory.
 *
 * @param object libConfig
 * @param string libConfig.src - The source directory
 * @param string libConfig.dest - The destination directory
 * @param array libConfig.files - The list of files to copy from the source
 *                                to the destination directory
 */
function diffFiles(libConfig) {
  libConfig.files.forEach((file) => {
    const dir = path.parse(file).dir;

    gulp.src(libConfig.src + file)
      .pipe(diff(libConfig.dest + dir))
      .pipe(diff.reporter({ fail: true, quiet: true }))
      .on('error', () => {
        console.error(new Error( // eslint-disable-line
          `Sanity check failed. ${libConfig.dest}${file} has been modified.`
        ));
        process.exit(1);
      });
  });
}

/**
 * Transforms the passed JavaScript files to UMD modules.
 *
 * @param array files - The files to transform.
 * @param string dest - The output directory.
 * @return object
 */
function transformToUmd(files, dest) {
  return eventStream.merge(files.map((file) => { // eslint-disable-line
    return gulp.src(file)
      .pipe(babel({
        presets: ['es2015'],
        moduleId: `ss.${path.parse(file).name}`,
        plugins: ['transform-es2015-modules-umd'],
        comments: false,
      }))
      .on('error', notify.onError({
        message: 'Error: <%= error.message %>',
      }))
      .pipe(gulp.dest(dest));
  }));
}

// Make sure the version of Node being used is valid.
if (!semver.satisfies(process.versions.node, packageJson.engines.node)) {
  console.error( // eslint-disable-line
    `Invalid Node.js version. You need to be using ${packageJson.engines.node}. ` +
    'If you want to manage multiple Node.js versions try https://github.com/creationix/nvm'
  );
  process.exit(1);
}

if (isDev) {
  browserifyOptions.cache = {};
  browserifyOptions.packageCache = {};
  browserifyOptions.plugin = [watchify];
}

gulp.task('build', ['umd', 'bundle']);

gulp.task('bundle', ['bundle-lib', 'bundle-legacy', 'bundle-framework']);

gulp.task('bundle-lib', function bundleLib() {
  const bundleFileName = 'bundle-lib.js';

  const es6 = browserify(Object.assign({}, browserifyOptions,
    { entries: `${PATHS.ADMIN_JS_SRC}/bundles/lib.js` }
  ))
    .on('update', bundleLib)
    .on('log', (msg) =>
      gulpUtil.log('Finished', `bundled ${bundleFileName} ${msg}`)
    )
    .transform('babelify', babelifyOptions)
    .require('deep-freeze-strict',
      { expose: 'deep-freeze-strict' }
    )
    .require('react',
      { expose: 'react' }
    )
    .require('tether',
      { expose: 'tether' }
    )
    .require('react-bootstrap-ss',
      { expose: 'react-bootstrap-ss' }
    )
    .require('react-addons-css-transition-group',
      { expose: 'react-addons-css-transition-group' }
    )
    .require('react-addons-test-utils',
      { expose: 'react-addons-test-utils' }
    )
    .require('react-dom',
      { expose: 'react-dom' }
    )
    .require('react-redux',
      { expose: 'react-redux' }
    )
    .require('redux',
      { expose: 'redux' }
    )
    .require('redux-thunk',
      { expose: 'redux-thunk' }
    )
    .require('react-router',
      { expose: 'react-router' }
    )
    .require('react-router-redux',
      { expose: 'react-router-redux' }
    )
    .require('page.js',
      { expose: 'page.js' }
    )
    .require(`${PATHS.MODULES}/bootstrap/dist/js/umd/collapse.js`,
      { expose: 'bootstrap-collapse' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/components/Form/Form`,
      { expose: 'components/Form/Form' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/components/Form/FormConstants`,
      { expose: 'components/Form/FormConstants' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/components/FormAction/FormAction`,
      { expose: 'components/FormAction/FormAction' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/components/FormBuilder/FormBuilder`,
      { expose: 'components/FormBuilder/FormBuilder' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/components/GridField/GridField`,
      { expose: 'components/GridField/GridField' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/components/GridField/GridFieldCell`,
      { expose: 'components/GridField/GridFieldCell' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/components/GridField/GridFieldHeader`,
      { expose: 'components/GridField/GridFieldHeader' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/components/GridField/GridFieldHeaderCell`,
      { expose: 'components/GridField/GridFieldHeaderCell' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/components/GridField/GridFieldRow`,
      { expose: 'components/GridField/GridFieldRow' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/components/GridField/GridFieldTable`,
      { expose: 'components/GridField/GridFieldTable' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/components/HiddenField/HiddenField`,
      { expose: 'components/HiddenField/HiddenField' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/components/TextField/TextField`,
      { expose: 'components/TextField/TextField' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/components/Toolbar/Toolbar`,
      { expose: 'components/Toolbar/Toolbar' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/components/Breadcrumb/Breadcrumb`,
      { expose: 'components/Breadcrumb/Breadcrumb' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/state/breadcrumbs/BreadcrumbsActions`,
      { expose: 'state/breadcrumbs/BreadcrumbsActions' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/components/PopoverField/PopoverField`,
      { expose: 'components/PopoverField/PopoverField' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/components/SingleSelectField/SingleSelectField`,
      { expose: 'components/SingleSelectField/SingleSelectField' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/components/FormBuilderModal/FormBuilderModal`,
      { expose: 'components/FormBuilderModal/FormBuilderModal' }
    )
    .require(`${PATHS.FRAMEWORK_JS_SRC}/i18n.js`,
      { expose: 'i18n' }
    )
    .require(`${PATHS.FRAMEWORK_JS_SRC}/i18nx.js`,
      { expose: 'i18nx' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/lib/Config`,
      { expose: 'lib/Config' }
    )
    .require(`${PATHS.FRAMEWORK_JS_SRC}/jQuery.js`,
      { expose: 'jQuery' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/lib/ReducerRegister.js`,
      { expose: 'lib/ReducerRegister' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/lib/ReactRouteRegister.js`,
      { expose: 'lib/ReactRouteRegister' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/lib/Injector.js`,
      { expose: 'lib/Injector' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/lib/Router.js`,
      { expose: 'lib/Router' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/lib/SilverStripeComponent`,
      { expose: 'lib/SilverStripeComponent' }
    )
    .require(`${PATHS.ADMIN_JS_SRC}/lib/Backend`,
      { expose: 'lib/Backend' }
    )
    .bundle()
    .on('error', notify.onError({ message: `${bundleFileName}: <%= error.message %>` }))
    .pipe(source(bundleFileName))
    .pipe(buffer());

  const chosen = gulp.src([
    `${PATHS.MODULES}/chosen/coffee/lib/*.coffee`,
    `${PATHS.MODULES}/chosen/coffee/chosen.jquery.coffee`,
  ])
    .pipe(concat('chosen.js'))
    .pipe(coffee());

  return merge(es6, chosen)
    .pipe(order([`**/${bundleFileName}`, '**/chosen.js']))
    .pipe(sourcemaps.init({ loadMaps: true }))
    .pipe(concat(bundleFileName, { newLine: '\r\n;\r\n' }))
    .pipe(uglify(uglifyOptions))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest(PATHS.ADMIN_JS_DIST));
});

gulp.task('bundle-legacy', function bundleLeftAndMain() {
  const bundleFileName = 'bundle-legacy.js';

  return browserify(Object.assign({}, browserifyOptions,
    { entries: `${PATHS.ADMIN_JS_SRC}/bundles/legacy.js` }
  ))
    .on('update', bundleLeftAndMain)
    .on('log', (msg) =>
      gulpUtil.log('Finished', `bundled ${bundleFileName} ${msg}`)
    )
    .transform('babelify', babelifyOptions)
    .external('config')
    .external('jQuery')
    .external('i18n')
    .external('i18nx')
    .external('lib/Router')
    .external('react')
    .external('react-dom')
    .external('react-bootstrap-ss')
    .external('components/FormBuilderModal/FormBuilderModal')
    .bundle()
    .on('update', bundleLeftAndMain)
    .on('error', notify.onError({ message: `${bundleFileName}: <%= error.message %>` }))
    .pipe(source(bundleFileName))
    .pipe(buffer())
    .pipe(sourcemaps.init({ loadMaps: true }))
    .pipe(uglify(uglifyOptions))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest(PATHS.ADMIN_JS_DIST));
});

gulp.task('bundle-framework', function bundleBoot() {
  const bundleFileName = 'bundle-framework.js';

  return browserify(Object.assign({}, browserifyOptions,
    { entries: `${PATHS.ADMIN_JS_SRC}/boot/index.js` }
  ))
    .on('update', bundleBoot)
    .on('log', (msg) => {
      gulpUtil.log('Finished', `bundled ${bundleFileName} ${msg}`);
    })
    .transform('babelify', babelifyOptions)
    .external('bootstrap-collapse')
    .external('components/Breadcrumb/Breadcrumb')
    .external('state/breadcrumbs/BreadcrumbsActions')
    .external('components/FormAction/FormAction')
    .external('components/FormBuilder/FormBuilder')
    .external('components/GridField/GridField')
    .external('components/Toolbar/Toolbar')
    .external('deep-freeze-strict')
    .external('i18n')
    .external('i18nx')
    .external('jQuery')
    .external('lib/Backend')
    .external('lib/ReducerRegister')
    .external('lib/ReactRouteRegister')
    .external('lib/SilverStripeComponent')
    .external('page.js')
    .external('react-addons-test-utils')
    .external('react-dom')
    .external('tether')
    .external('react-bootstrap-ss')
    .external('react-redux')
    .external('react-router-redux')
    .external('react-router')
    .external('react')
    .external('redux-thunk')
    .external('redux')
    .bundle()
    .on('update', bundleBoot)
    .on('error', notify.onError({ message: `${bundleFileName}: <%= error.message %>` }))
    .pipe(source(bundleFileName))
    .pipe(buffer())
    .pipe(sourcemaps.init({ loadMaps: true }))
    .pipe(uglify(uglifyOptions))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest(PATHS.ADMIN_JS_DIST));
});

gulp.task('sanity', () => {
  diffFiles(blueimpFileUploadConfig);
  diffFiles(blueimpLoadImageConfig);
  diffFiles(blueimpTmplConfig);
  diffFiles(jquerySizesConfig);
  diffFiles(tinymceConfig);
});

gulp.task('thirdparty', () => {
  copyFiles(blueimpFileUploadConfig);
  copyFiles(blueimpLoadImageConfig);
  copyFiles(blueimpTmplConfig);
  copyFiles(jquerySizesConfig);
  copyFiles(tinymceConfig);
});

gulp.task('umd', ['umd-admin', 'umd-framework'], () => {
  if (isDev) {
    gulp.watch(`${PATHS.ADMIN_JS_SRC}/legacy/*.js`, ['umd-admin']);
    gulp.watch(`${PATHS.FRAMEWORK_JS_SRC}/**/*.js`, ['umd-framework']);
  }
});

gulp.task('umd-admin', () => {
  const files = glob.sync(
    `${PATHS.ADMIN_JS_SRC}/legacy/*.js`,
    { ignore: `${PATHS.ADMIN_JS_SRC}/LeftAndMain.!(Ping).js` }
  );

  return transformToUmd(files, PATHS.ADMIN_JS_DIST);
});

gulp.task('umd-framework', () => { // eslint-disable-line
  return transformToUmd(glob.sync(
    `${PATHS.FRAMEWORK_JS_SRC}/**/*.js`),
    PATHS.FRAMEWORK_JS_DIST
  );
});

/*
 * Takes individual images and compiles them together into sprites
 */
gulp.task('sprites', () => { // eslint-disable-line
  return sprity.src({
    src: `${PATHS.ADMIN_SPRITES_SRC}/**/*.{png,jpg}`,
    cssPath: '../images/sprites',
    style: './_sprity.scss',
    processor: 'sass',
    split: true,
    margin: 0,
  })
  .pipe(
    gulpif(
      '*.png',
      gulp.dest(PATHS.ADMIN_SPRITES_DIST),
      gulp.dest(`${PATHS.ADMIN_CSS_SRC}/legacy`)
    )
  );
});

gulp.task('css', ['compile:css'], () => {
  if (isDev) {
    Object.keys(scssFolders).forEach((sourceFolder) => {
      gulp.watch(`${sourceFolder}/**/*.scss`, ['compile:css']);
    });
  }
});

/*
 * Compiles scss into css
 * Watches for changes if --development flag is given
 */
gulp.task('compile:css', () => {
  const tasks = Object.keys(scssFolders).map((sourceFolder) => { // eslint-disable-line
    const targetFolder = scssFolders[sourceFolder];
    return gulp.src(`${sourceFolder}/**/*.scss`)
      .pipe(sourcemaps.init())
      .pipe(
        sass({
          outputStyle: 'compressed',
          importer: (url, prev, done) => {
            if (url.match(/^compass\//)) {
              done({ file: 'client/src/styles/_compasscompat.scss' });
            } else {
              done();
            }
          },
        })
        .on('error', notify.onError({
          message: 'Error: <%= error.message %>',
        }))
      )
      .pipe(postcss([autoprefixer({ browsers: supportedBrowsers })]))
      .pipe(sourcemaps.write())
      .pipe(flatten()) // avoid legacy/ paths in CSS output
      .pipe(gulp.dest(targetFolder));
  });

  return tasks;
});
