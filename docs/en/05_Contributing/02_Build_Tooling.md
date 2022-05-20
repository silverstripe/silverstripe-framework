---
title: Build tooling
summary: The tools we use to compile our frontend code
icon: tools
---

# Client-side build tooling

Core JavaScript, CSS, and thirdparty dependencies are managed with the build tooling
described below.

Note this only applies to core SilverStripe dependencies, you're free to manage
dependencies in your project codebase however you like.

## Installation

The [NodeJS](https://nodejs.org) JavaScript runtime is the foundation of our client-side
build tool chain. If you want to do things like upgrade dependencies, make changes to core
JavaScript or SCSS files, you'll need Node installed on your dev environment.

As of SilverStripe 4.4, our build tooling supports the v10.x (LTS as of Sept 2019) version
of NodeJS. SilverStripe 4.0 - 4.3 used Node v6.x.
 
If you already have a newer version of Node.js installed, check out the
[Node Version Manager](https://github.com/creationix/nvm) to run multiple versions
in your environment.

Since we're compiling SVG icons, you'll also need to compile native Node addons,
which requires `gcc` or a similar compiler - see [node-gyp](https://github.com/nodejs/node-gyp#installation)
for instructions on how to get a compiler running on your platform.

[yarn](https://yarnpkg.com/) is the package manager we use for JavaScript dependencies.
The configuration for an npm package goes in `package.json`.
You'll need to install yarn after Node.js is installed.
See [yarn installation docs](https://yarnpkg.com/en/docs/install).
We recommend using `npm` which comes with Node.js to install it globally.

```
npm install -g yarn
```

Once you've installed Node.js and yarn, run the following command once in each core module folders:

```
yarn
```

## The Basics: ES6, Webpack and Babel

[ECMAScript 6](https://github.com/lukehoban/es6features) (ES6)
is the newest version of the ECMAScript standard. It has some great new
features, but the browser support is still patchy, so we use Babel to transform ES6 source
files back to ES5 files for distribution. 

[Webpack](https://webpack.github.io) contains the build tooling to
"transpile" various syntax patterns (ES6, SCSS) into a format the browser can understand,
and resolve ES6's `import` ([details](https://github.com/lukehoban/es6features#modules)).
Webpack provides the entry point to our build tooling through a `webpack.config.js`
file in the root folder of each core module.

[Babel](https://babeljs.io/) is a JavaScript compiler. It takes JavaScript files as input,
performs some transformations, and outputs other JavaScript files. In SilverStripe we use
Babel to transform our JavaScript in two ways.

## Build Commands

The `script` property of a `package.json` file can be used to define command line 
[scripts](https://docs.npmjs.com/misc/scripts).
A nice thing about running commands from an npm script is binaries located in
`node_modules/.bin/` are temporally added to your `$PATH`. This means we can use dependencies
defined in `package.json` for things like compiling JavaScript and SCSS, and not require
developers to install these tools globally. This means builds are much more consistent
across development environments. 

To run an npm script, open up your terminal, change to the directory where `package.json`
is located, and run `$ yarn run <SCRIPT_NAME>`. Where `<SCRIPT_NAME>` is the name of the
script you wish to run.

### build

```
$ yarn run build
```

Runs [Webpack](https://webpack.github.io/) to builds the core JavaScript files. 
You will need to run this script whenever you make changes to a JavaScript file.

Run this script with `-- --watch` to automatically rebuild on file changes.
The first `--` separator is required to separate arguments from NPM's own ones.

```
$ yarn run build -- --watch
```

*For development only*:
Run this to keep webpack automatically rebuilding your file changes, this will also include *.map files
for easier debugging. It is important to note that this should not be used for pushing up changes,
and you should run `yarn run build` after you're done.

```
$ yarn run watch
```

### css

```
$ yarn run css
```

Compiles all of the `.scss` files into minified `.css` files.

Run this script with `-- --watch` to automatically rebuild on file changes.
The first `--` separator is required to separate arguments from NPM's own ones.

```
$ yarn run css -- --watch
```

### lint

```
$ yarn run lint
```

Run linters (`eslint` and `sass-lint`) linters to enforce
our [JavaScript](/contributing/javascript_coding_conventions) and 
[CSS](/contributing/css_coding_conventions) coding conventions.

### test

```
$ yarn run test
```

Runs the JavaScript unit tests.

### coverage

```
$ yarn run coverage
```

Generates a coverage report for the JavaScript unit tests. The report is generated
in the `coverage` directory.

## Requiring SilverStripe ES6 Modules in your own CMS customisation

SilverStripe creates bundles which contain many dependencies you might also
want to use in your own CMS customisation (e.g. `react`).
You might also need some of SilverStripe's own ES6 modules (e.g. `components/FormBuilder`).

To avoid double including these in your own generated bundles,
we have exposed many libraries as [Webpack externals](https://webpack.github.io/docs/library-and-externals.html).
This helps to keep the file size of your own bundle small, and avoids
execution issues with multiple versions of the same library.

In order to find out which libraries are exposed, check
the `framework/admin/client/src/bundles/` files for `require('expose?...')` statements.

A shortened `webpack.config.js` in your own module could look as follows:

```
module.exports = {
  entry: {
    'bundle': `mymodule/client/src/js/bundle.js`,
  },
  output: {
    path: './client/dist',
    filename: 'js/[name].js',
  },
  externals: {
    'components/FormBuilder/FormBuilder': 'FormBuilder',
    jQuery: 'jQuery',
    react: 'react',
  }
};
```

Now you can use the following statements in your ES6 code without double includes:

```
import react from 'react';
import jQuery from 'jQuery';
import FormBuilder from 'components/FormBuilder/FormBuilder';
```

## Publishing frontend packages to NPM

We're progressing to include NPM modules in our development process. We currently have a limited number of 
[JavaScript only projects published to NPM under the `@silverstripe` organisation](https://www.npmjs.com/search?q=%40silverstripe).

When a pull request is merged against one of those JS-only projects, a new release has to be published to NPM. Regular
Silverstripe CMS modules using these packages have to upgrade their JS dependencies to get the new release.

These are the steps involved to publish a new version to NPM for a package, similar steps apply for creating a new
package under the `@silverstripe` organisation:
 
1) Make your changes, pull from upstream if applicable
2) Change to the relevant container folder with the `package.json` file
3) Run `npm login` and make sure you’re part of the `@silverstripe` organisation
4) Make sure the `name` property of the `package.json` file matches to the right module name with organisation name prefix, e.g. `"name": "@silverstripe/webpack-config"`
5) Update the `version` property of the `package.json` file with a new version number, following semantic versioning where possible
6) Run `npm version` and validate that the version matches what you expect
7) Run `npm publish`
 
_IMPORTANT NOTE_: You cannot publish the same or lower version number. Only members of the Silverstripe CMS core team
can publish a release to NPM.
