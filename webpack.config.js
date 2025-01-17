/**
 * External dependencies
 */
const path = require('path');
const glob = require('glob');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');

/**
 * WordPress dependencies
 */
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

// Extend the default config.
const sharedConfig = {
  ...defaultConfig,
  devtool: false,
  plugins: [
    ...defaultConfig.plugins.map((plugin) => {
      if (plugin.constructor.name === 'MiniCssExtractPlugin') {
        return new MiniCssExtractPlugin({
          filename: '[name].min.css',
        });
      }
      return plugin;
    }),
    new RemoveEmptyScriptsPlugin(),
  ],
  optimization: {
    ...defaultConfig.optimization,
    minimizer: [
      '...',
      new CssMinimizerPlugin(),
    ],
    splitChunks: {
      ...defaultConfig.optimization.splitChunks,
    },
  },
};

// Configuration for the block editor support script.
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

// Configuration for the public-facing transcoder script.
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

// Configuration for processing all admin JS and CSS files.
const jsFiles = glob.sync('./admin/js/**/!(*.min).js', {
  ignore: [
    './admin/js/build/**/*.js',
  ],
});

const cssFiles = glob.sync('./admin/css/**/!(*.min).css');

const files = [...jsFiles, ...cssFiles];

// Create an entries object mapping names to file paths.
const entries = {};
const skipFiles = [
  'jquery-ui-1.7.2.custom.css',
  'rt-transcoder-block-editor-support.js',
];

files.forEach((file) => {
  if (skipFiles.some((skipFile) => file.includes(skipFile))) {
    return;
  }
  const extname = path.extname(file);
  const relativePath = path
    .relative(path.resolve(__dirname, 'admin'), file)
    .replace(extname, '')
    .replace(/\\/g, '/');
  entries[relativePath] = path.resolve(__dirname, file);
});

// Configuration for processing all admin JS and CSS files.
const adminAssetsConfig = {
  ...sharedConfig,
  entry: entries,
  output: {
    filename: '[name].min.js',
    path: path.resolve(__dirname, 'admin'),
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: '[name].min.css',
    }),
    new RemoveEmptyScriptsPlugin(),
  ],
};

// Export the configurations.
module.exports = [blockEditorSupport, transcoderJS, adminAssetsConfig];
