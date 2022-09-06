// Inside your Gruntfile.js
module.exports = function (grunt) {
    // Define a zip task
    grunt.initConfig({
        copy: {
            main: {
                files: [
                    // includes files within path
                    {
                        expand: false,
                        src: '../../language/en-GB/mod_tinyslider.ini',
                        dest: 'language/en-GB/mod_tinyslider.ini',
                        filter: 'isFile'
                    },
                    {
                        expand: false,
                        src: '../../language/en-GB/mod_tinyslider.sys.ini',
                        dest: 'language/en-GB/mod_tinyslider.sys.ini',
                        filter: 'isFile'
                    },
                    {
                        expand: false,
                        src: '../../language/nl-NL/mod_tinyslider.ini',
                        dest: 'language/nl-NL/mod_tinyslider.ini',
                        filter: 'isFile'
                    },
                    {
                        expand: false,
                        src: '../../language/nl-NL/mod_tinyslider.sys.ini',
                        dest: 'language/nl-NL/mod_tinyslider.sys.ini',
                        filter: 'isFile'
                    },
                    {
                        expand: true,
                        cwd: '../../media/mod_tinyslider/',
                        src: '**',
                        dest: 'media/',
                        filter: 'isFile'
                    },
                ],
            },
        },

        zip: {
            'mod_tinyslider-1.0.0.zip': [
                'language/**',
                'media/**',
                'src/**',
                'tmpl/**',
                'mod_tinyslider.php',
                'mod_tinyslider.xml',
            ]
        },

        rename: {
            main: {
                files: [
                    {src: ['mod_tinyslider-1.0.0.zip'], dest: 'mod_tinyslider-' + grunt.template.today('yyyymmdd-HHMMss') + '.zip'},
                ]
            }
        },
    });

    // Load in `grunt-zip`
    grunt.loadNpmTasks('grunt-zip');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-rename');

    // To run use: grunt build
    grunt.task.registerTask('build', ['copy', 'zip', 'rename']);
  };