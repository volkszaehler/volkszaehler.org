/**
 * Frontend configuration
 *
 * @author Florian Ziegler <fz@f10-home.de>
 * @author Justin Otherguy <justin@justinotherguy.org>
 * @author Steffen Vogel <info@steffenvogel.de>
 * @copyright Copyright (c) 2011, The volkszaehler.org project
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

vz.options = {
	language: 'de',
	precision: 2,							// TODO update from middleware capabilities?
	tuples: null,							// automatically determined by plot size
	refresh: true,						// update chart if zoomed to current timestamp
	interval: 24*60*60*1000,	// 1 day default time interval to show
	totalsInterval: 300,			// update interval for total consumption in s (only channels where initialconsumption > 0)
	pushRedrawTimeout: 1000,	// ms delay for collecting push updates before redrawing
	minTimeout: 2000,					// minimum refresh time in ms
	shortenLongTypes: false,	// show shorter type names in table
	middleware: [
		{
			title: 'Local (default)',
			url: '../middleware.php',
			live: 8082
		}, {
			title: 'Volkszaehler Demo',
			url: '//demo.volkszaehler.org/middleware.php',
			live: 8082
		}
	],
	monthNames: ['Jan', 'Feb', unescape('M%E4r'), 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
	dayNames: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
	lineWidthDefault: 2,
	lineWidthSelected: 4,
	speedupFactor: 2,   // higher values give higher speedup but can produce chunky display
	hiddenProperties: ['link', 'tolerance', 'local', 'active'] // hide less commonly used properties
};

/**
 * Plot options are passed on to flot
 */
vz.options.plot = {
	colors: ['#579D1C', '#7E0021', '#FFD320', '#FF420E', '#004586', '#0084D1', '#C5000B', '#FF950E', '#4B1F6F', '#AECF00', '#314004', '#83CAFF'],
	series: {
		shadowSize: 0,
		points: {
			radius: 3
		}
	},
 	legend: {
		show: true,
		position: 'nw',
		backgroundOpacity: 0.80,
	},
	xaxis: {
		mode: 'time',
		timezone: 'browser'
	},
	axisLabels: {
		show: false // set to true to show labels
	},
	yaxes: [
		{
			axisLabel: 'W' // assign el. energy to first axis- remove if not used
		},
		{
			// alignTicksWithAxis: 1,
			position: 'right'
		}
	],
	selection: { mode: 'x' },
	crosshair: { mode: 'x' },
	grid: {
		hoverable: true,
		autoHighlight: false
	}
};

vz.options.saveCookies = function() {
	var expires = new Date(2038, 0, 1); // some days before y2k38 problem

	for (var key in vz.options) {
		if (vz.options.hasOwnProperty(key) &&
			typeof vz.options[key] != 'function' &&
			typeof vz.options[key] != 'object' &&
			typeof vz.options[key] != 'undefined'
		) {
			$.setCookie('vz_' + key, vz.options[key], {expires: expires});
		}
	}
};

vz.options.loadCookies = function() {
	for (var key in this) {
		var value = $.getCookie('vz_' + key);
		if (value !== undefined) {
			switch(typeof this[key]) {
				case 'string':
					this[key] = value;
					break;
				case 'number':
					this[key] = Number(value);
					break;
				case 'boolean':
				 	this[key] = (value == 'true');
					break;
			}
		}
	}
};
