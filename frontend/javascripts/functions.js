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
 * Initialize the WUI (Web User Interface)
 */
function initInterface() {
	$('#accordion h3').click(function() {
		$(this).next().toggle('fast');
		return false;
	}).next().hide();
	
	$('button').button();
	
	$('button[name=addUUID]').click(function() {
		$('#addUUID').dialog({
			title: 'UUID hinzufügen',
			width: 400
		});
	});
	
	$('button[name=newEntity]').click(function() {
		$('#newEntity').dialog({
			title: 'Entity erstellen',
			width: 400
		});
	});
	
	// bind controls
	$('#move input').click(panPlot);
	
	// options
	$('input[name=trendline]').attr('checked', vz.options.plot.seriesDefaults.trendline.show).change(function() {
		vz.options.plot.seriesDefaults.trendline.show = $(this).attr('checked');
		drawPlot();
	});
	
	$('input[name=backendUrl]').val(vz.options.backendUrl).change(function() {
		vz.options.backendUrl = $(this).val();
	});
	
	$('#tuples input').val(vz.options.tuples).change(function() {
		vz.options.tuples = $(this).val();
	});
	
	$('#tuples .slider').slider({
		min: 1,
		max: 1000,
		step: 10
	});
	
	$('#refresh .slider').slider({
		min: 500,
		max: 60000,
		step: 500
	});
}

/**
 * Refresh plot with new data
 */
function refreshWindow() {
	if ($('input[name=refresh]').attr('checked')) {
		loadData();
	}
}

/**
 * Move & zoom in the plotting area
 */
function panPlot() {
	delta = vz.to - vz.from;
	
	switch(this.value) {
		case 'move_last':
			vz.to = (new Date()).getTime();
			vz.from = vz.to - delta;
			break;
			
		case 'move_back':
			vz.from -= delta;
			vz.to -= delta;
			break;
		case 'move_forward':
			vz.from += delta;
			vz.to += delta;
			break;
		
		case 'zoom_reset':
			// TODO
			break;
			
		case 'zoom_in':
			// TODO
			break;
			
		case 'zoom_out':
			// TODO
			break;
			
		case 'refresh':
			// do nothing; just loadData()
	}
	
	loadData();
}

/**
 * Load json data with given time window
 */
function loadData() {
	eachRecursive(vz.entities, function(entity, parent) {
		if (entity.active && entity.type != 'group') {
			$.getJSON(vz.options.backendUrl + '/data/' + entity.uuid + '.json', { from: vz.from, to: vz.to, tuples: vz.options.tuples }, ajaxWait(function(json) {
				entity.data = json.data[0]; // TODO filter for correct uuid
			}, drawPlot, 'data'));
		}
	});
}

function drawPlot() {
	//vz.plot.axes.xaxis.min = vz.from;
	//vz.plot.axes.xaxis.min = vz.to;
	
	var i = 0;
	
	eachRecursive(vz.entities, function(entity, parent) {
		vz.plot.series[i++].data = entity.data.tuples;
	});
	
	vz.plot.replot({
		resetAxes: true
	});
}

/*
 * Entity list related functions
 */

/**
 * Get all entity information from backend
 */
function loadEntities() {
	$.each(vz.uuids, function(index, value) {
		$.getJSON(vz.options.backendUrl + '/entity/' + value + '.json', ajaxWait(function(json) {
			vz.entities.push(json.entity);
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
	
	eachRecursive(vz.entities, function(entity, parent) {
		entity.active = true;	// TODO active by default or via backend property?
		entity.color = vz.options.plot.seriesColors[i++ % vz.options.plot.seriesColors.length];
		
		$('#entities tbody').append(
			$('<tr>')
				.addClass((parent) ? 'child-of-entity-' + parent.uuid : '')
				.attr('id', 'entity-' + entity.uuid)
				.append($('<td>')
					.css('background-color', entity.color)
					.css('width', 19)
					.append($('<input>')
						.attr('type', 'checkbox')
						.attr('checked', entity.active)
						.bind('change', entity, function(event) {
							event.data.active = $(this).attr('checked');
							loadData();
						})
					)
				)
				.append($('<td>')
					.css('width', 20)
				)
				.append($('<td>')
					.append($('<span>')
						.text(entity.title)
						.addClass('indicator')
						.addClass((entity.type == 'group') ? 'group' : 'channel')
					)
				)
				.append($('<td>').text(entity.type))
				.append($('<td>'))	// min
				.append($('<td>'))	// max
				.append($('<td>'))	// avg
				.append($('<td>')	// operations
					.css('text-align', 'right')
					.append($('<input>')
						.attr('type', 'image')
						.attr('src', 'images/information.png')
						.attr('alt', 'details')
						.bind('click', entity, function(event) { showEntityDetails(event.data); })
					)
					.append($('<input>')
						.attr('type', 'image')
						.attr('src', 'images/delete.png')
						.attr('alt', 'delete')
						.bind('click', entity, function(event) { removeUUID(event.data.uuid); })
					)
				)
		);
	});
	
	// http://ludo.cubicphuse.nl/jquery-plugins/treeTable/doc/index.html
	$('#entities table').treeTable({
		treeColumn: 2,
		clickableNodeNames: true
	});
	
	// load data and show plot
	loadData();
}

/**
 * Show and edit entity details
 * @param entity
 */
function showEntityDetails(entity) {
	var properties = $('<table>');
	
	$.each(entity, function(key, value) {
		properties.append($('<tr>')
			.append($('<td>')
				.addClass('key')
				.text(key)
			)
			.append($('<td>')
				.addClass('value')
				.text(value)
			)
		);
	});
	
	$('<div>')
	.addClass('details')
	.append(properties)
	.dialog({
		title: 'Entity Details',
		width: 450
	});
}

/*
 * Cookie & UUID related functions
 */
function getUUIDs() {
	if ($.getCookie('uuids')) {
		return JSON.parse($.getCookie('uuids'));
	}
	else {
		return new Array();
	}
}

function addUUID(uuid) {
	if (!vz.uuids.contains(uuid)) {
		vz.uuids.push(uuid);
		$.setCookie('uuids', JSON.stringify(vz.uuids));
	}
}

function removeUUID(uuid) {
	if (uuids.contains(uuid)) {
		uuids.filter(function(value) {
			return value != uuid;
		});
		$.setCookie('uuids', JSON.stringify(uuids));
	}
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

/**
 * Checks if value of part of the array
 * 
 * @param needle the value to search for
 * @return boolean
 */
Array.prototype.contains = function(needle) {
	return this.key(needle) ? true : false;
};

/**
 * Calculates the diffrence between this and another Array
 * 
 * @param compare the Array to compare with
 * @return array
 */
Array.prototype.diff = function(compare) {
	return this.filter(function(elem) {
		return !compare.contains(elem);
	});
};

/**
 * Find the key to an given value
 * 
 * @param needle the value to search for
 * @return integer
 */
Array.prototype.key = function(needle) {
	for (var i=0; i<this.length; i++) {
		if (this[i] == needle) {
			return i;
		}
	}
};

/**
 * Remove a value from the array
 */
Array.prototype.remove = function(needle) {
	var key = this.key(needle);
	if (key) {
		this.splice(key, 1);
	}
};
