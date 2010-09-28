/**
 * Javascript functions for the frontend
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

function loadChannelList() {
	$.getJSON('../backend/index.php/entity/' + myUUID + '.json', {format: 'json'}, function(json) {
		channels = json;
	});
}

function moveWindow(mode) {
	delta = myWindowEnd - myWindowStart;
	
	if(mode == 'last')
		myWindowEnd = (new Date()).getTime();
		myWindowStart = myWindowEnd - delta;
	if(mode == 'back') {
		myWindowStart -= delta;
		myWindowEnd -= delta;
	}
	if(mode == 'forward') {
		myWindowStart += delta;
		myWindowEnd += delta;
	}
	
	getData();
}


function getData() {
	// load json data with given time window
	$.getJSON("../backend/index.php/data/" + myUUID + '.json?from='+myWindowStart+'&to='+myWindowEnd+'&resolution=500', function(json){
		data = json;
		showChart();
		$('#loading').empty();
	});
	
	return false;
}

function showChart() {
	var jqData = new Array();
	
	EformatString = '%d.%m.%y %H:%M';
	
	jqOptions = {
		series: [],
		cursor: {
			zoom: true,
			showTooltip: true,
			constrainZoomTo: 'x'
		},
		seriesDefaults: {
			lineWidth: 1,
			showMarker: false
		}
	};
	
	// legend entries
	$.each(data.data, function(index, value) {
		jqData.push(value.tuples);
	});

	jqOptions.axes = {
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
				formatString: EformatString,
				angle: -30
			},
			pad: 1,
			renderer: $.jqplot.DateAxisRenderer,
			rendererOptions: {
				tickRenderer: $.jqplot.CanvasAxisTickRenderer
			}
		}
	};
	
	$('plot').empty();
	chart = $.jqplot('plot', jqData, jqOptions);
	chart.replot({
		clear: true,
		resetAxes: true
	});
}