const webpack = require('webpack');
const Config = require('./webpack.config');
const ExtractTextPlugin = require('extract-text-webpack-plugin');

if (Array.isArray(Config)) {
  const jsConfig = Config.find((item) => item.name === 'js');

  jsConfig.plugins = [
    new webpack.ProvidePlugin({
      jQuery: 'jQuery',
      $: 'jQuery',
    }),
    // Most vendor libs are loaded directly into the 'vendor' bundle (through require() calls in vendor.js).
    // This ensures that any further require() calls in other bundles aren't duplicating libs.
    new webpack.optimize.CommonsChunkPlugin({
      name: 'vendor',
      minChunks: Infinity,
    }),
  ];

  for (var i = 0; i < Config.length; i++) {
    Config[i].devtool = 'source-map';
  }

  const cssConfig = Config.find((item) => item.name === 'css');
  const frameworkCssConfig = Config.find((item) => item.name === 'framework-css');

  const createCssModule = () => ({
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
  });

  cssConfig.module = createCssModule();
  frameworkCssConfig.module = createCssModule();
}

module.exports = Config;
