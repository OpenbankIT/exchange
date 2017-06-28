var gulp = require('gulp'),
    sass = require('gulp-sass'),
    prefix = require('gulp-autoprefixer'),
    minifycss = require('gulp-minify-css'),
    notify = require('gulp-notify'),
    jshint = require('gulp-jshint'),
    concat = require('gulp-concat'),
    rename = require('gulp-rename'),
    uglify = require('gulp-uglify'),
    livereload = require('gulp-livereload'),
    imagemin = require('gulp-imagemin'),
    cache = require('gulp-cache'),

var assets_path = '.';
var deploy_path = '../public';

// Styles
gulp.task('styles', function() {
    gulp.src('./scss/**/*.scss')
        .pipe(sass({
            outputStyle: 'expanded'
        }))
        .pipe(prefix({
            browsers: ['last 2 versions'],
            cascade: false
        }))
        .pipe(minifycss())
        .pipe(gulp.dest(deploy_path + '/css'))
        .pipe(notify({
            message: 'Styles task complete',
            onLast: true
        }));
});

// Js hint
gulp.task('jshint', function() {
    gulp.src('./js/app.js')
        .pipe(jshint())
        .pipe(jshint.reporter('jshint-stylish'));
})

// Minify Scripts
gulp.task('scripts', ['jshint'], function() {
    return gulp.src('./js/app.js')
        .pipe(include())
        .on('error', console.log)
        .pipe(rename({
            suffix: '.min'
        }))
        // .pipe(uglify())
        .pipe(gulp.dest(deploy_path + '/js'))
        .pipe(notify({
            message: 'Scripts task complete',
            onLast: true
        }));
});

// Images
gulp.task('images', function() {
    return gulp.src('./img/**/*')
        .pipe(cache(imagemin()))
        .pipe(gulp.dest(deploy_path + '/img'))
        .pipe(notify({
            message: 'Images task complete',
            onLast: true
        }));
});

// Default task
gulp.task('default', function() {
    gulp.run('bower', 'styles', 'scripts', 'images');
});

// Watch
gulp.task('watch', function() {

    livereload.listen();

    // Watch .scss files
    gulp.watch(assets_path + '/scss/**/*.scss', ['styles']).on('change', livereload.changed);

    // Watch .js files
    gulp.watch(assets_path + '/js/**/*.js', ['scripts']).on('change', livereload.changed);

    // Watch image files
    gulp.watch(assets_path + '/img/**/*', ['images']).on('change', livereload.changed);
});
