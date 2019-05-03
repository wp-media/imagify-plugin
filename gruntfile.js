/* globals module: true, require: false */

module.exports = function( grunt ) {
	grunt.initConfig( {
		// JS linter.
		'eslint': {
			'all': [ 'gruntfile.js', 'assets/js/*.js', '!**/*.min.js', '!assets/js/chart.js', '!assets/js/es6-promise.auto.js', '!assets/js/imagify-gulp.js', '!assets/js/jquery.event.move.js', '!assets/js/sweetalert2.js' ]
		},
		// JS minify.
		'uglify': {
			'all': {
				'files': [ {
					'expand': true,
					'cwd':    'assets/js',
					'src':    [ '*.js', '!*.min.js', '!chart.js', '!es6-promise.auto.js', '!imagify-gulp.js', '!jquery.event.move.js', '!jquery.twentytwenty.js', '!sweetalert2.js' ],
					'dest':   'assets/js',
					'ext':    '.min.js'
				} ]
			},
			'bugfix': {
				'files': {
					'assets/js/jquery.event.move.min.js':   [ 'assets/js/jquery.event.move.js' ],
					'assets/js/jquery.twentytwenty.min.js': [ 'assets/js/jquery.twentytwenty.js' ]
				}
			}
		},
		// PostCSS: Autoprefixer.
		'postcss': {
			'options': {
				'processors': [
					require( 'autoprefixer' )( {
						'browsers': 'last 3 versions'
					} )
				]
			},
			'target': {
				'files': [ {
					'expand': true,
					'cwd':    'assets/css',
					'src':    [ '*.css', '!*.min.css' ],
					'dest':   'assets/css',
					'ext':    '.min.css'
				} ]
			}
		},
		// CSS minify.
		'cssmin': {
			'options': {
				'shorthandCompacting': false,
				'roundingPrecision':   -1
			},
			'target': {
				'files': [ {
					'expand': true,
					'cwd':    'assets/css',
					'src':    [ '*.min.css' ],
					'dest':   'assets/css',
					'ext':    '.min.css'
				} ]
			}
		}
	} );

	/**
	 * Allow local configuration. For example:
	 * {
	 *   "copy": {
	 *     "whatever": {
	 *       "files": [ { "cwd": "/absolute/path/to/a/local/directory" } ]
	 *     }
	 *   }
	 * }
	 */
	if ( grunt.file.exists( 'gruntlocalconf.json' ) ) {
		grunt.config.merge( grunt.file.readJSON( 'gruntlocalconf.json' ) );
	}

	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-postcss' );

	// Our custom tasks.
	grunt.registerTask( 'css',    [ 'postcss', 'cssmin' ] );
	grunt.registerTask( 'js',     [ 'eslint', 'uglify' ] );
	grunt.registerTask( 'jsh',    [ 'eslint' ] );
	grunt.registerTask( 'esl',    [ 'eslint' ] );
	grunt.registerTask( 'minify', [ 'eslint', 'uglify', 'postcss', 'cssmin' ] );
};
