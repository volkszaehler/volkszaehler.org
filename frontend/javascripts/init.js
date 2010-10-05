/**
 * Initialization and configuration of frontend
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
 * volkzaehler.org is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * volkzaehler.org is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
 */

// default time interval to show
const defaultInterval = 7*24*60*60*1000; // 1 week

// volkszaehler.org object
// holds all data, options and functions for the frontend
// acts like a namespace (we dont want to pollute the global one)
var vz = {
	// entity information & properties
	entities: new Array,
	
	// known UUIDs in the browser
	uuids: new Array,
	
	// plot including data
	plot: {
		// data for plot
		data: new Array,
		// container for flot instance
		flot: { }
	},
	
	// definitions of entities & properties
	// for validation, translation etc..
	definitions: {
		properties: {},
		entities: {}
	},

	// timeinterval to request
	to: new Date().getTime(),
	from: new Date().getTime() - defaultInterval,
		
	options: {
		backendUrl: '../backend/index.php',
		tuples: 300,
		plot: {
			colors: ['#83CAFF', '#7E0021', '#579D1C', '#FFD320', '#FF420E', '#004586', '#0084D1', '#C5000B', '#FF950E', '#4B1F6F', '#AECF00', '#314004'],
			series: {
				lines: { show: false },
				points: {
					show: true,
					radius: 1,
					//symbol: 'square'
					symbol: function(ctx, x, y, radius, shadow) {
						ctx.rect(x, y, radius, radius);
					}
				}
			},
			legend: { show: false },
			xaxis: {
				mode: 'time',
				timeformat: '%d.%b %h:%M',
				monthNames: ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez']
			},
			yaxis: { },
			selection: { mode: 'x' },
			//crosshair: { mode: 'x' },
			grid: { hoverable: true, autoHighlight: false },
			zoom: {
				interactive: true,
				frameRate: null
			},
			pan: {
				interactive: false,
				frameRate: 20
			}
		}
	}
};

// executed on document loaded complete
// this is where it all starts...
$(document).ready(function() {
	// initialize user interface
	vz.initInterface();
	
	// parse uuids from cookie
	vz.uuids.parseCookie();
	
	// add optional uuid from url
	if($.getUrlVar('uuid')) {
		vz.uuids.add($.getUrlVar('uuid'));
	}
	
	if (vz.uuids.length == 0) {
		$('#addUUID').dialog({
			title: 'UUID hinzufügen',
			width: 400
		});
	}
	
	// start auto refresh timer
	window.setInterval(vz.refresh, 5000);
	
	vz.bindEvents();
	
	vz.entities.load();
});
