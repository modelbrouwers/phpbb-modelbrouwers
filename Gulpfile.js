'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass');
var bourbon = require('bourbon');
var neat = require('bourbon-neat');
var sourcemaps = require('gulp-sourcemaps');
var autoprefixer = require('gulp-autoprefixer');


var sass_src = 'sass/**/*.scss';

gulp.task('sass', function() {
    gulp.src(sass_src)
        .pipe(sourcemaps.init())
        .pipe(sass({
            outputStyle: 'expanded',
            includePaths: bourbon.includePaths.concat(neat.includePaths)
        }).on('error', sass.logError))
        .pipe(autoprefixer({
            browsers: ['last 2 versions'],
            cascade: false
        }))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./styles'));
});


// watch task
gulp.task('default', ['sass'], function() {
    gulp.watch(sass_src, ['sass']);
});
