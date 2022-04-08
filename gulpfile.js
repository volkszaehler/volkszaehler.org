// include plug-ins
var gulp = require('gulp');
var $ = require('gulp-load-plugins')({
	pattern: ['gulp-*', 'gulp.*', 'watch', 'del'],
});

// settings
var base = './htdocs/';
var build = base + 'build/';

// styles
var styles = base + 'css/';

// images
var images = base + 'img/';

// js
var vendor = base + 'vendor/';
var javascripts = base + 'js/';
var flot = base + 'vendor/flot/';
var extensions = base + 'js/jquery/';
var jsfiles = [
	'gulpfile.js',
	base + 'js/**/*.js',
	'!' + base + 'js/canvas/**/*.js',
	'!' + base + 'js/jquery/**/*.js',
	'!**/*.min.js'
];

/**
 * Defaults
 */
gulp.task('default', ['jshint', 'watch']);


/**
 * Watch javascripts
 */
gulp.task('watch', function() {
	gulp.src(jsfiles)
		.pipe($.watch(jsfiles))
		.pipe($.jshint())
		.pipe($.jshint.reporter('default'));
});


/**
 * Build
 */
gulp.task('build', ['clean', 'jshint', 'scripts', 'sprites']);

gulp.task('clean', function () {
	$.del([
		build + '**'
	]);
});

gulp.task('jshint', function() {
	gulp.src(jsfiles)
		.pipe($.jshint())
		.pipe($.jshint.reporter('default'))
		.pipe($.jshint.reporter('fail'));
});


/**
 * Scripts
 */
gulp.task('scripts', ['flot', 'jquery-ext', 'vz-scripts']);

gulp.task('vz-scripts', function() {
	return gulp.src([
		javascripts + 'helper.js',	// in order of index.html
		javascripts + 'init.js',
		javascripts + 'functions.js',
		javascripts + 'entities.js',
		javascripts + 'wui.js',
		javascripts + 'plot.js',
		javascripts + 'entity.js',
		'!**/options.js',						// exclude options
	])
		.pipe($.concat('scripts.js'))
		.pipe(gulp.dest(build))				// for reference only
		.pipe($.size({showFiles: true}))
		.pipe($.uglify())
		.pipe($.rename('scripts.min.js'))
		.pipe(gulp.dest(javascripts))
		.pipe($.size({showFiles: true}));
});

gulp.task('flot', function() {
	return gulp.src([
		flot + '**/jquery.flot.js',	// flot first
		// flot + '**.js',							// then flot modules
		flot + '**/jquery.flot.crosshair.js',
		flot + '**/jquery.flot.selection.js',
		flot + '**/jquery.flot.time.js',
		flot + '**/jquery.flot.canvas.js',
		flot + '**/jquery.flot.axislabels.js',
		flot + '**/jquery.flot.xgap.js',
		flot + '**/jquery.flot.orderBars.js',
		flot + '**/date.js',
		flot + '**/canvas2image.js',
		flot + '**/base64.js',
		'!**/excanvas*.js',					// omit canvas helper
		'!**/*.min.js',							// omit minified files
	])
		.pipe($.concat('flot.js'))
		.pipe(gulp.dest(build))				// for reference only
		.pipe($.size({showFiles: true}))
		.pipe($.uglify())
		.pipe($.rename('flot.min.js'))
		.pipe(gulp.dest(flot))
		.pipe($.size({showFiles: true}));
});

gulp.task('jquery-ext', function() {
	return gulp.src([
		extensions + '**.js',
		'!**/*.min.js',							// omit minified files
	])
		.pipe($.concat('jquery-extensions.js'))
		.pipe(gulp.dest(build))				// for reference only
		.pipe($.size({showFiles: true}))
		.pipe($.uglify())
		.pipe($.rename('jquery-ext.min.js'))
		.pipe(gulp.dest(extensions))
		.pipe($.size({showFiles: true}));
});


/**
 * Sprites
 */
gulp.task('sprites', function () {
	// Combine images into sprite
	var spriteData = gulp.src([
		images + '!(sprites|blank|ui-|style_|empty)*.png',
		images + 'types/*!(32).png'
	])
		.pipe($.spritesmith({
			imgName: '../img/sprites.png',	// link to images folder
			cssName: 'sprites.css',
			algorithm: 'top-down',
			padding: 2
		}));

	spriteData.img
		.pipe($.imagemin())
		.pipe(gulp.dest(images));

	spriteData.css
		.pipe(gulp.dest(build));

	// remove dimensions and save as new css file
	gulp.src(build + 'sprites.css')
		.pipe($.replace(/  width: 16px;\n  height: 16px;\n/g, ''))
		.pipe(gulp.dest(styles));
});


/**
 * CSS
 */
// not used
gulp.task('css-minimize', function () {
	gulp.src(styles + '*.css')
		.pipe($.concat('styles.css'))
		.pipe(gulp.dest(build))
		.pipe($.cssnano({
			keepBreaks:true
		}))
		.pipe($.rename('styles.min.css'))
		.pipe(gulp.dest(build));
});

// not used
gulp.task('css-strip', function() {
	gulp.src(styles + '*.css')
		// gulp.src(build + 'styles.min.css')
		.pipe($.uncss({
			html: [base + 'index.html']
		}))
		// .pipe($.rename('styles.min.css'))
		.pipe(gulp.dest(build))
		.pipe(cssnano({
			keepBreaks:true
		}))
		.pipe(rename('styles.min.css'))
		.pipe(gulp.dest(build));
});

// not used
gulp.task('css-strip', function() {
	gulp.src(styles + '*.css')
		// gulp.src(build + 'styles.min.css')
		.pipe(uncss({
				html: [base + 'index.html']
		}))
		// .pipe(rename('styles.min.css'))
		.pipe(gulp.dest(build));
});
