'use strict';
module.exports = function ( grunt ) {

	// load all grunt tasks matching the `grunt-*` pattern
	// Ref. https://npmjs.org/package/load-grunt-tasks
	require( 'load-grunt-tasks' )( grunt );

	grunt.initConfig( {
		// watch for changes and trigger sass, jshint, uglify and livereload
		watch: {
			cssmin: {
				files: [ 'admin/css/*.{css}' ],
				tasks: [ 'cssmin' ]
			},
			autoprefixer: {
				files: [ 'admin/css/*.{css}' ],
				tasks: [ 'autoprefixer' ]
			},
			js: {
				files: [ '<%= uglify.backend.src %>' ],
				tasks: [ 'uglify' ]
			},
		},
		// cssmin
		cssmin: {
			options: {
				style: 'compressed',
				sourcemap: 'none'
			},
			target: {
				files: {
					'admin/css/rt-transcoder-admin.min.css': 'admin/css/rt-transcoder-admin.css',
				}
			}
		},
		// autoprefixer
		autoprefixer: {
			dist: {
				options: {
					browsers: [ 'last 2 versions', 'ie 9', 'ios 6', 'android 4' ],
					expand: true,
					flatten: true
				},
				files: {
					'admin/css/rt-transcoder-admin.css': 'admin/css/rt-transcoder-admin.css',
				}
			}
		},
		// Uglify Ref. https://npmjs.org/package/grunt-contrib-uglify
		uglify: {
			options: {
				banner: '/*! \n * Transcoder Library \n * @package Transcoder \n */\n',
			},
			backend: {
				src: [
					'admin/js/rt-transcoder-admin.js',
				],
				dest: 'admin/js/rt-transcoder-admin.min.js',
			}
		},
		checktextdomain: {
			options: {
				text_domain: 'transcoder', //Specify allowed domain(s)
				keywords: [ //List keyword specifications
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			target: {
				files: [ {
					src: [
							'*.php',
							'**/*.php',
							'!node_modules/**',
							'!tests/**'
						], //all php
					expand: true
				} ]
			}
		},
		makepot: {
			target: {
				options: {
					cwd: '.', // Directory of files to internationalize.
					domainPath: 'languages/', // Where to save the POT file.
					exclude: [ 'node_modules/*' ], // List of files or directories to ignore.
					mainFile: 'index.php', // Main project file.
					potFilename: 'transcoder.po', // Name of the POT file.
					potHeaders: { // Headers to add to the generated POT file.
						poedit: true, // Includes common Poedit headers.
						'Last-Translator': 'Transcoder <rt@rtcamp.com>',
						'Language-Team': 'Transcoder <rt@rtcamp.com>',
						'report-msgid-bugs-to': 'http://community.rtcamp.com/c/rt/',
						'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
					},
					type: 'wp-plugin', // Type of project (wp-plugin or wp-theme).
					updateTimestamp: true // Whether the POT-Creation-Date should be updated without other changes.
				}
			}
		}

	} );
	// register task
	grunt.registerTask( 'default', [ 'cssmin', 'autoprefixer', 'uglify', 'checktextdomain', 'makepot', 'watch' ] );
};
