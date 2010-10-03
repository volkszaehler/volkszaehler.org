/**
 * Main javascript file
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

// volkszaehler.org object
// holds all data and options for the frontend
var vz = {
	// storing entities
	entities: new Array,
	uuids: new Array,

	// parameter for json server
	to: new Date().getTime(),

	//parameter for json server (last 24 hours)
	from: new Date().getTime() - 24*60*60*1000,
		
	options: {
		backendUrl: '../backend/index.php',
		tuples: 300,
		plot: {
			series: [],
			seriesColors: ['#83CAFF', '#7E0021', '#579D1C', '#FFD320', '#FF420E', '#004586', '#0084D1', '#C5000B', '#FF950E', '#4B1F6F', '#AECF00', '#314004'],
			cursor: {
				zoom: true,
				showTooltip: true,
				constrainZoomTo: 'x',
				showVerticalLine: true
			},
			seriesDefaults: {
				lineWidth: 1,
				showMarker: true,
				showLine: false,
				markerOptions: {
					style: 'dash',
					shadow: false,
					size: 2
				},
				trendline: {
					show: true,
					shadow: false,
					color: 'red'
				}
			},
			axes: {
				yaxis: {
					autoscale: true,
					label: 'Leistung (Watt)',
					tickOptions: {
						formatString: '%.3f'
					},
					labelRenderer: $.jqplot.CanvasAxisLabelRenderer
				},
				xaxis: {
					autoscale: true,
					tickOptions: {
						formatString: '%d.%m.%y %H:%M',
						angle: -35
					},
					pad: 1,
					renderer: $.jqplot.DateAxisRenderer,
					rendererOptions: {
						tickRenderer: $.jqplot.CanvasAxisTickRenderer
					}
				}
			}
		}
	}
};

// executed on document loaded complete
// this is where it all starts...
$(document).ready(function() {
	// parse uuids from cookie
	vz.uuids = getUUIDs();

	// add optional uuid from url
	if($.getUrlVar('uuid')) {
		addUUID($.getUrlVar('uuid'));
	}
	
	// start auto refresh timer
	window.setInterval(refreshWindow, 5000);
	
	// initialize plot
	vz.plot = $.jqplot('plot', [[]], vz.options.plot);
	
	// zoom events
	vz.plot.target.bind('jqplotZoom', function(event, gridpos, datapos, plot, cursor) {
		//alert('zoomed'); // TODO refresh of data
	});
	
	vz.plot.target.bind('jqplotResetZoom', function(event, plot, cursor) {
		alert('zoom reset'); // TODO refresh of data
	});

	loadEntities();
});
