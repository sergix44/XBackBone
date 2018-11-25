module.exports = function (grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        jshint: {
            all: ['Gruntfile.js', 'src/js/app.js']
        },

        cssmin: {
            build: {
                files: {
                    'static/app/app.css': [
                        'src/css/app.css'
                    ]
                }
            }
        },

        uglify: {
            options: {
                preserveComments: false,
                compress: true
            },
            build: {
                files: {
                    'static/app/app.js': [
                        'src/js/app.js'
                    ],
                }
            }
        },

        watch: {
            css: {
                files: [
                    'src/css/app.css'
                ],

                tasks: ['cssmin']
            },
            scripts: {
                files: [
                    'src/js/app.js',
                ],

                tasks: ['uglify']
            }
        },

        copy: {
            main: {
                files: [
                    {expand: true, cwd: 'node_modules/@fortawesome/fontawesome-free', src: ['css/**', 'js/**'], dest: 'static/fontawesome'},
                    {expand: true, cwd: 'node_modules/bootstrap/dist', src: ['**'], dest: 'static/bootstrap'},
                    {expand: true, cwd: 'node_modules/clipboard/dist', src: ['**'], dest: 'static/clipboardjs'},
                    {expand: true, cwd: 'node_modules/video.js/dist', src: ['video.min.js', 'video-js.min.css'], dest: 'static/videojs'},
                    {expand: true, cwd: 'node_modules/highlightjs', src: ['styles/**/*', 'highlight.pack.min.js'], dest: 'static/highlightjs'},
                    {expand: true, cwd: 'node_modules/jquery/dist', src: ['jquery.min.js'], dest: 'static/jquery'}
                ],
            },
        },

        zip: {
            'release.zip': [
                'app/**/*',
                'bin/**/*',
                'bootstrap/**/*',
                'install/**/*',
                'logs/**/',
                'resources/cache',
                'resources/sessions',
                'resources/database',
                'resources/lang/**/*',
                'resources/templates/**/*',
                'resources/schemas/**/*',
                'resources/lang/**/*',
                'static/**/*',
                'vendor/**/*',
                '.htaccess',
                'config.example.php',
                'index.php',
                'composer.json',
                'composer.lock'
            ]
        }

    });

    require('load-grunt-tasks')(grunt);
    grunt.registerTask('default', ['jshint', 'cssmin', 'uglify', 'copy']);
    grunt.registerTask('test', ['jshint']);
    grunt.registerTask('build-release', ['default', 'zip']);

};