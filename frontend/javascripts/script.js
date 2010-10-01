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

/*
 * Constants & settings
 */
var backendUrl = '../backend/index.php';
var tuples = 300;
var colors = ['#83CAFF', '#7E0021', '#579D1C', '#FFD320', '#FF420E', '#004586', '#0084D1', '#C5000B', '#FF950E', '#4B1F6F', '#AECF00', '#314004'];
var jqOptions = {
	series: [],
	cursor: {
		zoom: true,
		showTooltip: true,
		constrainZoomTo: 'x'
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
			shadow: false
		}
	},
	axes: {
		yaxis: {
			autoscale: true,
			min: 0,
			label: 'Leistung (Watt)',
			tickOptions: {
				formatString: '%.3f'
			},
			labelRenderer: $.jqplot.CanvasAxisLabelRenderer
		},
		xaxis: {
			autoscale: true,
			min: myWindowStart,
			max: myWindowEnd,
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
};

// storing entities
var entities = new Array;
var uuids = new Array;

// windowEnd parameter for json server
var myWindowEnd = new Date().getTime();

// windowStart parameter for json server (last 24 hours)
var myWindowStart = myWindowEnd - 24*60*60*1000;

// executed on document loaded complete
// this is where it all starts...
$(document).ready(function() {
	// parse uuids from cookie
	uuids = getUUIDs();

	// add optional uuid from url
	if($.getUrlVar('uuid')) {
		addUUID($.getUrlVar('uuid'));
	}
	console.log('cookie uuids', uuids);
	
	// start auto refresh timer
	window.setInterval(refreshWindow, 5000);
	
	$('#accordion h3').click(function() {
		$(this).next().toggle('fast');
		return false;
	}).next().hide();
	
	// add new entity to list
	$('#addEntity button').click(function() {
		uuids.push($(this).prev().val());
		loadEntities(uuids);
	});
	
	// options
	$('input[name=trendline]').change(function() {
		jqOptions.seriesDefaults.trendline.show = $(this).attr('checked');
	});
	
	$('input[name=backendUrl]').val(backendUrl);
	$('input[name=tuples]').val(tuples);
	
	// load all entity information
	loadEntities(uuids);
});
