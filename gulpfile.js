var packageJson =  require('./package.json'),
    autoprefixer = require('autoprefixer'),
    babelify =     require('babelify'),
    browserify =   require('browserify'),
    eventStream =  require('event-stream'),
    glob =         require('glob'),
    gulp =         require('gulp'),
    babel =        require('gulp-babel'),
    diff =         require('gulp-diff'),
    gulpif =       require('gulp-if'),
    notify =       require('gulp-notify'),
    postcss =      require('gulp-postcss'),
    sass =         require('gulp-sass'),
    sourcemaps =   require('gulp-sourcemaps'),
    uglify =       require('gulp-uglify'),
    gulpUtil =     require('gulp-util'),
    path =         require('path'),
    source =       require('vinyl-source-stream'),
    buffer =       require('vinyl-buffer'),
    semver =       require('semver'),
    sprity =       require('sprity'),
    watchify =     require('watchify');

var isDev = typeof process.env.npm_config_development !== 'undefined';

process.env.NODE_ENV = isDev ? 'development' : 'production';

var PATHS = {
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
    FRAMEWORK_JAVASCRIPT_DIST: './javascript/dist'
};

// Folders which contain both scss and css folders to be compiled
var rootCompileFolders = [PATHS.FRAMEWORK, PATHS.ADMIN, PATHS.FRAMEWORK_DEV_INSTALL]

var browserifyOptions = { debug: true };

var babelifyOptions = {
    presets: ['es2015', 'react'],
    ignore: /(node_modules|thirdparty)/,
    comments: false
};

// Used for autoprefixing css properties (same as Bootstrap Aplha.2 defaults)
var supportedBrowsers = [
    'Chrome >= 35',
    'Firefox >= 31',
    'Edge >= 12',
    'Explorer >= 9',
    'iOS >= 8',
    'Safari >= 8',
    'Android 2.3',
    'Android >= 4',
    'Opera >= 12'
];

var blueimpFileUploadConfig = {
    src: PATHS.MODULES + '/blueimp-file-upload',
    dest: PATHS.FRAMEWORK_THIRDPARTY + '/jquery-fileupload',
    files: [
        '/cors/jquery.postmessage-transport.js',
        '/cors/jquery.xdr-transport.js',
        '/jquery.fileupload-ui.js',
        '/jquery.fileupload.js',
        '/jquery.iframe-transport.js'
    ]
};

var blueimpLoadImageConfig = {
    src: PATHS.MODULES + '/blueimp-load-image',
    dest: PATHS.FRAMEWORK_THIRDPARTY + '/javascript-loadimage',
    files: ['/load-image.js']
};

var blueimpTmplConfig = {
    src: PATHS.MODULES + '/blueimp-tmpl',
    dest: PATHS.FRAMEWORK_THIRDPARTY + '/javascript-templates',
    files: ['/tmpl.js']
};

var jquerySizesConfig = {
    src: PATHS.MODULES + '/jquery-sizes',
    dest: PATHS.ADMIN_THIRDPARTY + '/jsizes',
    files: ['/lib/jquery.sizes.js']
};

var tinymceConfig = {
    src: PATHS.MODULES + '/tinymce',
    dest: PATHS.FRAMEWORK_THIRDPARTY + '/tinymce',
    files: [
        '/tinymce.min.js', // Exclude unminified file to keep repository size down
        '/jquery.tinymce.min.js',
        '/themes/**',
        '/skins/**',
        '/plugins/**'
    ]
};

/**
 * Copies files from a source directory to a destination directory.
 *
 * @param object libConfig
 * @param string libConfig.src - The source directory
 * @param string libConfig.dest - The destination directory
 * @param array libConfig.files - The list of files to copy from the source to the destination directory
 */
function copyFiles(libConfig) {
    libConfig.files.forEach(function (file) {
        var dir = path.parse(file).dir;

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
 * @param array libConfig.files - The list of files to copy from the source to the destination directory
 */
function diffFiles(libConfig) {
    libConfig.files.forEach(function (file) {
        var dir = path.parse(file).dir;

        gulp.src(libConfig.src + file)
            .pipe(diff(libConfig.dest + dir))
            .pipe(diff.reporter({ fail: true, quiet: true }))
            .on('error', function (error) {
                console.error(new Error('Sanity check failed. \'' + libConfig.dest + file + '\' has been modified.'));
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
    return eventStream.merge(files.map(function (file) {
        return gulp.src(file)
            .pipe(babel({
                presets: ['es2015'],
                moduleId: 'ss.' + path.parse(file).name,
                plugins: ['transform-es2015-modules-umd'],
                comments: false
            }))
            .on('error', notify.onError({
                message: 'Error: <%= error.message %>',
            }))
            .pipe(gulp.dest(dest));
    }));
}

// Make sure the version of Node being used is valid.
if (!semver.satisfies(process.versions.node, packageJson.engines.node)) {
    console.error('Invalid Node.js version. You need to be using ' + packageJson.engines.node + '. If you want to manage multiple Node.js versions try https://github.com/creationix/nvm');
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
    var bundleFileName = 'bundle-lib.js';

    return browserify(Object.assign({}, browserifyOptions, { entries: PATHS.ADMIN_JAVASCRIPT_SRC + '/bundles/lib.js' }))
        .on('update', bundleLib)
        .on('log', function (msg) { gulpUtil.log('Finished', 'bundled ' + bundleFileName + ' ' + msg) })
        .transform('babelify', babelifyOptions)
        .require(PATHS.ADMIN_JAVASCRIPT_SRC + '/components/action',                   { expose: 'action-button' })
        .require(PATHS.ADMIN_JAVASCRIPT_SRC + '/config',                              { expose: 'config' })
        .require('deep-freeze',                                                       { expose: 'deep-freeze' })
        .require(PATHS.ADMIN_JAVASCRIPT_SRC + '/components/form',                     { expose: 'form' })
        .require(PATHS.ADMIN_JAVASCRIPT_SRC + '/components/form-action',              { expose: 'form-action' })
        .require(PATHS.ADMIN_JAVASCRIPT_SRC + '/components/form-builder',             { expose: 'form-builder' })
        .require(PATHS.ADMIN_JAVASCRIPT_SRC + '/components/grid-field',               { expose: 'grid-field' })
        .require(PATHS.ADMIN_JAVASCRIPT_SRC + '/components/grid-field/cell',          { expose: 'grid-field-cell' })
        .require(PATHS.ADMIN_JAVASCRIPT_SRC + '/components/grid-field/header',        { expose: 'grid-field-header' })
        .require(PATHS.ADMIN_JAVASCRIPT_SRC + '/components/grid-field/header-cell',   { expose: 'grid-field-header-cell' })
        .require(PATHS.ADMIN_JAVASCRIPT_SRC + '/components/grid-field/row',           { expose: 'grid-field-row' })
        .require(PATHS.ADMIN_JAVASCRIPT_SRC + '/components/grid-field/table',         { expose: 'grid-field-table' })
        .require(PATHS.ADMIN_JAVASCRIPT_SRC + '/components/hidden-field',             { expose: 'hidden-field' })
        .require(PATHS.FRAMEWORK_JAVASCRIPT_SRC + '/i18n.js',                         { expose: 'i18n' })
        .require(PATHS.FRAMEWORK_JAVASCRIPT_SRC + '/jQuery.js',                       { expose: 'jQuery' })
        .require(PATHS.ADMIN_JAVASCRIPT_SRC + '/components/north-header',             { expose: 'north-header' })
        .require(PATHS.ADMIN_JAVASCRIPT_SRC + '/components/north-header-breadcrumbs', { expose: 'north-header-breadcrumbs' })
        .require('react',                                                             { expose: 'react' })
        .require('react-addons-css-transition-group',                                 { expose: 'react-addons-css-transition-group' })
        .require('react-addons-test-utils',                                           { expose: 'react-addons-test-utils' })
        .require('react-dom',                                                         { expose: 'react-dom' })
        .require('react-redux',                                                       { expose: 'react-redux' })
        .require(PATHS.ADMIN_JAVASCRIPT_SRC + '/reducer-register.js',                 { expose: 'reducer-register' })
        .require('redux',                                                             { expose: 'redux' })
        .require('redux-thunk',                                                       { expose: 'redux-thunk' })
        .require(PATHS.FRAMEWORK_JAVASCRIPT_SRC + '/router.js',                       { expose: 'router' })
        .require(PATHS.ADMIN_JAVASCRIPT_SRC + '/SilverStripeComponent',               { expose: 'silverstripe-component' })
        .require(PATHS.ADMIN_JAVASCRIPT_SRC + '/components/text-field',               { expose: 'text-field' })
        .bundle()
        .on('error', notify.onError({ message: bundleFileName + ': <%= error.message %>' }))
        .pipe(source(bundleFileName))
        .pipe(buffer())
        .pipe(sourcemaps.init({ loadMaps: true }))
        .pipe(gulpif(!isDev, uglify()))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest(PATHS.ADMIN_JAVASCRIPT_DIST));
});


gulp.task('bundle-legacy', function bundleLeftAndMain() {
    var bundleFileName = 'bundle-legacy.js';

    return browserify(Object.assign({}, browserifyOptions, { entries: PATHS.ADMIN_JAVASCRIPT_SRC + '/bundles/legacy.js' }))
        .on('update', bundleLeftAndMain)
        .on('log', function (msg) { gulpUtil.log('Finished', 'bundled ' + bundleFileName + ' ' + msg) })
        .transform('babelify', babelifyOptions)
        .external('jQuery')
        .external('i18n')
        .external('router')
        .bundle()
        .on('error', notify.onError({ message: bundleFileName + ': <%= error.message %>' }))
        .pipe(source(bundleFileName))
        .pipe(buffer())
        .pipe(sourcemaps.init({ loadMaps: true }))
        .pipe(gulpif(!isDev, uglify()))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest(PATHS.ADMIN_JAVASCRIPT_DIST));
});

gulp.task('bundle-framework', function bundleBoot() {
    var bundleFileName = 'bundle-framework.js';

    return browserify(Object.assign({}, browserifyOptions, { entries: PATHS.ADMIN_JAVASCRIPT_SRC + '/boot/index.js' }))
        .on('update', bundleBoot)
        .on('log', function (msg) { gulpUtil.log('Finished', 'bundled ' + bundleFileName + ' ' + msg) })
        .transform('babelify', babelifyOptions)
        .external('action-button')
        .external('config')
        .external('deep-freeze')
        .external('grid-field')
        .external('i18n')
        .external('jQuery')
        .external('jQuery')
        .external('north-header')
        .external('page.js')
        .external('react-addons-test-utils')
        .external('react-dom')
        .external('react-redux')
        .external('react')
        .external('reducer-register')
        .external('redux-thunk')
        .external('redux')
        .external('silverstripe-component')
        .bundle()
        .on('error', notify.onError({ message: bundleFileName + ': <%= error.message %>' }))
        .pipe(source(bundleFileName))
        .pipe(buffer())
        .pipe(sourcemaps.init({ loadMaps: true }))
        .pipe(gulpif(!isDev, uglify()))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest(PATHS.ADMIN_JAVASCRIPT_DIST));
});

gulp.task('sanity', function () {
    diffFiles(blueimpFileUploadConfig);
    diffFiles(blueimpLoadImageConfig);
    diffFiles(blueimpTmplConfig);
    diffFiles(jquerySizesConfig);
    diffFiles(tinymceConfig);
});

gulp.task('thirdparty', function () {
    copyFiles(blueimpFileUploadConfig);
    copyFiles(blueimpLoadImageConfig);
    copyFiles(blueimpTmplConfig);
    copyFiles(jquerySizesConfig);
    copyFiles(tinymceConfig);
});

gulp.task('umd', ['umd-admin', 'umd-framework'], function () {
    if (isDev) {
        gulp.watch(PATHS.ADMIN_JAVASCRIPT_SRC + '/*.js', ['umd-admin']);
        gulp.watch(PATHS.FRAMEWORK_JAVASCRIPT_SRC + '/*.js', ['umd-framework']);
    }
});

gulp.task('umd-admin', function () {
    var files = glob.sync(PATHS.ADMIN_JAVASCRIPT_SRC + '/*.js', { ignore: PATHS.ADMIN_JAVASCRIPT_SRC + '/LeftAndMain.!(Ping).js' });

    return transformToUmd(files, PATHS.ADMIN_JAVASCRIPT_DIST);
});

gulp.task('umd-framework', function () {
    return transformToUmd(glob.sync(PATHS.FRAMEWORK_JAVASCRIPT_SRC + '/*.js'), PATHS.FRAMEWORK_JAVASCRIPT_DIST);
});

/*
 * Takes individual images and compiles them together into sprites
 */
gulp.task('sprites', function () {
    return sprity.src({
        src: PATHS.ADMIN_IMAGES + '/sprites/src/**/*.{png,jpg}',
        cssPath: '../images/sprites/dist',
        style: './_spritey.scss',
        processor: 'sass',
        split: true,
        margin: 0
    })
    .pipe(gulpif('*.png', gulp.dest(PATHS.ADMIN_IMAGES + '/sprites/dist'), gulp.dest(PATHS.ADMIN_SCSS)))
});

gulp.task('css', ['compile:css'], function () {
    if (isDev) {
        rootCompileFolders.forEach(function (folder) {
            gulp.watch(folder + '/scss/**/*.scss', ['compile:css']);
        });

        // Watch the .scss files in react components
        gulp.watch('./admin/javascript/src/**/*.scss', ['compile:css']);
    }
})

/*
 * Compiles scss into css
 * Watches for changes if --development flag is given
 */
gulp.task('compile:css', function () {
    var outputStyle = isDev ? 'expanded' : 'compressed';

    var tasks = rootCompileFolders.map(function(folder) {
        return gulp.src(folder + '/scss/**/*.scss')
            .pipe(sourcemaps.init())
            .pipe(sass({ outputStyle: outputStyle })
                .on('error', notify.onError({
                    message: 'Error: <%= error.message %>'
                }))
            )
            .pipe(postcss([autoprefixer({ browsers: supportedBrowsers })]))
            .pipe(sourcemaps.write())
            .pipe(gulp.dest(folder + '/css'))
    });

    return tasks;
});
