'use strict';

// Which SASS implementation to use.
const sass = require('sass');

module.exports = function(grunt) {

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		wpversion: grunt.file.read('avatar-privacy.php').toString().match(/Version:\s*([0-9](?:\w|\.|\-)*)\s|\Z/)[1],

		clean: {
			build: ["build/*"],
			autoloader: [
				"build/tests",
				"build/composer.*",
				"build/vendor-scoped/composer/*.json",
				"build/vendor-scoped/composer/autoload_psr4.php",
				"build/vendor-scoped/composer/InstalledVersions.php",
				"build/vendor-scoped/composer/installed.php",
				"build/vendor-scoped/scoper-autoload.php",
				"build/vendor-scoped/dangoodman"
			],
			vendor: [
				"build/vendor-scoped/bin",
				"build/vendor-scoped/{jdenticon,mistic100,splitbrain}/*/*",
				"!build/vendor-scoped/**/src",
				"!build/vendor-scoped/**/partials",
				// Prune PNG-based RingIcon.
				"build/vendor-scoped/splitbrain/php-ringicon/src/RingIcon.php",
				// Prune PNG-based Jdenticon.
				"build/vendor-scoped/jdenticon/jdenticon/src/Rendering/{ImagickRenderer.php,InternalPngRenderer.php}",
				"build/vendor-scoped/jdenticon/jdenticon/src/Canvas",
			],
		},

		exec: {
			composer_build: {
				command: "composer --quiet <%= grunt.task.current.args[0] %>",
				cwd: 'build'
			},
			composer_dev: {
				command: "composer <%= grunt.task.current.args[0] %>",
			}
		},

		"string-replace": {
			autoloader: {
				files: {
					"build/": "build/vendor-scoped/composer/autoload_{classmap,psr4,static}.php",
				},
				options: {
					replacements: [
						{
							pattern: /\s+'Dangoodman\\\\ComposerForWordpress\\\\' =>\s+array\s*\([^,]+,\s*\),/,
							replacement: ''
						}, {
							pattern: /\s+'Dangoodman\\\\ComposerForWordpress\\\\.*,(?=\n)/g,
							replacement: ''
						}, {
							pattern: /\s+'Composer\\\\InstalledVersions.*,(?=\n)/g,
							replacement: ''
						}
					]
				}
			},
			namespaces: {
				options: {
					replacements: [{
						pattern: '', // Set later.
						replacement: '$1' + 'Avatar_Privacy\\Vendor\\' + '$2'
					}],
				},
				files: [{
					expand: true,
					flatten: false,
					src: ['build/includes/**/*.php'],
					dest: '',
				}]
			},
			"composer-vendor-dir": {
				options: {
                    replacements: [
                        {
                            pattern: /"vendor-dir":\s*"vendor"/g,
                            replacement: '"vendor-dir": "vendor-scoped"'
                        },
                        {
                            pattern: /"dealerdirect\\\/phpcodesniffer-composer-installer":\s*true/g,
                            replacement: '"dealerdirect\/phpcodesniffer-composer-installer": false'
                        },
                        {
                            pattern: /"phpstan\\\/extension-installer":\s*true/g,
                            replacement: '"phpstan\/extension-installer": false'
                        },
                    ],
				},
				files: [{
					expand: true,
					flatten: false,
					src: ['build/composer.json'],
					dest: '',
				}]
			},
			"vendor-dir": {
				options: {
					replacements: [{
						pattern: /vendor\//g,
						replacement: 'vendor-scoped/'
					}],
				},
				files: [{
					expand: true,
					flatten: false,
					src: ['build/**/*.php'],
					dest: '',
				}]
			}
		},

		copy: {
			main: {
				files: [{
					expand: true,
					nonull: true,
					src: [
						'readme.txt',
						'*.php',
						'admin/**',
						'!admin/blocks/js/**',
						'public/**',
						'includes/**',
						'!**/scss/**',
						'!**/tests/**',
						'vendor/**/partials/**',
					],
					dest: 'build/',
					rename: function(dest, src) {
						return dest + src.replace( /\bvendor\b/, 'vendor-scoped');
					}
				}],
			},
			meta: {
				files: [{
					expand: true,
					nonull: false,
					src: [
						'vendor/{composer,mundschenk-at,level-2,mistic100,jdenticon,splitbrain}/**/LICENSE*',
						'vendor/{composer,mundschenk-at,level-2,mistic100,jdenticon,splitbrain}/**/README*',
						'vendor/{composer,mundschenk-at,level-2,mistic100,jdenticon,splitbrain}/**/CREDITS*',
						'vendor/{composer,mundschenk-at,level-2,mistic100,jdenticon,splitbrain}/**/COPYING*',
						'vendor/{composer,mundschenk-at,level-2,mistic100,jdenticon,splitbrain}/**/CHANGE*',
						'!vendor/mundschenk-at/phpunit-cross-version/**',
						'!vendor/composer/package-versions-deprecated/**',
					],
					dest: 'build/',
					rename: function(dest, src) {
						return dest + src.replace( /\bvendor\b/, 'vendor-scoped');
					}
				}],
			}
		},

		rename: {
			vendor: {
				files: [{
					src: "build/vendor",
					dest: "build/vendor-scoped"
				}]
			}
		},

		wp_deploy: {
			options: {
				plugin_slug: 'avatar-privacy',
				svn_url: "https://plugins.svn.wordpress.org/{plugin-slug}/",
				// svn_user: 'your-wp-repo-username',
				build_dir: 'build', //relative path to your build directory
				assets_dir: 'wp-assets', //relative path to your assets directory (optional).
				max_buffer: 1024 * 1024
			},
			release: {
				// nothing
				deploy_trunk: true,
				deploy_tag: true,
			},
			trunk: {
				options: {
					deploy_trunk: true,
					deploy_assets: true,
					deploy_tag: false,
				}
			},
			assets: {
				options: {
					deploy_assets: true,
					deploy_trunk: false,
					deploy_tag: false,
				}
			}
		},

		eslint: {
			src: [
				'admin/js/**/*.js',
				'admin/blocks/src/**/*.js',
				'public/js/**/src/*.js',
				'!**/*.min.js',
			]
		},

		sass: {
			options: {
				implementation: sass,
			},
			dist: {
				options: {
					outputStyle: 'compressed',
					sourceComments: false,
					sourcemap: 'none',
				},
				files: [{
						expand: true,
						cwd: 'admin/scss',
						src: ['**/*.scss'],
						dest: 'build/admin/css',
						ext: '.min.css'
					},
					{
						expand: true,
						cwd: 'public/scss',
						src: ['**/*.scss'],
						dest: 'build/public/css',
						ext: '.min.css'
					}
				]
			},
			dev: {
				options: {
					outputStyle: 'expanded',
					sourceComments: false,
					sourceMapEmbed: true,
				},
				files: [{
						expand: true,
						cwd: 'admin/scss',
						src: ['**/*.scss'],
						dest: 'admin/css',
						ext: '.css'
					},
					{
						expand: true,
						cwd: 'public/scss',
						src: ['**/*.scss'],
						dest: 'public/css',
						ext: '.css'
					}
				]
			}
		},

		postcss: {
			options: {
				map: true, // inline sourcemaps.
				processors: [
					require('pixrem')(), // add fallbacks for rem units
					require('autoprefixer')() // add vendor prefixes
				]
			},
			dev: {
				files: [{
						expand: true,
						cwd: 'admin/css',
						src: ['**/*.css'],
						dest: 'admin/css',
						ext: '.css'
					},
					{
						expand: true,
						cwd: 'public/css',
						src: ['**/*.css'],
						dest: 'public/css',
						ext: '.css'
					}
				]
			},
			dist: {
				files: [{
						expand: true,
						cwd: 'build/admin/css',
						src: ['**/*.css'],
						dest: 'build/admin/css',
						ext: '.css'
					},
					{
						expand: true,
						cwd: 'build/public/css',
						src: ['**/*.css'],
						dest: 'build/public/css',
						ext: '.css'
					}
				]
			}
		},

		// uglify targets are dynamically generated by the minify task
		uglify: {
			options: {
				banner: '/*! <%= pkg.name %> <%= ugtargets[grunt.task.current.target].filename %> <%= grunt.template.today("yyyy-mm-dd h:MM:ss TT") %> */\n',
				report: 'min',
			},
		},

		minify: {
			dist: {
				expand: true,
				//dest: 'build/',
				files: grunt.file.expandMapping([
						'admin/js/**/*.js',
						'public/js/**/*.js',
						'!**/src/*.js',
						'!**/*min.js',
					], 'build/', {
					rename: function(destBase, destPath) {
						return destBase + destPath.replace('.js', '.min.js');
					}
				})
			},
		},

		compress: {
			beta: {
				options: {
					mode: 'zip',
					archive: '<%= pkg.name %>-<%= wpversion %>.zip'
				},
				files: [{
					expand: true,
					cwd: 'build/',
					src: ['**/*'],
					dest: '<%= pkg.name %>/',
				}],
			}
		},
	});

	// Set correct pattern for naemspace replacement.
	grunt.config(
		'string-replace.namespaces.options.replacements.0.pattern',
		new RegExp( '([^\\w\\\\]|\\B\\\\?)((?:' + grunt.config('pkg.phpPrefixNamespaces').join('|') + ')\\\\[\\w_]+)', 'g' )
	);

	// load all tasks
	require('load-grunt-tasks')(grunt, {
		scope: 'devDependencies'
	});

	// Load NPM scripts as tasks.
	require('grunt-load-npm-run-tasks')(grunt);

	grunt.registerTask('default', [
		'newer:eslint',
		'newer:exec:composer_dev:phpcs',
		'newer:sass:dev',
		'newer:postcss:dev'
	]);

	grunt.registerTask('build', [
		// Clean house
		'clean:build',
		// Scope dependencies
		'exec:composer_dev:scope-dependencies',
		// Rename vendor directory
		'string-replace:composer-vendor-dir',
		'rename:vendor',
		// Generate stylesheets
		'newer:sass:dist',
		'newer:postcss:dist',
		// Build scripts.
		'build-js',
		// Copy other files
		'copy:main',
		'copy:meta',
		// Use scoped dependencies
		'string-replace:namespaces',
		'exec:composer_build:build-wordpress',
		// Clean up unused packages
		'clean:autoloader',
		'clean:vendor',
		'string-replace:vendor-dir',
		'string-replace:autoloader',
	]);

	grunt.registerTask('build-js', [
			'npmRun:build-wpdiscuz',
			'npmRun:build-blocks',
			'newer:minify',
	]);

	grunt.registerTask('build-beta', [
		'build',
		'compress:beta',
	]);

	// dynamically generate uglify targets
	grunt.registerMultiTask('minify', function() {
		this.files.forEach(function(file) {
			var path = file.src[0],
				target = path.match(/([^.]*)\.js/)[1];

			// store some information about this file in config
			grunt.config('ugtargets.' + target, {
				path: path,
				filename: path.split('/').pop()
			});

			// create and run an uglify target for this file
			grunt.config('uglify.' + target + '.files', [{
				src: [path],
				dest: path.replace(/^(.*)\.js$/, 'build/$1.min.js')
			}]);
			grunt.task.run('uglify:' + target);
		});
	});

	grunt.registerTask('deploy', [
		'exec:composer_dev:phpcs',
		'eslint',
		'build',
		'wp_deploy:release'
	]);

	grunt.registerTask('trunk', [
		'exec:composer_dev:phpcs',
		'eslint',
		'build',
		'wp_deploy:trunk'
	]);

	grunt.registerTask('assets', [
		'clean:build',
		'copy',
		'wp_deploy:assets'
	]);

};
