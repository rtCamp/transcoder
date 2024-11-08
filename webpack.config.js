/**
 * External dependencies
 */
const path = require('path');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');

/**
 * WordPress dependencies
 */
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

// Extend the default config
const sharedConfig = {
  ...defaultConfig,
  plugins: [
    ...defaultConfig.plugins.map((plugin) => {
      if (plugin.constructor.name === 'MiniCssExtractPlugin') {
        plugin.options.filename = '../css/[name].css';
      }
      return plugin;
    }),
    new RemoveEmptyScriptsPlugin(),
  ],
  optimization: {
    ...defaultConfig.optimization,
    splitChunks: {
      ...defaultConfig.optimization.splitChunks,
    },
  },
};

// Configuration for the block editor support script
const blockEditorSupport = {
  ...sharedConfig,
  entry: {
    blocks: path.resolve(
      process.cwd(),
      'admin',
      'js',
      'rt-transcoder-block-editor-support.js'
    ),
  },
  output: {
    filename: 'rt-transcoder-block-editor-support.build.js',
    path: path.resolve(__dirname, 'admin', 'js', 'build'),
  },
  externals: {
    react: 'React',
    'react-dom': 'ReactDOM',
    'react-dom/server': 'ReactDOMServer',
    tinymce: 'tinymce',
    moment: 'moment',
    jquery: 'jQuery',
    '@wordpress/components': 'wp.components',
  },
};

// Configuration for the public-facing transcoder script
const transcoderJS = {
  ...sharedConfig,
  entry: {
    'transcoder.min': path.resolve(
      process.cwd(),
      'public-assets',
      'js',
      'transcoder.js'
    ),
  },
  output: {
    filename: '[name].js',
    path: path.resolve(__dirname, 'public-assets', 'js', 'build'),
  },
};

// Export the configurations
module.exports = [blockEditorSupport, transcoderJS];
