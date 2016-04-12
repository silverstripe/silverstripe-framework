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

const isDev = typeof process.env.npm_config_development !== 'undefined';

process.env.NODE_ENV = isDev ? 'development' : 'production';

const PATHS = {
  MODULES: './node_modules',
  ADMIN: './admin',
  ADMIN_IMAGES: './admin/images',
  ADMIN_SCSS: './admin/scss',
  ADMIN_THIRDPARTY: './admin/thirdparty',
  ADMIN_JAVASCRIPT_SRC: './admin/javascript/src',
  ADMIN_JAVASCRIPT_DIST: './admin/javascript/dist',
  FRAMEWORK: '.',
  FRAMEWORK_THIRDPARTY: './thirdparty',
  FRAMEWORK_DEV_INSTALL: './dev/install',
  FRAMEWORK_JAVASCRIPT_SRC: './javascript/src',
  FRAMEWORK_JAVASCRIPT_DIST: './javascript/dist',
};

// Folders which contain both scss and css folders to be compiled
const rootCompileFolders = [PATHS.FRAMEWORK, PATHS.ADMIN, PATHS.FRAMEWORK_DEV_INSTALL];

const browserifyOptions = {
  debug: true,
  paths: [PATHS.ADMIN_JAVASCRIPT_SRC, PATHS.FRAMEWORK_JAVASCRIPT_SRC],
};

const babelifyOptions = {
  presets: ['es2015', 'es2015-ie', 'react'],
  plugins: ['transform-object-assign'],
  ignore: /(node_modules|thirdparty)/,
  comments: false,
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
    { entries: `${PATHS.ADMIN_JAVASCRIPT_SRC}/bundles/lib.js` }
  ))
    .on('update', bundleLib)
    .on('log', (msg) =>
      gulpUtil.log('Finished', `bundled ${bundleFileName} ${msg}`)
    )
    .transform('babelify', babelifyOptions)
    .require('deep-freeze',
      { expose: 'deep-freeze' }
    )
    .require('react',
      { expose: 'react' }
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
    .require(`${PATHS.MODULES}/bootstrap/dist/js/umd/collapse.js`,
      { expose: 'bootstrap-collapse' }
    )
    .require(`${PATHS.ADMIN_JAVASCRIPT_SRC}/components/form/index`,
      { expose: 'components/form/index' }
    )
    .require(`${PATHS.ADMIN_JAVASCRIPT_SRC}/components/form-action/index`,
      { expose: 'components/form-action/index' }
    )
    .require(`${PATHS.ADMIN_JAVASCRIPT_SRC}/components/form-builder/index`,
      { expose: 'components/form-builder/index' }
    )
    .require(`${PATHS.ADMIN_JAVASCRIPT_SRC}/components/grid-field/index`,
      { expose: 'components/grid-field/index' }
    )
    .require(`${PATHS.ADMIN_JAVASCRIPT_SRC}/components/grid-field/cell`,
      { expose: 'components/grid-field/cell/index' }
    )
    .require(`${PATHS.ADMIN_JAVASCRIPT_SRC}/components/grid-field/header`,
      { expose: 'components/grid-field/header' }
    )
    .require(`${PATHS.ADMIN_JAVASCRIPT_SRC}/components/grid-field/header-cell`,
      { expose: 'components/grid-field/header-cell' }
    )
    .require(`${PATHS.ADMIN_JAVASCRIPT_SRC}/components/grid-field/row`,
      { expose: 'components/grid-field/row' }
    )
    .require(`${PATHS.ADMIN_JAVASCRIPT_SRC}/components/grid-field/table`,
      { expose: 'components/grid-field/table' }
    )
    .require(`${PATHS.ADMIN_JAVASCRIPT_SRC}/components/hidden-field/index`,
      { expose: 'components/hidden-field/index' }
    )
    .require(`${PATHS.ADMIN_JAVASCRIPT_SRC}/components/text-field/index`,
      { expose: 'components/text-field/index' }
    )
    .require(`${PATHS.ADMIN_JAVASCRIPT_SRC}/components/north-header/index`,
      { expose: 'components/north-header/index' }
    )
    .require(`${PATHS.ADMIN_JAVASCRIPT_SRC}/components/north-header-breadcrumbs/index`,
      { expose: 'components/north-header-breadcrumbs/index' }
    )
    .require(`${PATHS.FRAMEWORK_JAVASCRIPT_SRC}/i18n.js`,
      { expose: 'i18n' }
    )
    .require(`${PATHS.ADMIN_JAVASCRIPT_SRC}/config.js`,
      { expose: 'config' }
    )
    .require(`${PATHS.FRAMEWORK_JAVASCRIPT_SRC}/jQuery.js`,
      { expose: 'jQuery' }
    )
    .require(`${PATHS.ADMIN_JAVASCRIPT_SRC}/reducer-register.js`,
      { expose: 'reducer-register' }
    )
    .require(`${PATHS.FRAMEWORK_JAVASCRIPT_SRC}/router.js`,
      { expose: 'router' }
    )
    .require(`${PATHS.ADMIN_JAVASCRIPT_SRC}/silverstripe-component`,
      { expose: 'silverstripe-component' }
    )
    .require(`${PATHS.ADMIN_JAVASCRIPT_SRC}/silverstripe-backend`,
      { expose: 'silverstripe-backend' }
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
    .pipe(uglify())
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest(PATHS.ADMIN_JAVASCRIPT_DIST));
});

gulp.task('bundle-legacy', function bundleLeftAndMain() {
  const bundleFileName = 'bundle-legacy.js';

  return browserify(Object.assign({}, browserifyOptions,
    { entries: `${PATHS.ADMIN_JAVASCRIPT_SRC}/bundles/legacy.js` }
  ))
    .on('update', bundleLeftAndMain)
    .on('log', (msg) =>
      gulpUtil.log('Finished', `bundled ${bundleFileName} ${msg}`)
    )
    .transform('babelify', babelifyOptions)
    .external('config')
    .external('jQuery')
    .external('i18n')
    .external('router')
    .bundle()
    .on('update', bundleLeftAndMain)
    .on('error', notify.onError({ message: `${bundleFileName}: <%= error.message %>` }))
    .pipe(source(bundleFileName))
    .pipe(buffer())
    .pipe(sourcemaps.init({ loadMaps: true }))
    .pipe(uglify())
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest(PATHS.ADMIN_JAVASCRIPT_DIST));
});

gulp.task('bundle-framework', function bundleBoot() {
  const bundleFileName = 'bundle-framework.js';

  return browserify(Object.assign({}, browserifyOptions,
    { entries: `${PATHS.ADMIN_JAVASCRIPT_SRC}/boot/index.js` }
  ))
    .on('update', bundleBoot)
    .on('log', (msg) => {
      gulpUtil.log('Finished', `bundled ${bundleFileName} ${msg}`);
    })
    .transform('babelify', babelifyOptions)
    .external('components/action-button/index')
    .external('components/north-header/index')
    .external('components/form-builder/index')
    .external('components/form-action/index')
    .external('deep-freeze')
    .external('components/grid-field/index')
    .external('i18n')
    .external('jQuery')
    .external('page.js')
    .external('react-addons-test-utils')
    .external('react-dom')
    .external('react-redux')
    .external('react')
    .external('reducer-register')
    .external('redux-thunk')
    .external('redux')
    .external('silverstripe-component')
    .external('bootstrap-collapse')
    .bundle()
    .on('update', bundleBoot)
    .on('error', notify.onError({ message: `${bundleFileName}: <%= error.message %>` }))
    .pipe(source(bundleFileName))
    .pipe(buffer())
    .pipe(sourcemaps.init({ loadMaps: true }))
    .pipe(uglify())
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest(PATHS.ADMIN_JAVASCRIPT_DIST));
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
    gulp.watch(`${PATHS.ADMIN_JAVASCRIPT_SRC}/*.js`, ['umd-admin']);
    gulp.watch(`${PATHS.FRAMEWORK_JAVASCRIPT_SRC}/*.js`, ['umd-framework']);
  }
});

gulp.task('umd-admin', () => {
  const files = glob.sync(
    `${PATHS.ADMIN_JAVASCRIPT_SRC}/*.js`,
    { ignore: `${PATHS.ADMIN_JAVASCRIPT_SRC}/LeftAndMain.!(Ping).js` }
  );

  return transformToUmd(files, PATHS.ADMIN_JAVASCRIPT_DIST);
});

gulp.task('umd-framework', () => { // eslint-disable-line
  return transformToUmd(glob.sync(
    `${PATHS.FRAMEWORK_JAVASCRIPT_SRC}/*.js`),
    PATHS.FRAMEWORK_JAVASCRIPT_DIST
  );
});

/*
 * Takes individual images and compiles them together into sprites
 */
gulp.task('sprites', () => { // eslint-disable-line
  return sprity.src({
    src: `${PATHS.ADMIN_IMAGES}/sprites/src/**/*.{png,jpg}`,
    cssPath: '../images/sprites/dist',
    style: './_spritey.scss',
    processor: 'sass',
    split: true,
    margin: 0,
  })
  .pipe(
    gulpif(
      '*.png',
      gulp.dest(`${PATHS.ADMIN_IMAGES}/sprites/dist`),
      gulp.dest(PATHS.ADMIN_SCSS)
    )
  );
});

gulp.task('css', ['compile:css'], () => {
  if (isDev) {
    rootCompileFolders.forEach((folder) => {
      gulp.watch(`${folder}/scss/**/*.scss`, ['compile:css']);
    });

    // Watch the .scss files in react components
    gulp.watch('./admin/javascript/src/**/*.scss', ['compile:css']);
  }
});

/*
 * Compiles scss into css
 * Watches for changes if --development flag is given
 */
gulp.task('compile:css', () => {
  const tasks = rootCompileFolders.map((folder) => { // eslint-disable-line
    return gulp.src(`${folder}/scss/**/*.scss`)
      .pipe(sourcemaps.init())
      .pipe(
        sass({
          outputStyle: 'compressed',
          importer: (url, prev, done) => {
            if (url.match(/^compass\//)) {
              done({ file: 'scss/_compasscompat.scss' });
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
      .pipe(gulp.dest(`${folder}/css`));
  });

  return tasks;
});
