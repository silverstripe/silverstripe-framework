## Client-side build tooling

Core JavaScript, CSS, and thirdparty dependencies are managed with the build tooling
described below.

Note this only applies to core SilverStripe dependencies, you're free to manage
dependencies in your project codebase however you like.

### Node.js

The [Node.js](https://nodejs.org) JavaScript runtime is the foundation of our client-side
build tool chain. If you want to do things like upgrade dependencies, make changes to core
JavaScript or SCSS files, you'll need Node installed on your dev environment. Our build
tooling supports the v4.2.x (LTS) version of Node. 
You'll likely want to manage multiple versions of Node, we suggest using
[Node Version Manager](https://github.com/creationix/nvm).
Since we're compiling SVG icons, you'll also need to compile native Node addons,
which requires `gcc` or a similar compiler - see [node-gyp](https://github.com/nodejs/node-gyp#installation)
for instructions on how to get a compiler running on your platform.

### npm

[npm](https://www.npmjs.com/) is the package manager we use for JavaScript dependencies.
It comes bundled with Node.js so should already have it installed if you have Node.

The configuration for an npm package goes in `package.json`. You'll see one in the root
directory of `framework`. As well as being used for defining dependencies and basic package
information, the `package.json` file has some other handy features.

#### npm scripts

The `script` property of a `package.json` file can be used to define command line scripts.
A nice thing about running commands from an npm script is binaries located in
`node_modules/.bin/` are temporally added to your `$PATH`. This means we can use dependencies
defined in `package.json` for things like compiling JavaScript and SCSS, and not require
developers to install these tools globally. This means builds are much more consistent
across development environments. 

For more info on npm scripts see
[https://docs.npmjs.com/misc/scripts](https://docs.npmjs.com/misc/scripts)

To run an npm script, open up your terminal, change to the directory where `package.json`
is located, and run `$ npm run <SCRIPT_NAME>`. Where `<SCRIPT_NAME>` is the name of the
script you wish to run.

Here are the scripts which are available in `framework`

Note you'll need to run an `npm install` to download the dependencies required by these scripts.

##### build

```
$ npm run build
```

Runs a Gulp task which builds the core JavaScript files. You will need to run this script
whenever you make changes to a JavaScript file.

Run this script with the `--development` flag to watch for changes in JavaScript files
and automatically trigger a rebuild.

##### lint

```
$ npm run lint
```

Run `eslint` over JavaScript files reports errors.

##### test

```
$ npm run test
```

Runs the JavaScript unit tests.

##### coverage

```
$ npm run coverage
```

Generates a coverage report for the JavaScript unit tests. The report is generated
in the `coverage` directory.

##### css

```
$ npm run css
```

Compile all of the .scss files into minified .css files. Run with the `--development` flag to
compile non-minified CSS and watch for every time a .scss file is changed.

##### sprites

```
$ npm run sprites
```

Generates sprites from the individual image files in `admin/images/sprites/src`.

##### thirdparty

```
$ npm run thirdparty
```

Copies legacy JavaScript dependencies from `node_modules` into the `thirdparty` directory.
This is only required legacy dependencies which are not written as CommonJS or ES6 modules.
All other modules will be included automatically with the `build` script.

##### sanity

```
$ npm run sanity
```

Makes sure files in `thirdparty` match files copied from `node_modules`. You should never commit
custom changes to a library file. This script will catch them if you do :smile:

##### lock

```
$ npm run lock
```

Generates a "shrinkwrap" file containing all npm package versions and writes it to
`npm-shrinkwrap.json`. Run this command whenever a new package is added to `package.json`,
or when updating packages. Commit the resulting `npm-shrinkwrap.json`. This uses a third party
[npm-shrinkwrap](https://github.com/uber/npm-shrinkwrap) library
since the built-in `npm shrinkwrap` (without a dash) has proven unreliable.

### Gulp

[Gulp](http://gulpjs.com/) is the build system which gets invoked by most npm scripts
in SilverStripe. The `gulpfile.js` script is where Gulp tasks are defined.

Here are the Gulp tasks which are defined in `gulpfile.js`

#### build

This is where JavaScript files are compiled and bundled. There are two parts to this which
are important to understand when working core JavaScript files.

##### Babel

[Babel](https://babeljs.io/) is a JavaScript compiler. It takes JavaScript files as input,
performs some transformations, and outputs other JavaScript files. In SilverStripe we use
Babel to transform our JavaScript in two ways.

###### Transforming ES6

ECMAScript 6 (ES6) is the newest version of the ECMAScript standard. It has some great new
features, but the browser support is still patchy, so we use Babel to transform ES6 source
files back to ES5 files for distribution.

To see some of the new features check out
[https://github.com/lukehoban/es6features](https://github.com/lukehoban/es6features)

###### Transforming to UMD

[Universal Module Definition](https://github.com/umdjs/umd) (UMD) is a pattern for writing
JavaScript modules. The advantage of UMD is modules can be 'required' by module loaders
(AMD and ES6 / CommonJS) and can also be loaded via `<script>` tags. Here's a simple example.

```js
(function (global, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD
        define(['jQuery'], factory);
    } else if (typeof exports === 'object') {
        // CommonJS
        module.exports = factory(require('jQuery'));
    } else {
        // Default browser with no bundling (global is window)
        global.MyModule = factory(global.jQuery);
    }
}(this, function (jQuery) {
    // Module code here
}));
```

The UMD wrapper is generated by Babel so you'll never have to write it manually,
it's handled for you by the build task.

##### Browserify

One of the great new features in ES6 is
[support for native modules](https://github.com/lukehoban/es6features#modules).
In order to support modules, SilverStripe uses
[Browserify](https://github.com/substack/node-browserify) to bundle modules for distribution.

Browserify takes an entry file, creates an abstract syntax tree (AST) by recursively
looking up all the `require` statements it finds, and outputs a bundled JavaScript file which
can be executed in a browser.

In addition to being a concatenated JavaScript file, Browserify bundles contain a lightweight
`require()` implementation, and an API wrapper which allows modules to require each other at
runtime. In most cases modules will bundled together in one JavaScript file, but it's also
possible to require modules bundled in another file, these are called external dependencies.

In this example the `BetterField` module requires `jQuery` from another bundle.

__gulpfile.js__

```js
gulp.task('bundle-a', function () {
	return browserify()
		.transform(babelify.configure({
			presets: ['es2015'] // Transform ES6 to ES5.
		}))
		.require('jQuery', { expose: 'jQuery' }) // Make jQuery available to other bundles at runtime.
		.bundle()
		.pipe(source('bundle-a.js'))
		.pipe(gulp.dest('./dist'));
});
```

This generates a bundle `bundle-a.js` which includes jQuery and exposed it to other bundles.

__better-field.js__

```js
import $ from 'jQuery';

$('.better-field').fadeIn();
```

__gulpfile.js__

```js

...

gulp.task('bundle-better-field', function () {
	return browserify('./src/better-field.js')
		.transform(babelify.configure({
			presets: ['es2015'] // Transform ES6 to ES5.
		}))
		.external('jQuery') // Get jQuery from another bundle at runtime.
		.bundle()
		.pipe(source('bundle-b.js'))
		.pipe(gulp.dest('./dist'));
});
```

When Browserify bundles `./src/better-field.js` (the entry file) it will ignore all
require statements that refer to `jQuery` and assume `jQuery` will be available via another
bundle at runtime.

The advantage of using externals is a reduced file size. The browser only needs to download
`jQuery` once (inside `bundle-a.js`) rather than it being included in multiple bundles.

Core dependencies are bundled and exposed in the `bundle-lib.js` file. Most of the libraries
a CMS developer requires are available a externals in that bundle.
