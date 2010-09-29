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


/*
 * Frontend related functions
 */

/**
 * Refresh plot with new data
 */
function refresh() {
	if ($('[name=refresh]').attr('checked')) {
		getData();
	}
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
	$.getJSON(backendUrl + '/data/' + myUUID + '.json', { from: myWindowStart, to: myWindowEnd, tuples: 500 }, function(data){
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

/*
 * Entity list related functions
 */

/**
 * Get all entity infomration from backend
 */
function loadEntities() {
	$.each(uuids, function(index, value) {
		$.getJSON(backendUrl + '/entity/' + value + '.json', ajaxWait(showEntities, 'enities'));
	});
}

/**
 * Create nested entity list
 * @param data
 */
function showEntities(data) {
	$('#entities tbody').empty();
	
	$.each(data, function(index, value) {
		var entity = (value.group) ? value.group : value.channel;
		
		showEntity(entity);
	});
	
	$('#entities').treeTable();
}

/**
 * Create nested entity list (recursive)
 * @param entity
 * @param parent
 */
function showEntity(entity, parent) {
	$('#entities tbody').append(
		$('<tr>')
			.attr('class', (parent) ? 'child-of-entity-' + parent.uuid : '')
			.attr('id', 'entity-' + entity.uuid)
		.append($('<td>').text(entity.uuid))
		.append($('<td>').text(entity.title))
		.append($('<td>').text(entity.type))
	);

	var entities = new Array();
	if (entity.channels) {
		$.merge(entities, entity.channels);
	}
	if (entity.groups) {
		$.merge(entities, entity.groups);
	}
	
	$.each(entities, function(index, value) {
		showEntity(value, entity);
	});
}

/*
 * General helper functions
 */

function ajaxWait(callback, identifier) {
	if (!identifier) {
		var identifier = 0;
	}
	
	if (!ajaxWait.counter || !ajaxWait.data) {
		ajaxWait.counter = new Array();
		ajaxWait.data = new Array();
	}
	
	if (!ajaxWait.counter[identifier] || !ajaxWait.data[identifier]) {
		ajaxWait.counter[identifier] = 0;
		ajaxWait.data[identifier] = new Array;
	}
	
	ajaxWait.counter[identifier]++;
	
	return function (data, textStatus) {
		ajaxWait.data[identifier].push(data);
		
		if (!--ajaxWait.counter[identifier]) {
			callback(ajaxWait.data[identifier]);
		}
	};
}