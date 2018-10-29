module.exports = function( grunt ) {
	require( 'load-grunt-tasks' )( grunt );

	// Project configuration.
	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		// PHPLint
		phplint: {
			all: [
				'**/*.php',
				'!bower_components/**',
				'!deploy/**',
				'!node_modules/**',
				'!vendor/**',
				'!wordpress/**'
			]
		},

		// PHP Code Sniffer
		phpcs: {
			application: {
				src: [
					'**/*.php',
					'!bower_components/**',
					'!deploy/**',
					'!node_modules/**',
					'!vendor/**',
					'!wordpress/**'
				]
			},
			options: {
				bin: 'vendor/bin/phpcs',
				standard: 'phpcs.xml.dist',
				showSniffCodes: true
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
					exclude: [
						'deploy/.*',
						'node_modules/.*',
						'vendor/.*',
						'wordpress/.*'
					],
				}
			}
		},

		// Clean
		clean: {
			deploy: {
				src: [ 'deploy/latest' ]
			},
		},

		// Copy
		copy: {
			deploy: {
				src: [
					'**',
					'!Gruntfile.js',
					'!composer.json',
					'!composer.lock',
					'!package.json',
					'!package-lock.json',
					'!phpcs.xml.dist',
					'!README.md',
					'!yarn.lock',
					'!deploy/**',
					'!node_modules/**',
					'!vendor/**',
					'!wordpress/**'
				],
				dest: 'deploy/latest',
				expand: true
			},
		},

		// Compress
		compress: {
			deploy: {
				options: {
					archive: 'deploy/archives/<%= pkg.name %>.<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'deploy/latest',
				src: ['**/*'],
				dest: '<%= pkg.name %>/'
			}
		},

		// Git checkout
		gitcheckout: {
			tag: {
				options: {
					branch: 'tags/<%= pkg.version %>'
				}
			},
			develop: {
				options: {
					branch: 'develop'
				}
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
						cwd: 'deploy/archives/',
						src: '<%= pkg.name %>.<%= pkg.version %>.zip',
						dest: 'plugins/<%= pkg.name %>/'
					}
				]
			}
		}
	} );

	// Default task(s).
	grunt.registerTask( 'default', [ 'phplint', 'phpcs', 'checkwpversion' ] );
	grunt.registerTask( 'pot', [ 'makepot' ] );

	grunt.registerTask( 'deploy', [
		'default',
		'clean:deploy',
		'copy:deploy',
		'compress:deploy',
	] );

	grunt.registerTask( 'release', [
		'gitcheckout:tag',
		'clean:deploy',
		'copy:deploy',
		'compress:deploy',
		'aws_s3:deploy',
		'gitcheckout:develop'
	] );
};
