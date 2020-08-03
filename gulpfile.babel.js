import gulp from 'gulp';
import browserSync from 'browser-sync';
import sass from 'gulp-sass';
import postcss from 'gulp-postcss';
import uglify from 'gulp-uglify';
import del from 'del';
import autoprefixer from 'autoprefixer';
import rename from 'gulp-rename';
import babel from 'gulp-babel';

const server = browserSync.create();
const clean = () => del(['dist']);

function sassTask() {
  return gulp.src(["css/*.scss", "css/**/*.scss"])
    .pipe(sass({
      errLogToConsole: true,
      outputStyle: 'compressed'
    }))
    .pipe(postcss([autoprefixer()]))
	  .pipe(rename({suffix: '.min'}))
    .pipe(gulp.dest("css"));
}

function javascriptTask() {
  return gulp.src(["js/*.js", "!js/*.min.js"])
    .pipe(babel({
      presets: ['@babel/env']
    }))
    .pipe(uglify())
    .pipe(rename({suffix: '.min'}))
    .pipe(gulp.dest("js"));
}

function watchTask() {
  gulp.watch(["css/*.scss", "css/**/*.scss"], sassTask);
  gulp.watch(["js/*.js", "!js/*.min.js"], javascriptTask);
}

const dev = gulp.series(clean, sassTask, javascriptTask, watchTask);
export default dev;
