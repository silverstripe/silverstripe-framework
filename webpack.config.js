const autoprefixer = require('autoprefixer');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
// const SprityWebpackPlugin = require('sprity-webpack-plugin');

const PATHS = {
  MODULES: './node_modules',
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
          loader: 'url-loader?limit=10000&name=../images/[name].[ext]',
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
