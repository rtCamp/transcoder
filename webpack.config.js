/**
 * Webpack configuration file.
 *
 * @package transcoder
 */

/* global process */

const webpack = require( 'webpack' );
const glob = require( 'glob' );
const UglifyJsPlugin = require( 'uglifyjs-webpack-plugin' );

const externals = {
	react: 'React',
	'react-dom': 'ReactDOM',
	'react-dom/server': 'ReactDOMServer',
	tinymce: 'tinymce',
	moment: 'moment',
	jquery: 'jQuery',
	'@wordpress/components': 'wp.components' // Not really a package.
};

module.exports = [
	{
		entry: {
			blocks: glob.sync( './admin/js/rt-transcoder-block-editor-support.js' )
		},
		output: {
			filename: './admin/js/build/rt-transcoder-block-editor-support.build.js',
			path: __dirname
		},
		externals,
		resolve: {
			modules: [
				__dirname,
				'node_modules'
			]
		},
		module: {
			rules: [
				{
					test: /.js?$/,
					loader: 'babel-loader',
					exclude: /node_modules/
				}
			]
		},
		plugins: [
			new webpack.DefinePlugin( {
				'process.env.NODE_ENV': JSON.stringify( process.env.NODE_ENV || 'development' )
			} )
		]
	},
	{
		entry: './public-assets/js/transcoder.js',
		output: {
			filename: './public-assets/js/build/transcoder.min.js',
			path: __dirname
		}
	}
];

if ( process.env.NODE_ENV === 'production' ) {
	for ( var moduleConfig of module.exports ) {
		moduleConfig.plugins = (
			moduleConfig.plugins || []
		).concat(
			[
				new UglifyJsPlugin( {
					sourceMap: true,
					uglifyOptions: {
						ecma: 8,
						compress: {
							warnings: false
						}
					}
				} )
			]
		);
	}
}
