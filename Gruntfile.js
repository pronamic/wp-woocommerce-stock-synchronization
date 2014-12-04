module.exports = function( grunt ) {
	// Project configuration.
	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		// PHPLint
		phplint: {
			options: {
				phpArgs: {
					'-lf': null
				}
			},
			all: [ '**/*.php' ]
		},

		// PHP Code Sniffer
		phpcs: {
			application: {
				dir: [ './' ],
			},
			options: {
				standard: 'phpcs.ruleset.xml',
				extensions: 'php',
				ignore: 'wp-svn,deploy,node_modules'
			}
		},

		// Check WordPress version
		checkwpversion: {
			options: {
				readme: 'readme.txt',
				plugin: 'stock-synchronization.php',
			},
			check: {
				version1: 'plugin',
				version2: 'readme',
				compare: '=='
			},
			check2: {
				version1: 'plugin',
				version2: '<%= pkg.version %>',
				compare: '=='
			}
		},

		// Make POT
		makepot: {
			target: {
				options: {
					cwd: '',
					domainPath: 'languages',
					type: 'wp-plugin',
					exclude: [ 'deploy/.*', 'wp-svn/.*' ],
				}
			}
		},

		// Clean
		clean: {
			deploy: {
				src: [ 'deploy' ]
			},
		},

		// Copy
		copy: {
			deploy: {
				src: [
					'**',
					'!Gruntfile.js',
					'!package.json',
					'!phpcs.ruleset.xml',
					'!README.md',
					'!archives/**',
					'!build/**',
					'!node_modules/**'
				],
				dest: 'deploy',
				expand: true
			},
		},

		// Compress
		compress: {
			deploy: {
				options: {
					archive: 'archives/<%= pkg.name %>.<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'deploy/',
				src: ['**/*'],
				dest: '<%= pkg.name %>/'
			}
		},

		// S3
		aws_s3: {
			options: {
				region: 'eu-central-1'
			},
			deploy: {
				options: {
					bucket: 'downloads.pronamic.eu',
					differential: true
				},
				files: [
      				{
      					expand: true,
      					cwd: 'archives/',
      					src: '<%= pkg.name %>.<%= pkg.version %>.zip',
      					dest: 'plugins/<%= pkg.name %>/'
      				}
    			]
  			}
 		}
	} );

	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-contrib-compress' );
	grunt.loadNpmTasks( 'grunt-phplint' );
	grunt.loadNpmTasks( 'grunt-phpcs' );
	grunt.loadNpmTasks( 'grunt-checkwpversion' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-aws-s3' );
	grunt.loadNpmTasks( 'grunt-s3' );

	// Default task(s).
	grunt.registerTask( 'default', [ 'phplint', 'phpcs', 'checkwpversion' ] );
	grunt.registerTask( 'pot', [ 'makepot' ] );

	grunt.registerTask( 'deploy', [
		'default',
		'clean:deploy',
		'copy:deploy',
		'compress:deploy',
		'aws_s3:deploy'
	] );
};
