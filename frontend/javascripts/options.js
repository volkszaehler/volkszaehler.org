/**
 * Frontend configuration
 * 
 * @author Florian Ziegler <fz@f10-home.de>
 * @author Justin Otherguy <justin@justinotherguy.org>
 * @author Steffen Vogel <info@steffenvogel.de>
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package default
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */
/*
 * This file is part of volkzaehler.org
 * 
 * volkzaehler.org is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or any later version.
 * 
 * volkzaehler.org is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU General Public License along with
 * volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
 */

// default time interval to show
vz.options = {
	backendUrl: '../backend/index.php',
	tuples: 300,
	refresh: false,
	defaultInterval: 1*24*60*60*1000, // 1 day
};

vz.options.plot = {
	colors: ['#83CAFF', '#7E0021', '#579D1C', '#FFD320', '#FF420E', '#004586', '#0084D1', '#C5000B', '#FF950E', '#4B1F6F', '#AECF00', '#314004'],
	series: {
		lines: { show: true },
		shadowSize: 0,
		points: {
			show: false,
			radius: 1,
			//symbol: 'square'
			symbol: function(ctx, x, y, radius, shadow) { // just draw simple pixels
				ctx.lineWidth = 1;
				ctx.strokeRect(x-1, y-1, 2, 2);
			}
		}
	},
	legend: { show: false },
	xaxis: {
		mode: 'time',
		max: new Date().getTime(), // timeinterval to request
		min: new Date().getTime() - vz.options.defaultInterval,
		timeformat: '%d.%b %h:%M',
		monthNames: ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez']
	},
	yaxis: { },
	selection: { mode: 'x' },
	crosshair: { mode: 'x' },
	grid: {
		hoverable: true,
		autoHighlight: false
	},
	zoom: {
		interactive: true,
		frameRate: null
	},
	pan: {
		interactive: false,
		frameRate: 20
	}
}

vz.options.save = function() {
	for (var key in this) {
		if (typeof this[key] == 'string' || typeof this[key] == 'number') {
			$.setCookie('vz_' + key, this[key]);
		}
	}
};

vz.options.load = function() {
	for (var key in this) {
		if (typeof this[key] == 'string' || typeof this[key] == 'number') {
			this[key] = $.getCookie('vz_' + key);
			//console.log('loaded option', key, this[key]);
		}
	}
};
