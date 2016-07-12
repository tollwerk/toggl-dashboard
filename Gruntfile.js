/* global module:false */
module.exports = function (grunt) {
    var fs = require('fs');
    require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

    grunt.initConfig({
        sass: {
            dist: {
                files: [{
                    expand: true,
                    cwd: 'src/Toggl/Infrastructure/Sass',
                    src: ['**/*.scss'],
                    dest: 'dist/css',
                    rename: function (dest, src) {
                        var folder = src.substring(0, src.lastIndexOf('/')),
                            filename = src.substring(src.lastIndexOf('/'), src.length);
                        filename = filename.substring(0, filename.lastIndexOf('.'));
                        return dest + '/' + folder + filename + '.css';
                    }
                }],
                options: {
                    sourcemap: false,
                    style: 'nested'
                }
            }
        },

        favicons: {
            options: {
                html: 'dist/favicons.html',
                HTMLPrefix: '/favicon/',
                precomposed: false,
                firefox: true,
                firefoxManifest: 'public/favicon/dashboard.webapp',
                appleTouchBackgroundColor: 'transparent'
            },
            icons: {
                src: 'src/Toggl/Infrastructure/Favicon/tollwerk.png',
                dest: 'public/favicon'
            }
        },

        copy: {
            favicon: {
                src: 'public/favicon/favicon.ico',
                dest: 'public/favicon.ico'
            }
        },

        replace: {
            favicon: {
                src: ['dist/favicons.html'],
                overwrite: true,
                replacements: [{
                    from: /[\t\r\n]+/g,
                    to: ''
                }, {
                    from: /<link rel="shortcut icon".*/g,
                    to: '<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon"/><link rel="icon" href="/favicon.ico" type="image/x-icon"/>'
                }]
            }
        },

        autoprefixer: {
            options: {
                browsers: ['last 3 versions', 'ie 8'],
                map: true
            },
            dist: {
                expand: true,
                flatten: true,
                src: 'dist/css/*.css',
                dest: 'dist/css/'
            }
        },

        cssmin: {
            dist: {
                expand: true,
                cwd: 'dist/css',
                src: ['**/*.css', '!**/*.min.css'],
                dest: 'public/css',
                rename: function (dest, src) {
                    var folder = src.substring(0, src.lastIndexOf('/')),
                        filename = src.substring(src.lastIndexOf('/'), src.length);
                    filename = filename.substring(0, filename.lastIndexOf('.'));
                    return dest + '/' + folder + filename + '.min.css';
                }
            }
        },

        concat_sourcemap: {
            options: {
                sourceRoot: '/'
            },
            javascript: {
                expand: true,
                cwd: 'src/js/',
                src: ['**/*.js'],
                dest: 'public/js',
                ext: '.js',
                extDot: 'last',
                rename: function (dest, src) {
                    return dest + '/' + ((src.indexOf('/') >= 0) ? (src.substring(0, src.indexOf('/')) + '.js') : src);
                }
            }
        },

        uglify: {
            options: {
                sourceMap: false,
                sourceMapIn: function (input) {
                    return fs.existsSync(input + '.map') ? (input + '.map') : null;
                }
            },
            javascript: {
                expand: true,
                cwd: 'src/Toggl/Infrastructure/Javascript/',
                src: ['**/*.js', '!**/*.min.js'],
                dest: 'public/js/',
                ext: '.min.js',
                extDot: 'last'
            }
        },

        clean: {
            general: ['public/css/*.css', 'public/css/*.min.css'],
            favicon: ['favicon.ico']
        },

        watch: {
            sass: {
                files: ['src/Toggl/Infrastructure/Sass/**/*.scss'],
                tasks: ['sass:dist']
            },

            // Watch changing CSS resources
            cssNoconcat: {
                files: ['dist/css/*.css'],
                tasks: ['autoprefixer:dist', 'cssmin:dist'],
                options: {
                    spawn: true
                }
            },

            // Watch & uglify changing JavaScript resources
            javascript: {
                files: ['src/Toggl/Infrastructure/Javascript/**/*.js'],
                tasks: ['concat_sourcemap:javascript', 'uglify'],
                options: {
                    spawn: true
                }
            },

            grunt: {
                files: ['Gruntfile.js'],
                options: {
                    reload: true
                }
            }
        }
    });

    // Default task.
    grunt.registerTask('default', ['sass', 'css', 'js']);
    grunt.registerTask('css', ['clean:general', 'sass', 'autoprefixer', 'cssmin']);
    grunt.registerTask('js', ['concat_sourcemap:javascript', 'uglify']);
    grunt.registerTask('favicon', ['clean:favicon', 'favicons', 'copy:favicon', 'replace:favicon']);
};
