var gulp = require('gulp');

var $ = require('gulp-load-plugins')({
    pattern: ['gulp-*']
});

gulp.task('less', function() {
    return gulp.src('./Resources/public/less/gwu.less')  // only compile the entry file
        .pipe($.plumber())
        .pipe($.less())
        .pipe($.rename('style.css'))
        //.pipe($.prefix("last 8 version", "> 1%", "ie 8", "ie 7"), {cascade:true})
        .pipe(gulp.dest('./Resources/public/css'));
});

// Watch all the .less files, then run the less task
gulp.task('watch', function() {
    gulp.watch('./Resources/public/less/gwu.less', ['less']);
});

// Default will run the 'entry' watch task
gulp.task('default', ['watch']);