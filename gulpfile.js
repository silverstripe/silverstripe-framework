var gulp = require('gulp'),
    babel = require('gulp-babel'),
    diff = require('gulp-diff'),
    notify = require('gulp-notify'),
    postcss = require('gulp-postcss'),
    sass = require('gulp-sass'),
    uglify = require('gulp-uglify'),
    gulpUtil = require('gulp-util'),
    autoprefixer = require('autoprefixer'),
    browserify = require('browserify'),
    babelify = require('babelify'),
    watchify = require('watchify'),
    source = require('vinyl-source-stream'),
    buffer = require('vinyl-buffer'),
    path = require('path'),
    glob = require('glob'),
    eventStream = require('event-stream'),
    semver = require('semver'),
    packageJson = require('./package.json'),
    sprity = require('sprity'),
    gulpif = require('gulp-if'),
    sourcemaps = require('gulp-sourcemaps');

var isDev = typeof process.env.npm_config_development !== 'undefined';

var PATHS = {
    MODULES: './node_modules',
    ADMIN_IMAGES: './admin/images',
    ADMIN_SCSS: './admin/scss',
    ADMIN_CSS: './admin/css',
    ADMIN_THIRDPARTY: './admin/thirdparty',
    ADMIN_JAVASCRIPT_SRC: './admin/javascript/src',
    ADMIN_JAVASCRIPT_DIST: './admin/javascript/dist',
    FRAMEWORK_THIRDPARTY: './thirdparty',
    FRAMEWORK_CSS: './css',
    FRAMEWORK_SCSS: './scss',
    FRAMEWORK_DEV_INSTALL_SCSS: './dev/install/scss',
    FRAMEWORK_DEV_INSTALL_CSS: './dev/install/css',
    FRAMEWORK_JAVASCRIPT_SRC: './javascript/src',
    FRAMEWORK_JAVASCRIPT_DIST: './javascript/dist'
};

var browserifyOptions = {
    cache: {},
    packageCache: {},
    poll: true,
    plugin: [watchify]
};

// Used for autoprefixing Bootstrap css classes (same as Bootstrap Aplha.2 defaults)
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
                plugins: ['transform-es2015-modules-umd']
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
    browserifyOptions.debug = true;
}

gulp.task('build', ['umd', 'umd-watch', 'bundle']);

gulp.task('bundle', ['bundle-lib', 'bundle-leftandmain']);

gulp.task('bundle-leftandmain', function bundleLeftAndMain() {
    var stream = browserify(Object.assign({}, browserifyOptions, {
            entries: PATHS.ADMIN_JAVASCRIPT_SRC + '/bundles/leftandmain.js'
        }))
        .transform(babelify.configure({
            presets: ['es2015'],
            ignore: /(thirdparty)/
        }))
        .on('log', function (msg) { gulpUtil.log('Finished bundle-leftandmain.js ' + msg); })
        .on('update', bundleLeftAndMain)
        .external('jQuery')
        .external('i18n')
        .bundle()
        .on('error', notify.onError({
            message: 'Error: <%= error.message %>',
        }))
        .pipe(source('bundle-leftandmain.js'))
        .pipe(buffer());

    if (!isDev) {
        stream.pipe(uglify());
    }

    return stream.pipe(gulp.dest(PATHS.ADMIN_JAVASCRIPT_DIST));
});

gulp.task('bundle-lib', function bundleLib() {
    var stream = browserify(Object.assign({}, browserifyOptions, {
            entries: PATHS.ADMIN_JAVASCRIPT_SRC + '/bundles/lib.js'
        }))
        .transform(babelify.configure({
            presets: ['es2015'],
            ignore: /(thirdparty)/
        }))
        .on('log', function (msg) { gulpUtil.log('Finished bundle-lib.js ' + msg); })
        .on('update', bundleLib)
        .require(PATHS.FRAMEWORK_JAVASCRIPT_SRC + '/jQuery.js', { expose: 'jQuery' })
        .require(PATHS.FRAMEWORK_JAVASCRIPT_SRC + '/i18n.js', { expose: 'i18n' })
        .bundle()
        .on('error', notify.onError({
            message: 'Error: <%= error.message %>',
        }))
        .pipe(source('bundle-lib.js'))
        .pipe(buffer());

    if (!isDev) {
        stream.pipe(uglify());
    }

    return stream.pipe(gulp.dest(PATHS.ADMIN_JAVASCRIPT_DIST));
});

gulp.task('sanity', function () {
    diffFiles(blueimpFileUploadConfig);
    diffFiles(blueimpLoadImageConfig);
    diffFiles(blueimpTmplConfig);
    diffFiles(jquerySizesConfig);
});

gulp.task('bootstrap-css', function () {
    var outputStyle = isDev ? 'expanded' : 'compressed';

    return gulp.src(PATHS.ADMIN_SCSS + '/bootstrap/**/*.scss')
        .pipe(sass({ outputStyle: outputStyle })
            .on('error', notify.onError({
                message: 'Error: <%= error.message %>'
            }))
        )
        .pipe(postcss([autoprefixer({ browsers: supportedBrowsers })]))
        .pipe(gulp.dest(PATHS.ADMIN_THIRDPARTY + '/bootstrap'));
});

gulp.task('thirdparty', ['bootstrap-css'], function () {
    copyFiles(blueimpFileUploadConfig);
    copyFiles(blueimpLoadImageConfig);
    copyFiles(blueimpTmplConfig);
    copyFiles(jquerySizesConfig);
});

gulp.task('umd', ['umd-admin', 'umd-framework']);

gulp.task('umd-admin', function () {
    var files = glob.sync(PATHS.ADMIN_JAVASCRIPT_SRC + '/*.js', { ignore: PATHS.ADMIN_JAVASCRIPT_SRC + '/LeftAndMain.!(Ping).js' });

    return transformToUmd(files, PATHS.ADMIN_JAVASCRIPT_DIST);
});

gulp.task('umd-framework', function () {
    return transformToUmd(glob.sync(PATHS.FRAMEWORK_JAVASCRIPT_SRC + '/*.js'), PATHS.FRAMEWORK_JAVASCRIPT_DIST);
});

gulp.task('umd-watch', function () {
    gulp.watch(PATHS.ADMIN_JAVASCRIPT_SRC + '/*.js', ['umd-admin']);
    gulp.watch(PATHS.FRAMEWORK_JAVASCRIPT_SRC + '/*.js', ['umd-framework']);
});


/*
 * Sprite and scss/css compilation
 */
 
gulp.task('compile', ['sprites', 'compile-admin:css', 'compile:css', 'compile-dev-install:css'], function () {
    if (isDev) {
        gulp.watch(PATHS.FRAMEWORK_SCSS + '/**/*.scss', ['compile:css']);
        gulp.watch(PATHS.ADMIN_SCSS + '/**/*.scss', ['compile-admin:css']);
        gulp.watch(PATHS.FRAMEWORK_DEV_INSTALL_SCSS + '/**/*.scss', ['compile-dev-install:css']);
        gulp.watch(PATHS.ADMIN_IMAGES + '/**/*.{png,jpg}', ['sprites']);
    }
})

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

gulp.task('compile-admin:css', function () {
    var outputStyle = isDev ? 'expanded' : 'compressed';
    
    return gulp.src(PATHS.ADMIN_SCSS + '/**/*.scss')
        .pipe(sourcemaps.init())
        .pipe(sass({ outputStyle: outputStyle })
            .on('error', notify.onError({
                message: 'Error: <%= error.message %>'
            }))
        )
        .pipe(postcss([autoprefixer({ browsers: supportedBrowsers })]))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest(PATHS.ADMIN_CSS))
});

gulp.task('compile:css', function () {
    var outputStyle = isDev ? 'expanded' : 'compressed';
    
    return gulp.src(PATHS.FRAMEWORK_SCSS + '/**/*.scss')
        .pipe(sourcemaps.init())
        .pipe(sass({ outputStyle: outputStyle })
            .on('error', notify.onError({
                message: 'Error: <%= error.message %>'
            }))
        )
        .pipe(postcss([autoprefixer({ browsers: supportedBrowsers })]))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest(PATHS.FRAMEWORK_CSS))
});

gulp.task('compile-dev-install:css', function () {
    var outputStyle = isDev ? 'expanded' : 'compressed';
    
    return gulp.src(PATHS.FRAMEWORK_DEV_INSTALL_SCSS + '/**/*.scss')
        .pipe(sourcemaps.init())
        .pipe(sass({ outputStyle: outputStyle })
            .on('error', notify.onError({
                message: 'Error: <%= error.message %>'
            }))
        )
        .pipe(postcss([autoprefixer({ browsers: supportedBrowsers })]))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest(PATHS.FRAMEWORK_DEV_INSTALL_CSS))
});


