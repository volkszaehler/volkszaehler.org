/**
 * Colour scheme (light / dark / follow system) for the web frontend
 *
 * Loaded in <head> so that the scheme is set before the first paint. Has no
 * dependencies; uses vz.* and jQuery only when they exist at call time.
 *
 * The choice is stored in the cookie `vz_theme` (auto | light | dark) and is
 * applied immediately, independent of the "save options" button.
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
 */

(function(window, document) {
	'use strict';

	var COOKIE = 'vz_theme';
	var MODES = ['auto', 'light', 'dark'];
	var BG_LIGHT = '#ffffff';
	var BG_DARK = '#14171a';
	var PLOT_BG_DARK = '#1d2125';
	var MIN_LUMINANCE = 0.22; // series colours darker than this are lightened

	var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
	var current = null; // 'dark' | 'light' as currently applied

	function readMode() {
		var match = document.cookie.match(/(?:^|;\s*)vz_theme=([^;]*)/);
		var value = match ? decodeURIComponent(match[1]) : null;
		return MODES.indexOf(value) >= 0 ? value : 'auto';
	}

	function writeMode(mode) {
		var expires = new Date(2038, 0, 1); // same lifetime as the other vz_ cookies
		document.cookie = COOKIE + '=' + encodeURIComponent(mode) + '; expires=' + expires.toUTCString() + '; SameSite=Lax';
	}

	/**
	 * Parse #rgb, #rrggbb or rgb()/rgba() to [r, g, b]; null if unknown
	 */
	function parseColor(str) {
		var m;
		if (typeof str !== 'string') {
			return null;
		}
		str = str.trim();
		if ((m = /^#([0-9a-f])([0-9a-f])([0-9a-f])$/i.exec(str))) {
			return [parseInt(m[1] + m[1], 16), parseInt(m[2] + m[2], 16), parseInt(m[3] + m[3], 16)];
		}
		if ((m = /^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i.exec(str))) {
			return [parseInt(m[1], 16), parseInt(m[2], 16), parseInt(m[3], 16)];
		}
		if ((m = /^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i.exec(str))) {
			return [+m[1], +m[2], +m[3]];
		}
		return null;
	}

	/**
	 * Relative luminance (WCAG) of [r, g, b]
	 */
	function luminance(rgb) {
		var c = rgb.map(function(v) {
			v /= 255;
			return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
		});
		return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
	}

	function toHex(rgb) {
		return '#' + rgb.map(function(v) {
			return ('0' + Math.round(v).toString(16)).slice(-2);
		}).join('');
	}

	var vzTheme = {

		/** stored choice: auto | light | dark */
		getMode: readMode,

		/** effective scheme */
		isDark: function() {
			var mode = readMode();
			if (mode === 'auto') {
				return !!(media && media.matches);
			}
			return mode === 'dark';
		},

		setMode: function(mode) {
			if (MODES.indexOf(mode) < 0) {
				return;
			}
			writeMode(mode);
			this.apply();
		},

		/**
		 * Set the scheme on <html>, update the browser colour and redraw the
		 * chart if the effective scheme changed
		 */
		apply: function() {
			var scheme = this.isDark() ? 'dark' : 'light';
			var changed = scheme !== current;

			current = scheme;
			document.documentElement.setAttribute('data-theme', scheme);

			var meta = document.querySelector('meta[name=theme-color]');
			if (meta) {
				meta.setAttribute('content', scheme === 'dark' ? BG_DARK : BG_LIGHT);
			}

			var select = document.getElementById('theme');
			if (select && select.value !== readMode()) {
				select.value = readMode();
			}

			if (changed && window.vz && window.vz.plot && window.vz.wui && typeof window.vz.wui.drawPlot === 'function') {
				window.vz.wui.drawPlot();
			}
		},

		/**
		 * Adjust flot options (a copy of vz.options.plot) for the current scheme
		 */
		plotOptions: function(options) {
			if (!this.isDark()) {
				return options;
			}

			options.grid = options.grid || {};
			options.grid.color = '#b9c0c6';
			options.grid.borderColor = '#4a525a';
			options.grid.tickColor = 'rgba(255, 255, 255, 0.10)';

			options.legend = options.legend || {};
			options.legend.labelBoxBorderColor = '#4a525a';
			options.legend.backgroundColor = PLOT_BG_DARK;

			options.crosshair = options.crosshair || {};
			options.crosshair.color = 'rgba(255, 110, 110, 0.90)';

			return options;
		},

		/**
		 * Lighten colours that would vanish on the dark plot background
		 */
		seriesColor: function(color) {
			if (!this.isDark()) {
				return color;
			}
			var rgb = parseColor(color);
			if (!rgb || luminance(rgb) >= MIN_LUMINANCE) {
				return color;
			}
			for (var t = 0.05; t <= 1; t += 0.05) {
				var mixed = rgb.map(function(v) {
					return v + (255 - v) * t;
				});
				if (luminance(mixed) >= MIN_LUMINANCE) {
					return toHex(mixed);
				}
			}
			return '#ffffff';
		},

		/**
		 * Canvas for PNG export: the plot canvas is transparent, so in dark mode
		 * paint it onto the plot background to keep the light grid readable
		 */
		exportCanvas: function(canvas) {
			if (!this.isDark()) {
				return canvas;
			}
			var flat = document.createElement('canvas');
			flat.width = canvas.width;
			flat.height = canvas.height;
			var ctx = flat.getContext('2d');
			ctx.fillStyle = PLOT_BG_DARK;
			ctx.fillRect(0, 0, flat.width, flat.height);
			ctx.drawImage(canvas, 0, 0);
			return flat;
		}
	};

	window.vzTheme = vzTheme;

	// before first paint
	vzTheme.apply();

	// follow system changes while in "auto"
	if (media) {
		var onSystemChange = function() {
			if (readMode() === 'auto') {
				vzTheme.apply();
			}
		};
		if (media.addEventListener) {
			media.addEventListener('change', onSystemChange);
		}
		else if (media.addListener) {
			media.addListener(onSystemChange); // older Safari
		}
	}

	// options dialog
	document.addEventListener('DOMContentLoaded', function() {
		var select = document.getElementById('theme');
		if (!select) {
			return;
		}
		select.value = readMode();
		select.addEventListener('change', function() {
			vzTheme.setMode(select.value);
		});
	});

})(window, document);
