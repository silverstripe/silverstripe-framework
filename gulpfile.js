var gulp = require('gulp'),
    diff = require('gulp-diff'),
    path = require('path'),
    semver = require('semver'),
    packageJson = require('./package.json');

var paths = {
    modules: './node_modules/',
    frameworkThirdparty: './thirdparty/',
    adminThirdparty: './admin/thirdparty/'
};

var blueimpFileUploadConfig = {
    src: paths.modules + 'blueimp-file-upload/',
    dest: paths.frameworkThirdparty + 'jquery-fileupload/',
    files: [
        'cors/jquery.postmessage-transport.js',
        'cors/jquery.xdr-transport.js',
        'jquery.fileupload-ui.js',
        'jquery.fileupload.js',
        'jquery.iframe-transport.js'
    ]
};

var blueimpLoadImageConfig = {
    src: paths.modules + 'blueimp-load-image/',
    dest: paths.frameworkThirdparty + 'javascript-loadimage/',
    files: ['load-image.js']
};

var blueimpTmplConfig = {
    src: paths.modules + 'blueimp-tmpl/',
    dest: paths.frameworkThirdparty + 'javascript-templates/',
    files: ['tmpl.js']
};

var jquerySizesConfig = {
    src: paths.modules + 'jquery-sizes/',
    dest: paths.adminThirdparty + 'jsizes/',
    files: ['lib/jquery.sizes.js']
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

// Make sure the version of Node being used is valid.
if (!semver.satisfies(process.versions.node, packageJson.engines.node)) {
    console.error('Invalid Node.js version. You need to be using ' + packageJson.engines.node + '. If you want to manage multiple Node.js versions try https://github.com/creationix/nvm');
    process.exit(1);
}

gulp.task('build', function () {
    copyFiles(blueimpFileUploadConfig);
    copyFiles(blueimpLoadImageConfig);
    copyFiles(blueimpTmplConfig);
    copyFiles(jquerySizesConfig);
});

gulp.task('sanity', function () {
    diffFiles(blueimpFileUploadConfig);
    diffFiles(blueimpLoadImageConfig);
    diffFiles(blueimpTmplConfig);
    diffFiles(jquerySizesConfig);
});
