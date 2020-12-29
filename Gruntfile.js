/**
 * Main Grunt file.
 *
 * @package Lampalagua-Theme.
 */

module.exports = function (grunt) {
	grunt.initConfig(
		{
			sync: {
				all: {
					files: [{
						cwd: 'src/',
						src: [
							'**/*.php',
							'**/*.jpg', '**/*.png', '**/*.gif', '**/*.ico',  '**/*.svg',
							'**/*.eot', '**/*.ttf', '**/*.woff', '**/*.woff2',
							'**/*.css',
							'**/*.js',
							'**/*.txt'
						],
						dest: 'dst/',
					}]
				},
				verbose: true,
				updateAndDelete: true,
			},
			watch: {
				all: {
					files: [
						'src/**/*.php',
						'src/**/*.scss',
						'src/**/*.css',
						'src/**/*.js',
						'src/**/*.txt'
					],
					options: {
						interval: 500,
					},
					tasks: ['sync:all'],
				}
			},
			clean: {
				dst: {
					src:['dst/*']
				}
			}
		}
	)

	grunt.loadNpmTasks( 'grunt-sync' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );

	grunt.registerTask(
		'dist',
		[
			'clean:dst',
			'sync:all',
			]
	)

	grunt.registerTask(
		'dev',
		[
			'watch:all',
		]
	);

};
