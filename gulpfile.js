// include plug-ins
var gulp = require('gulp');
var debug = require('gulp-debug');
var gulpif = require("gulp-if");
var replace = require('gulp-replace');
var watch = require('gulp-watch');
var rename = require('gulp-rename');

var jshint = require('gulp-jshint');
var uglify = require('gulp-uglify');
var spritesmith = require("gulp-spritesmith");

// settings
var base = './htdocs/frontend/';
var dest = './htdocs/frontend/';

/**
 * Defaults
 */
gulp.task('build', ['sprites'/*, 'scripts', 'styles', 'replace'*/]);

// watch for JS changes
gulp.task('default', function() {
	gulp.src(base + 'javascripts/**/*.js', { read: false })
		.pipe(watch())
		.pipe(jshint())
		.pipe(jshint.reporter('default'));
});

/**
 * jsHint JS scripts
 */
gulp.task('jshint', function() {
	gulp.src([base + 'javascripts/!(property)*.js'])
		.pipe(jshint())
		.pipe(jshint.reporter('default'));
});

/**
 * Create CSS sprites for icons
 */
gulp.task('sprites', ['sprites-combine', 'sprites-optimize']);

// Combine images into sprite
gulp.task('sprites-combine', function () {
	var imgBase = base + 'images/';
	var cssDst = 'stylesheets/sprites.max.css';

	gulp.src([imgBase + '!(sprites|blank|ui-|style_|empty)*.png', imgBase + 'types/*!(32).png'])
		.pipe(spritesmith({
			imgName: 'images/sprites.png',
			styleName: cssDst,
			imgPath: '../images/sprites.png',
			algorithm: 'top-down'
		}))
		.pipe(gulpif('*.png', gulp.dest(dest)))
		.pipe(gulpif('*.css', gulp.dest(dest)));
});

// Rewrite sprites.css
gulp.task('sprites-optimize', function () {
	var cssDst = 'stylesheets/sprites.max.css';

	// remove dimensions and save as new css file
	gulp.src(base + cssDst)
		.pipe(replace(/  width: 16px;\n  height: 16px;\n/g, ''))
		.pipe(rename('sprites.css'))
		.pipe(gulp.dest(dest + 'stylesheets/'));
});
