const webpack = require('webpack');
const autoprefixer = require('autoprefixer');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
// const SprityWebpackPlugin = require('sprity-webpack-plugin');

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

// Used for autoprefixing css properties (same as Bootstrap Aplha.2 defaults)
const SUPPORTED_BROWSERS = [
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

const config = [
  {
    name: 'js',
    entry: {
      'bundle-framework': `${PATHS.ADMIN_JS_SRC}/boot/index.js`,
      'bundle-legacy': `${PATHS.ADMIN_JS_SRC}/bundles/legacy.js`,
      'bundle-lib': `${PATHS.ADMIN_JS_SRC}/bundles/lib.js`,
      MemberImportForm: `${PATHS.ADMIN_JS_SRC}/legacy/MemberImportForm.js`,
      ModelAdmin: `${PATHS.ADMIN_JS_SRC}/legacy/ModelAdmin.js`,
      SecurityAdmin: `${PATHS.ADMIN_JS_SRC}/legacy/SecurityAdmin.js`,
      CMSSecurity: `${PATHS.ADMIN_JS_SRC}/legacy/CMSSecurity.js`,
    },
    resolve: {
      modulesDirectories: [PATHS.ADMIN_JS_SRC, PATHS.FRAMEWORK_JS_SRC, PATHS.MODULES],
    },
    output: {
      path: 'admin/client/dist',
      filename: 'js/[name].js',
    },

    // lib.js provies these globals and more. These references allow the framework bundle
    // to access them.
    externals: {
      'bootstrap-collapse': 'BootstrapCollapse',
      'components/Breadcrumb/Breadcrumb': 'Breadcrumb',
      'state/breadcrumbs/BreadcrumbsActions': 'BreadcrumbsActions',
      'components/FormAction/FormAction': 'FormAction',
      'components/FormBuilder/FormBuilder': 'FormBuilder',
      'components/GridField/GridField': 'GridField',
      'components/Toolbar/Toolbar': 'Toolbar',
      'deep-freeze-strict': 'DeepFreezeStrict',
      i18n: 'i18n',
      i18nx: 'i18nx',
      jQuery: 'jQuery',
      'lib/Backend': 'Backend',
      'lib/ReducerRegister': 'ReducerRegister',
      'lib/ReactRouteRegister': 'ReactRouteRegister',
      'lib/SilverStripeComponent': 'SilverStripeComponent',
      'page.js': 'Page',
      'react-addons-test-utils': 'ReactAddonsTestUtils',
      'react-dom': 'ReactDom',
      tether: 'Tether',
      'react-bootstrap-ss': 'ReactBootstrap',
      'react-redux': 'ReactRedux',
      'react-router-redux': 'ReactRouterRedux',
      'react-router': 'ReactRouter',
      react: 'React',
      'redux-thunk': 'ReduxThunk',
      redux: 'Redux',
      config: 'Config',
      'lib/Router': 'Router',
    },
    devtool: 'source-map',
    module: {
      loaders: [
        {
          test: /\.js$/,
          exclude: /(node_modules|thirdparty)/,
          loader: 'babel-loader',
          query: {
            presets: ['es2015', 'react'],
            plugins: ['transform-object-assign', 'transform-object-rest-spread'],
            comments: false,
          },
        },
        {
          test: /\.scss$/,
          // We disable url handling because the SCSS files directly reference the
          // compiled sprite files
          loader: ExtractTextPlugin.extract(
            'css?-url&minimize&sourceMap!postcss?sourceMap!sass?sourceMap'
          ),
          // loaders: ['style', 'css?-url&minimize', 'postcss', 'sass'],
        },
        {
          test: /\.css$/,
          // We disable url handling because the SCSS files directly reference the
          // compiled sprite files
          loader: ExtractTextPlugin.extract(
            'css?-url&minimize&sourceMap!postcss?sourceMap'
          ),
          // loaders: ['style', 'css?-url&minimize', 'postcss'],
        },
        {
          test: /\.coffee$/,
          loader: 'coffee-loader',
        },
        {
          test: '/i18n.js/',
          loader: 'script-loader',
        },
      ],
    },
    postcss: [
      autoprefixer({ browsers: SUPPORTED_BROWSERS }),
    ],
    plugins: [
      new webpack.ProvidePlugin({
        jQuery: 'jQuery',
        $: 'jQuery',
      }),
      /*
      new webpack.optimize.UglifyJsPlugin({
        compress: {
          unused: false,
          warnings: false,
        },
        mangle: false,
      }),
      */
      new ExtractTextPlugin('styles/bundle.css', { allChunks: true }),
    ],
  },

  // Much of the CSS is included in the javascript confiugration (bundle.scss)
  // These CSS files have not yet been inlined into the javascript include chain
  {
    name: 'css',
    entry: {
      'admin/client/dist/styles/editor':
        `${PATHS.ADMIN_CSS_SRC}/editor.scss`,
      'client/dist/styles/GridField_print':
        `${PATHS.FRAMEWORK_CSS_SRC}/legacy/GridField_print.scss`,
      'client/dist/styles/debug':
        `${PATHS.FRAMEWORK_CSS_SRC}/legacy/debug.scss`,
      'client/dist/styles/AssetUploadField':
        `${PATHS.FRAMEWORK_CSS_SRC}/legacy/AssetUploadField.scss`,
      'client/dist/styles/UploadField':
        `${PATHS.FRAMEWORK_CSS_SRC}/legacy/UploadField.scss`,
      [`${PATHS.INSTALL_CSS_DIST}/install`]:
        `${PATHS.INSTALL_CSS_SRC}/install.scss`,
    },
    output: {
      path: './',
      filename: '[name].css',
    },
    module: {
      loaders: [
        {
          test: /\.scss$/,
          // We disable url handling because the SCSS files directly reference the
          // compiled sprite files
          loader: ExtractTextPlugin.extract([
            'css-loader?-url&minimize',
            'postcss-loader',
            'sass-loader',
          ]),
        },
      ],
    },
    postcss: [
      autoprefixer({ browsers: SUPPORTED_BROWSERS }),
    ],
    plugins: [
      new ExtractTextPlugin('[name].css', {allChunks: true}),
      // new SprityWebpackPlugin({
      //   src: `${PATHS.ADMIN_SPRITES_SRC}/**/*.{png,jpg}`,
      //   out: PATHS.ADMIN_SPRITES_DIST,
      //   cssPath: '../images/sprites',
      //   style: './_sprity.scss',
      //   processor: 'sass',
      //   split: true,
      //   margin: 0,
      // }),
    ],
  },
];

// Use WEBPACK_CHILD=js or WEBPACK_CHILD=css env var to run a single config
if (process.env.WEBPACK_CHILD) {
  module.exports = config.filter((entry) => entry.name === process.env.WEBPACK_CHILD)[0];
} else {
  module.exports = config;
}
