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
function refreshWindow() {
	if ($('input[name=refresh]').attr('checked')) {
		loadData();
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
	
	loadData();
}

//load json data with given time window
function loadData() {
	eachRecursive(entities, function(entity, parent) {
		if (entity.active && entity.type != 'group') {
			$.getJSON(backendUrl + '/data/' + entity.uuid + '.json', { from: myWindowStart, to: myWindowEnd, tuples: tuples }, ajaxWait(function(json) {
				entity.data = json.data[0]; // TODO filter for correct uuid
			}, showChart, 'data'));
		}
	});
}

function showChart() {
	var jqData = new Array();
	
	eachRecursive(entities, function(entity, parent) {
		jqData.push(entity.data.tuples);
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
 * Get all entity information from backend
 */
function loadEntities(uuids) {
	$.each(uuids, function(index, value) {
		$.getJSON(backendUrl + '/entity/' + value + '.json', ajaxWait(function(json) {
			entities.push(json.entity);
		}, showEntities, 'information'));
	});
}

/**
 * Create nested entity list
 * @param data
 */
function showEntities() {
	$('#entities tbody').empty();
	
	var i = 0;
	
	eachRecursive(entities, function(entity, parent) {
		entity.active = true;	// TODO active by default or via backend property?
		entity.color = colors[i++%colors.length];
		
		$('#entities tbody').append(
			$('<tr>')
				.addClass((parent) ? 'child-of-entity-' + parent.uuid : '')
				.attr('id', 'entity-' + entity.uuid)
			.append(
				$('<td>').append(
					$('<span>')
						.addClass((entity.type == 'group') ? 'group' : 'channel')
						.attr('title', entity.uuid)
						.text(entity.title)
				)
			)
			.append($('<td>').text(entity.type))
			.append($('<td>')	// operations
				.append($('<input>')
					.attr('type', 'image')
					.attr('src', 'images/delete.png')
					.attr('alt', 'delete')
					.bind('click', entity, function(event) { alert('delete: ' + event.data.uuid); })
				)
			)
			.append($('<td>')
				.append($('<div>')
					.css('background-color', entity.color)
					.addClass('indicator')
					.append($('<input>')
						.attr('type', 'checkbox')
						.attr('checked', entity.active)
						.bind('change', entity, function(event) {
							event.data.active = $(this).attr('checked');
							loadData();
						})
					)
				)
			)
		);
	});
	
	// http://ludo.cubicphuse.nl/jquery-plugins/treeTable/doc/index.html
	$('#entities table').treeTable();
	
	// load data and show plot
	loadData();
}

/*
 * General helper functions
 */

function ajaxWait(callback, finished, identifier) {
	if (!ajaxWait.counter) { ajaxWait.counter = new Array(); }
	if (!ajaxWait.counter[identifier]) { ajaxWait.counter[identifier] = 0; }
	
	ajaxWait.counter[identifier]++;
	
	return function (data, textStatus) {
		callback(data, textStatus);
		
		if (!--ajaxWait.counter[identifier]) {
			finished();
		}
	};
}

function eachRecursive(array, callback, parent) {
	$.each(array, function(index, value) {
		callback(value, parent);
		
		if (value.children) {	// has children?
			eachRecursive(value.children, callback, value);	// call recursive
		}
	});
}

Array.prototype.contains = function(needle) {
	for (var i=0; i<this.length; i++) {
		if (this[i] == needle) {
			return true;
		}
	}

	return false;
};

Array.prototype.diff = function(compare) {
	return this.filter(function(elem) {
		return !compare.contains(elem);
	});
};
