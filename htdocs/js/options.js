/**
 * Frontend configuration
 *
 * @author Florian Ziegler <fz@f10-home.de>
 * @author Justin Otherguy <justin@justinotherguy.org>
 * @author Steffen Vogel <info@steffenvogel.de>
 * @copyright Copyright (c) 2011-2020, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
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
	maxPrecision: {						// override precision for certain units
		'°C': 1
	},
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
			url: 'api'
			// live: 8082					// NOTE: live updates require
														//    - push-server running and
														//    - either apache proxy forwarding configured according to
														//			https://github.com/volkszaehler/volkszaehler.org/issues/382
														// 		- or push-server live update port configured and accessible
		}, {
			title: 'Volkszaehler Demo',
			url: 'https://demo.volkszaehler.org/middleware.php'
		}
	],
	monthNames: ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
	dayNames: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
	lineWidthDefault: 2,
	lineWidthSelected: 4,
	gap: 3600, // chart gap if no tuples for specified number of seconds
	hiddenProperties: ['link', 'tolerance', 'local', 'owner', 'description', 'gap', 'active'] // hide less commonly used properties
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
		},
		bars: {
			fill:      0.8,
			lineWidth: 0,
			usedSpace: 0.8 // percent of available space that bars should occupy
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
			axisLabel: 'W', // assign el. energy to first axis- remove if not used
			tickFormatter: vz.wui.tickFormatter		// show axis label
		},
		{
			axisLabel: '°C', // assign temperature to 2nd axis- remove if not used
			tickFormatter: vz.wui.tickFormatter		// show axis label
		},
		{
			/*
			** Please note: The last axis defined in here will also be used as a 
			** template to clone further axes as needed. All settings (except the 
			** axis label) will replicate into those additional axes.
			*/
			position: 'right',
			// alignTicksWithAxis: 1,
			tickFormatter: vz.wui.tickFormatter		// show axis label
		}
	],
	selection: { mode: 'x' },
	crosshair: {
		mode: 'x',
		leaveCallback: vz.wui.plotLeave
	},
	grid: {
		hoverable: true,
		autoHighlight: true,
		borderWidth:  1,
		borderColor: '#bbb',
		margin: 0
	}
};

// minimum displayable value
vz.options.minNumber = Math.pow(10, -(vz.options.precision + 1));

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
