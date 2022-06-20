module.exports = function (grunt) {
    let version = grunt.file.readJSON('composer.json').version;
    let releaseFilename = 'release-v' + version + '.zip';
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        jshint: {
            all: ['Gruntfile.js', 'src/js/app.js'],
            options: {
                'esversion': 6,
            }
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
                    'install/installer.js': [
                        'src/js/installer.js'
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
                    'src/js/installer.js',
                ],

                tasks: ['uglify']
            }
        },

        copy: {
            main: {
                files: [
                    {
                        expand: true,
                        cwd: 'node_modules/@fortawesome/fontawesome-free',
                        src: ['css/all.min.css', 'webfonts/**/*'],
                        dest: 'static/fontawesome'
                    },
                    {
                        expand: true,
                        cwd: 'node_modules/bootstrap/dist/css',
                        src: ['bootstrap.min.css'],
                        dest: 'static/bootstrap/css'
                    },
                    {
                        expand: true,
                        cwd: 'node_modules/bootstrap/dist/js',
                        src: ['bootstrap.bundle.min.js'],
                        dest: 'static/bootstrap/js'
                    },
                    {
                        expand: true,
                        cwd: 'node_modules/clipboard/dist',
                        src: ['clipboard.min.js'],
                        dest: 'static/clipboardjs'
                    },
                    {
                        expand: true,
                        cwd: 'node_modules/plyr/dist',
                        src: ['plyr.min.js', 'plyr.css'],
                        dest: 'static/plyr'
                    },
                    {
                        expand: true,
                        cwd: 'node_modules/highlightjs',
                        src: ['styles/**/*', 'highlight.pack.min.js'],
                        dest: 'static/highlightjs'
                    },                    {
                        expand: true,
                        cwd: 'node_modules/highlightjs-line-numbers.js/dist',
                        src: ['highlightjs-line-numbers.min.js'],
                        dest: 'static/highlightjs'
                    },
                    {
                        expand: true,
                        cwd: 'node_modules/dropzone/dist/min',
                        src: ['dropzone.min.css', 'dropzone.min.js'],
                        dest: 'static/dropzone'
                    },
                    {
                        expand: true,
                        cwd: 'node_modules/bootstrap4-toggle/css',
                        src: ['bootstrap4-toggle.min.css'],
                        dest: 'static/bootstrap/css'
                    },
                    {
                        expand: true,
                        cwd: 'node_modules/bootstrap4-toggle/js',
                        src: ['bootstrap4-toggle.min.js'],
                        dest: 'static/bootstrap/js'
                    },
                    {
                        expand: true,
                        cwd: 'src/images',
                        src: ['**/*'],
                        dest: 'static/images'
                    },
                    {expand: true, cwd: 'node_modules/jquery/dist', src: ['jquery.min.js'], dest: 'static/jquery'}
                ],
            },
        },

        shell: {
            phpstan: {
                command: '"./vendor/bin/phpstan" --level=0 analyse app resources/lang bin install'
            },
            composer_no_dev: {
                command: 'composer install --no-dev --prefer-dist'
            }
        },

        compress: {
            main: {
                options: {
                    archive: releaseFilename,
                    mode: 'zip',
                    level: 9,
                },
                files: [{
                    expand: true,
                    cwd: './',
                    src: [
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
                        'resources/uploaders/**/*',
                        'static/**/*',
                        'vendor/**/*',
                        '.htaccess',
                        'config.example.php',
                        'index.php',
                        'composer.json',
                        'composer.lock',
                        'LICENSE',
                        'favicon.ico',
                        'CHANGELOG.md'
                    ],
                    dest: '/'
                }]
            }
        },

    });

    require('load-grunt-tasks')(grunt);
    grunt.registerTask('default', ['jshint', 'cssmin', 'uglify', 'copy']);
    grunt.registerTask('test', ['jshint']);
    grunt.registerTask('phpstan', ['shell:phpstan']);
    grunt.registerTask('composer_no_dev', ['shell:composer_no_dev']);
    grunt.registerTask('build-release', ['default', 'composer_no_dev', 'compress']);
};