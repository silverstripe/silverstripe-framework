const webpack = require('webpack');
const Config = require('./webpack.config');

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
}

module.exports = Config;
