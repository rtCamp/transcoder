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
	'@wordpress/components': 'wp.components', // Not really a package.
};

module.exports = {
	entry: {
		blocks: glob.sync( './admin/js/rt-transcoder-gutenberg-support.js' ),
	},
	output: {
		filename: './admin/js/build/rt-transcoder-gutenberg-support.build.js',
		path: __dirname,
	},
	externals,
	resolve: {
		modules: [
			__dirname,
			'node_modules',
		],
	},
	module: {
		rules: [
			{
				test: /.js?$/,
				loader: 'babel-loader',
				exclude: /node_modules/,
			},
		],
	},
	plugins: [
		new webpack.DefinePlugin( {
			'process.env.NODE_ENV': JSON.stringify( process.env.NODE_ENV || 'development' ),
		} ),
	],
};

if ( process.env.NODE_ENV === 'production' ) {
	module.exports.plugins = ( module.exports.plugins || [] ).concat( [
		new UglifyJsPlugin( {
			sourceMap: true,
			uglifyOptions: {
				ecma: 8,
				compress: {
					warnings: false,
				},
			},
		} ),
	] );
}
