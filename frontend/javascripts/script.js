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
 * Constants
 */
const backendUrl = '../backend/index.php';
const jqOptions = {
	title: 'volkszaehler.org',
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
			size: 2
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

// uuids
var myUUID = '';
var uuids = new Array;

if ($.getCookie('uuids')) {
	var uuids = $.parseJSON($.getCookie('uuids'));
}

if($.getUrlVar('uuid')) {
	myUUID = $.getUrlVar('uuid');
	uuids.push($.getUrlVar('uuid'));
}

if (uuids.length == 0) {
	alert('Error: No UUIDs given!')
}

// storing json data
var json;

// windowEnd parameter for json server
var myWindowEnd = new Date().getTime();

// windowStart parameter for json server
var myWindowStart = myWindowEnd - 24*60*60*1000;

// executed on document loaded complete
// this is where it all starts...
$(document).ready(function() {
	// resize chart area for low resolution displays
	// works fine with HTC hero
	// perhaps you have to reload after display rotation
	if($(window).width() < 800) {
		$('#chart').animate({
			width: $(window).width() - 40,
			height: $(window).height() - 3,
		}, 0);
	}
	
	// load all entity information
	loadEntities();
	
	// start auto refresh timer
	window.setInterval(refresh, 5000);
	
	// initialization of user interface
	$('#accordion h3').click(function() {
		$(this).next().toggle('fast');
		return false;
	}).next().hide();
	
	$('#refreshInterval').slider();
	
	// load data and show plot
	getData();
});
