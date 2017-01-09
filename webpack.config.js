const webpack = require('webpack');
const autoprefixer = require('autoprefixer');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const path = require('path');
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
  FRAMEWORK: '.',
  FRAMEWORK_THIRDPARTY: './thirdparty',
  FRAMEWORK_CSS_SRC: './client/src/styles',
  FRAMEWORK_CSS_DIST: './client/dist/styles',
  INSTALL_CSS_SRC: './src/Dev/Install/client/src/styles',
  INSTALL_CSS_DIST: './src/Dev/Install/client/dist/styles',
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
    // TODO Split out with new 'admin' module
    name: 'js',
    entry: {
      vendor: `${PATHS.ADMIN_JS_SRC}/bundles/vendor.js`,
      bundle: `${PATHS.ADMIN_JS_SRC}/bundles/bundle.js`,
      'LeftAndMain.Ping': `${PATHS.ADMIN_JS_SRC}/legacy/LeftAndMain.Ping.js`,
      leaktools: `${PATHS.ADMIN_JS_SRC}/legacy/leaktools.js`,
      MemberImportForm: `${PATHS.ADMIN_JS_SRC}/legacy/MemberImportForm.js`,
      CMSSecurity: `${PATHS.ADMIN_JS_SRC}/legacy/CMSSecurity.js`,
      UploadField_select: `${PATHS.ADMIN_JS_SRC}/legacy/UploadField_select.js`,
      TinyMCE_SSPlugin: `${PATHS.ADMIN_JS_SRC}/legacy/TinyMCE_SSPlugin.js`,
    },
    resolve: {
      root: [__dirname, path.resolve(__dirname, PATHS.ADMIN_JS_SRC)],
      modulesDirectories: [PATHS.MODULES],
    },
    output: {
      path: 'admin/client/dist',
      filename: 'js/[name].js',
    },

    // lib.js provies these globals and more. These references allow the framework bundle
    // to access them.
    externals: {
      'apollo-client': 'ApolloClient',
      'bootstrap-collapse': 'BootstrapCollapse',
      'components/Breadcrumb/Breadcrumb': 'Breadcrumb',
      'state/breadcrumbs/BreadcrumbsActions': 'BreadcrumbsActions',
      'state/schema/SchemaActions': 'SchemaActions',
      'components/FieldHolder/FieldHolder': 'FieldHolder',
      'components/FormAction/FormAction': 'FormAction',
      'components/FormBuilder/FormBuilder': 'FormBuilder',
      'components/FormBuilderModal/FormBuilderModal': 'FormBuilderModal',
      'components/GridField/GridField': 'GridField',
      'components/Toolbar/Toolbar': 'Toolbar',
      'containers/FormBuilderLoader/FormBuilderLoader': 'FormBuilderLoader',
      'deep-freeze-strict': 'DeepFreezeStrict',
      'graphql-fragments': 'GraphQLFragments',
      'graphql-tag': 'GraphQLTag',
      i18n: 'i18n',
      jQuery: 'jQuery',
      'lib/Backend': 'Backend',
      'lib/ReducerRegister': 'ReducerRegister',
      'lib/ReactRouteRegister': 'ReactRouteRegister',
      'lib/SilverStripeComponent': 'SilverStripeComponent',
      'page.js': 'Page',
      'react-addons-test-utils': 'ReactAddonsTestUtils',
      'react-dom': 'ReactDom',
      tether: 'Tether',
      'react-apollo': 'ReactApollo',
      'react-bootstrap-ss': 'ReactBootstrap',
      'react-redux': 'ReactRedux',
      'react-router-redux': 'ReactRouterRedux',
      'react-router': 'ReactRouter',
      'react-addons-css-transition-group': 'ReactAddonsCssTransitionGroup',
      react: 'React',
      'redux-form': 'ReduxForm',
      'redux-thunk': 'ReduxThunk',
      redux: 'Redux',
      config: 'Config',
      'lib/Router': 'Router',
      qs: 'qs',
    },
    module: {
      loaders: [
        {
          test: /\.js$/,
          exclude: /(node_modules|thirdparty)/,
          loader: 'babel',
          query: {
            presets: ['es2015', 'react'],
            plugins: ['transform-object-assign', 'transform-object-rest-spread'],
            comments: false,
          },
        },
        {
          test: '/i18n.js/',
          loader: 'script-loader',
        },
      ],
    },
    plugins: [
      new webpack.ProvidePlugin({
        jQuery: 'jQuery',
        $: 'jQuery',
      }),
      new webpack.DefinePlugin({
        'process.env':{
          // Builds React in production mode, avoiding console warnings
          'NODE_ENV': JSON.stringify('production')
        }
      }),
      new webpack.optimize.UglifyJsPlugin({
        compress: {
          unused: false,
          warnings: false,
        },
        output: {
          beautify: false,
          semicolons: false,
          comments: false,
          max_line_len: 200,
        },
      }),
      // Most vendor libs are loaded directly into the 'vendor' bundle (through require() calls in vendor.js).
      // This ensures that any further require() calls in other bundles aren't duplicating libs.
      new webpack.optimize.CommonsChunkPlugin({
        name: 'vendor',
        minChunks: Infinity,
      }),
      // Most vendor libs are loaded directly into the 'vendor' bundle (through require() calls in vendor.js).
      // This ensures that any further require() calls in other bundles aren't duplicating libs.
      new webpack.optimize.CommonsChunkPlugin({
        name: 'vendor',
        minChunks: Infinity,
      }),
    ],
  },
  {
    // TODO Split out with new 'admin' module
    name: 'css',
    entry: {
      'bundle': `${PATHS.ADMIN_CSS_SRC}/bundle.scss`,
      'editor': `${PATHS.ADMIN_CSS_SRC}/editor.scss`,
      'GridField_print': `${PATHS.ADMIN_CSS_SRC}/legacy/GridField_print.scss`,
      'AssetUploadField': `${PATHS.ADMIN_CSS_SRC}/legacy/AssetUploadField.scss`,
      'UploadField': `${PATHS.ADMIN_CSS_SRC}/legacy/UploadField.scss`,
    },
    output: {
      path: 'admin/client/dist/styles',
      filename: '[name].css',
    },
    module: {
      loaders: [
        {
          test: /\.scss$/,
          loader: ExtractTextPlugin.extract([
            'css?sourceMap&minimize&-core&discardComments',
            'postcss?sourceMap',
            'resolve-url',
            'sass?sourceMap',
          ]),
        },
        {
          test: /\.css$/,
          loader: ExtractTextPlugin.extract([
            'css?sourceMap&minimize&-core&discardComments',
            'postcss?sourceMap',
          ]),
        },
        {
          test: /\.(png|gif|jpg|svg)$/,
          loader: `url?limit=10000&name=../images/[name].[ext]`,
        },
        {
          test: /\.(woff|eot|ttf)$/,
          loader: `file?name=../fonts/[name].[ext]`,
        },
      ],
    },
    postcss: [
      autoprefixer({ browsers: SUPPORTED_BROWSERS }),
    ],
    plugins: [
      new ExtractTextPlugin('[name].css', { allChunks: true }),
    ],
  },
  {
    name: 'framework-css',
    entry: {
      [`${PATHS.INSTALL_CSS_DIST}/install`]: `${PATHS.INSTALL_CSS_SRC}/install.scss`,
      [`${PATHS.FRAMEWORK_CSS_DIST}/debug`]: `${PATHS.FRAMEWORK_CSS_SRC}/debug.scss`,
    },
    output: {
      path: './',
      filename: '[name].css',
    },
    module: {
      loaders: [
        {
          test: /\.scss$/,
          loader: ExtractTextPlugin.extract([
            'css?sourceMap&minimize&-core&discardComments',
            'postcss?sourceMap',
            'resolve-url',
            'sass?sourceMap',
          ]),
        },
        {
          test: /\.css$/,
          loader: ExtractTextPlugin.extract([
            'css?sourceMap&minimize&-core&discardComments',
            'postcss?sourceMap',
          ]),
        },
        {
          test: /\.(png|gif|jpg|svg)$/,
          loader: `url?limit=10000&name=../images/[name].[ext]`,
        },
        {
          test: /\.(woff|eot|ttf)$/,
          loader: `file?name=../fonts/[name].[ext]`,
        },
      ],
    },
    postcss: [
      autoprefixer({ browsers: SUPPORTED_BROWSERS }),
    ],
    plugins: [
      new ExtractTextPlugin('[name].css', { allChunks: true }),
    ],
  },
];

// Use WEBPACK_CHILD=js or WEBPACK_CHILD=css env var to run a single config
if (process.env.WEBPACK_CHILD) {
  module.exports = config.filter((entry) => entry.name === process.env.WEBPACK_CHILD)[0];
} else {
  module.exports = config;
}
