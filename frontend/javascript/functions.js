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

function refresh() {
	if ($('[name=refresh]').attr('checked')) {
		getData();
	}
}

function loadEntities() {
	$('#entities').empty();
	$.each(uuids, function(index, value) {
		$.getJSON(backendUrl + '/entity/' + value + '.json', function(json) {
			var entity = (json.group) ? json.group : json.channel;
			$('#entities').append('<tr><td><input type="checkbox" /></td><td>' + entity.uuid + '</td><td>' + entity.title + '</td><td>' + entity.type + '</td></tr>');
		});
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
	$.getJSON(backendUrl + '/data/' + myUUID + '.json', {from: myWindowStart, to: myWindowEnd, tuples: 500}, function(data){
		json = data;
		showChart();
	});
	
	return false;
}

function showChart() {
	var jqData = new Array();
	
	$.each(json.data, function(index, value) {
		jqData.push(value.tuples);
	});

	// TODO read docs
	chart = $.jqplot('plot', jqData, jqOptions);
	chart.replot({
		clear: true,
		resetAxes: true
	});
}