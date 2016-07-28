/* jshint node:true */
module.exports = function ( grunt ) {
	'use strict';

	grunt.initConfig( {

		// gets the package vars
		pkg: grunt.file.readJSON( 'package.json' ),
		svn_settings: {
			path: 'C:\xampp\htdocs\plugins\svn\tnt-brazil-shipping-for-woocommerce',
			tag: '<%= svn_settings.path %>/tags/<%= pkg.version %>',
			trunk: '<%= svn_settings.path %>/trunk',
			exclude: [
				'.git/',
				'.tx/',
				'.editorconfig',
				'.gitignore',
				'.jshintrc',
				'.gitattributes',
				'node_modules/',
				'Gruntfile.js',
				'README.md',
				'package.json',
				'*.zip'
			]
		},

		makepot: {
			dist: {
				options: {
					type: 'wp-plugin',
					potHeaders: {
						'report-msgid-bugs-to': 'https://wordpress.org/plugins/tnt-shipping-for-woocommerce/',
						'language-team': 'LANGUAGE <EMAIL@ADDRESS>'
					}
				}
			}
		},
		
		jshint: {
			options: {
				jshintrc: 'Gruntfile.js'
			}
		},

		checktextdomain: {
			options: {
				text_domain: '<%= pkg.text_domain %>',
				keywords: [
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
			files: {
				src: [
					'**/*.php',
					'!node_modules/**'
				],
				expand: true
			}
		},

		// Rsync commands used to take the files to svn repository
		rsync: {
			options: {
				args: ['--verbose'],
				exclude: '<%= svn_settings.exclude %>',
				syncDest: true,
				recursive: true
			},
			tag: {
				options: {
					src: './',
					dest: '<%= svn_settings.tag %>'
				}
			},
			trunk: {
				options: {
					src: './',
					dest: '<%= svn_settings.trunk %>'
				}
			}
		},

		// Shell command to commit the new version of the plugin
		shell: {
			// Remove delete files.
			svn_remove: {
				command: 'svn st | grep \'^!\' | awk \'{print $2}\' | xargs svn --force delete',
				options: {
					stdout: true,
					stderr: true,
					execOptions: {
						cwd: '<%= svn_settings.path %>'
					}
				}
			},
			// Add new files.
			svn_add: {
				command: 'svn add --force * --auto-props --parents --depth infinity -q',
				options: {
					stdout: true,
					stderr: true,
					execOptions: {
						cwd: '<%= svn_settings.path %>'
					}
				}
			},
			// Commit the changes.
			svn_commit: {
				command: 'svn commit -m "updated the plugin version to <%= pkg.version %>"',
				options: {
					stdout: true,
					stderr: true,
					execOptions: {
						cwd: '<%= svn_settings.path %>'
					}
				}
			}
		}

	} );

	// Load tasks
	grunt.loadNpmTasks( 'grunt-checktextdomain' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-rsync' );
	grunt.loadNpmTasks( 'grunt-shell' );

	// i18n
	grunt.registerTask( 'i18n', [
		'checktextdomain',
		'makepot'
	] );
	
	grunt.registerTask( 'jshint', ['jshint'] );

	// Deploy task
	grunt.registerTask( 'deploy', [
		'default',
		'rsync:tag',
		'rsync:trunk',
		'shell:svn_remove',
		'shell:svn_add',
		'shell:svn_commit'
	] );
};