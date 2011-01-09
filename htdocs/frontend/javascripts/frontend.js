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

/**
 * Initialize the WUI (Web User Interface)
 */
vz.wui.init = function() {
	// initialize dropdown accordion
	$('#accordion h3').click(function() {
		$(this).next().toggle('fast');
		return false;
	}).next().hide();
	
	// buttons
	$('button, input[type=button],[type=image]').button();
	$('button[name=options-save]').click(function() { vz.options.save(); });
	$('#permalink').click(function() { // TODO add uuids
		var u = window.location.protocol + '//' +
			window.location.host +
			window.location.pathname +
			'?from=' + vz.options.plot.xaxis.min +
			'&to=' + vz.options.plot.xaxis.max;

		window.location = u;
	});
	$('button[name=entity-add]').click(function() { $('#entity-add').dialog('open'); });
	$('#entity-subscribe input[type=button]').click(function() {
		try {
			vz.uuids.add($('#entity-subscribe input[type=text]').val());
			$('#entity-subscribe input[type=text]').val('');
			$('#entity-add').dialog('close');
			vz.entities.loadDetails();
		}
		catch (exception) {
			vz.wui.dialogs.exception(exception);
		}
	});
	
	// bind plot actions
	$('#controls button').click(this.handleControls);
	$('#controls').buttonset();
	
	// tuple resolution
	vz.options.tuples = Math.round($('#flot').width() / 4);
	$('#tuples').val(vz.options.tuples).change(function() {
		vz.options.tuples = $(this).val();
		vz.entities.loadData();
	});

	// backend address
	$('#backend-url')
		.val(vz.options.backendUrl)
		.change(function() {
			vz.options.backendUrl = $(this).val();
		});

	// auto refresh
	if (vz.options.refresh) {
		$('#refresh').attr('checked', true);
		vz.wui.interval = window.setInterval(vz.wui.refresh, vz.options.refreshInterval);
	}
	$('#refresh').change(function() {
		if ($(this).attr('checked')) {
			vz.options.refresh = true;
			vz.wui.interval = window.setInterval(vz.wui.refresh, vz.options.refreshInterval);
		}
		else {
			vz.options.refresh = false;
			window.clearInterval(vz.wui.interval);
		}
	});
	
	// plot rendering
	$('#render-lines').attr('checked', (vz.options.render == 'lines'));
	$('#render-points').attr('checked', (vz.options.render == 'points'));
	$('input[name=render][type=radio]').change(function() {
		if ($(this).attr('checked')) {
			vz.options.render = $(this).val();
			vz.drawPlot();
		}
	});
};

/**
 * Initialize dialogs
 */
vz.wui.dialogs.init = function() {
	// initialize dialogs
	$('#entity-add.dialog').dialog({
		autoOpen: false,
		title: 'Kanal hinzuf&uuml;gen',
		width: 530,
		resizable: false
	});
	$('#entity-add.dialog > div').tabs();
};

/**
 * Bind events to handle plot zooming & panning
 */
vz.wui.initEvents = function() {
	$('#plot')
		.bind("plotselected", function (event, ranges) {
			vz.options.plot.xaxis.min = ranges.xaxis.from;
			vz.options.plot.xaxis.max = ranges.xaxis.to;
			vz.options.plot.yaxis.min = 0;
			vz.options.plot.yaxis.max = null;	// autoscaling
			vz.entities.loadData();
		})
		/*.bind('plotpan', function (event, plot) {
			var axes = plot.getAxes();
			vz.options.plot.xaxis.min = axes.xaxis.min;
			vz.options.plot.xaxis.max = axes.xaxis.max;
			vz.options.plot.yaxis.min = axes.yaxis.min;
			vz.options.plot.yaxis.max = axes.yaxis.max;
		})*/
		/*.bind('mouseup', function(event) {
			vz.entities.loadData();
		})*/
		.bind('plotzoom', function (event, plot) {
			var axes = plot.getAxes();
			vz.options.plot.xaxis.min = axes.xaxis.min;
			vz.options.plot.xaxis.max = axes.xaxis.max;
			vz.options.plot.yaxis.min = axes.yaxis.min;
			vz.options.plot.yaxis.max = axes.yaxis.max;
			vz.entities.loadData();
		});
};

/**
 * Refresh plot with new data
 */
vz.wui.refresh = function() {
	var delta = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min;
	vz.options.plot.xaxis.max = new Date().getTime();		// move plot
	vz.options.plot.xaxis.min = vz.options.plot.xaxis.max - delta;	// move plot
	vz.entities.loadData();
};

/**
 * Move & zoom in the plotting area
 */
vz.wui.handleControls = function () {
	var delta = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min;
	var middle = vz.options.plot.xaxis.min + delta/2;

	switch($(this).val()) {
		case 'move-last':
			vz.options.plot.xaxis.max = new Date().getTime();
			vz.options.plot.xaxis.min = new Date().getTime() - delta;
			break;
		case 'move-back':
			vz.options.plot.xaxis.min -= delta;
			vz.options.plot.xaxis.max -= delta;
			break;
		case 'move-forward':
			vz.options.plot.xaxis.min += delta;
			vz.options.plot.xaxis.max += delta;
			break;
		case 'zoom-reset':
			vz.options.plot.xaxis.min = middle - vz.options.defaultInterval/2;
			vz.options.plot.xaxis.max =  middle + vz.options.defaultInterval/2;
			break;
		case 'zoom-in':
			vz.options.plot.xaxis.min += delta/4;
			vz.options.plot.xaxis.max -= delta/4;
			break;
		case 'zoom-out':
			vz.options.plot.xaxis.min -= delta;
			vz.options.plot.xaxis.max += delta;
			break;
		case 'zoom-hour':
			hour = 60*60*1000;
			vz.options.plot.xaxis.min = middle - hour/2;
			vz.options.plot.xaxis.max =  middle + hour/2;
			break;
		case 'zoom-day':
			var day = 24*60*60*1000;
			vz.options.plot.xaxis.min = middle - day/2;
			vz.options.plot.xaxis.max =  middle + day/2;
			break;
		case 'zoom-week':
			var week = 7*24*60*60*1000;
			vz.options.plot.xaxis.min = middle - week/2;
			vz.options.plot.xaxis.max =  middle + week/2;
			break;
		case 'zoom-month':
			var month = 30*24*60*60*1000;
			vz.options.plot.xaxis.min = middle - month/2;
			vz.options.plot.xaxis.max =  middle + month/2;
			break;
		case 'zoom-year':
			var year = 365*24*60*60*1000;
			vz.options.plot.xaxis.min = middle - year/2;
			vz.options.plot.xaxis.max =  middle + year/2;
			break;
	}

	// reenable autoscaling for yaxis
	vz.options.plot.yaxis.min = 0;
	vz.options.plot.yaxis.max = null;
	
	// we dont want to zoom/pan into the future
	if (vz.options.plot.xaxis.max > new Date().getTime()) {
		delta = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min;
		vz.options.plot.xaxis.max = new Date().getTime();
		vz.options.plot.xaxis.min = new Date().getTime() - delta;
	}
	
	vz.entities.loadData();
};


/**
 * Get all entity information from backend
 */
vz.entities.loadDetails = function() {
	this.clear();
	$('#entity-list tbody').empty();

	vz.uuids.each(function(index, value) {
		vz.load('entity', value, {}, waitAsync(function(json) {
			vz.entities.push(new Entity(json.entity));
		}, vz.entities.show, 'information'));
	});
};

/**
 * Create nested entity list
 */
vz.entities.show = function() {
	var i = 0;

	vz.entities.each(function(entity, parent) {
		entity.color = vz.options.plot.colors[i++ % vz.options.plot.colors.length];
		entity.active = (entity.active) ? entity.active : true;

		var row = $('<tr>')
			.addClass((parent) ? 'child-of-entity-' + parent.uuid : '')
			.attr('id', 'entity-' + entity.uuid)
			.append($('<td>')
				.addClass('visibility')
				.css('background-color', entity.color)
				.append($('<input>')
					.attr('type', 'checkbox')
					.attr('checked', entity.active)
					.bind('change', entity, function(event) {
						var state = $(this).attr('checked');
						event.data.active = state;

						if (entity.type == 'group') {
							entity.children.each(function(entity) {
								$('#entity-' + entity.uuid + ' input[type=checkbox]').attr('checked', state);
								entity.active = state;
							});
						}

						vz.drawPlot();
					})
				)
			)
			.append($('<td>').addClass('expander'))
			.append($('<td>')
				.append($('<span>')
					.text(entity.title)
					.addClass('indicator')
					.addClass((entity.type == 'group') ? 'group' : 'channel')
				)
			)
			.append($('<td>').text(entity.type))	// channel type
			.append($('<td>').addClass('min'))	// min
			.append($('<td>').addClass('max'))	// max
			.append($('<td>').addClass('average'))	// avg
			.append($('<td>').addClass('last'))	// last
			.append($('<td>')			// operations
				.addClass('ops')
				.append($('<input>')
					.attr('type', 'image')
					.attr('src', 'images/information.png')
					.attr('alt', 'details')
					.bind('click', entity, function(event) { event.data.showDetails(); })
				)
			);
				
		if (parent == null) {
			$('td.ops', row).prepend($('<input>')
				.attr('type', 'image')
				.attr('src', 'images/delete.png')
				.attr('alt', 'delete')
				.bind('click', entity, function(event) {
					vz.uuids.remove(event.data.uuid);
					vz.entities.loadDetails();
				})
			);
		}
			
		$('#entity-list tbody').append(row);
	});
	
	// http://ludo.cubicphuse.nl/jquery-plugins/treeTable/doc/index.html
	$('#entity-list table').treeTable({
		treeColumn: 2,
		clickableNodeNames: true
	});

	// load data and show plot
	vz.entities.loadData();
};

/**
 * Overwritten each iterator for entity array
 */
vz.entities.each = function(cb) {
	for (var i = 0; i < this.length; i++) {
		this[i].each(cb);
	}
}

/**
 * Load json data from the backend
 */
vz.entities.loadData = function() {
	$('#overlay').html('<img src="images/loading.gif" alt="loading..." /><p>loading...</p>');
	this.each(function(entity, parent) {
		if (entity.active && entity.type != 'group') { // TODO add group data aggregation
			vz.load('data', entity.uuid,
				{
					from: Math.floor(vz.options.plot.xaxis.min),  // TODO fetch enough data to fill the holes on the beginning and the end of the plot
					to: Math.ceil(vz.options.plot.xaxis.max),
					tuples: vz.options.tuples
				},
				waitAsync(function(json) {
					entity.data = json.data;
					
					// update entity table
					// TODO add units
					if (entity.data.min) {
						$('#entity-' + entity.uuid + ' .min')
							.text(entity.data.min.value)
							.attr('title', $.plot.formatDate(new Date(entity.data.min.timestamp), '%d. %b %h:%M:%S', vz.options.plot.xaxis.monthNames));	
					}
					if (entity.data.max) {
						$('#entity-' + entity.uuid + ' .max')
							.text(entity.data.max.value)
							.attr('title', $.plot.formatDate(new Date(entity.data.max.timestamp), '%d. %b %h:%M:%S', vz.options.plot.xaxis.monthNames));	
					}
					if (entity.data.average) {
						$('#entity-' + entity.uuid + ' .average').text(Math.round(entity.data.average/vz.options.rounding)*vz.options.rounding);
						// rounding: Math.round rounds to whole numbers; to round to one decimal (e.g. 15.2) we multiply by 10 (resp. divide through 0.1), round and reverse the multiplication again; therefore "vz.options.rounding" needs to be set to 0.1 in that case
					}
					if (entity.data.last) {
						$('#entity-' + entity.uuid + ' .last').text(Math.round(entity.data.last/vz.options.rounding)*vz.options.rounding);
					}
				}, vz.drawPlot, 'data')
			);
		}
	});
};

vz.wui.updateHeadline = function() {
	var from = $.plot.formatDate(new Date(vz.options.plot.xaxis.min + vz.options.timezoneOffset), '%d. %b %h:%M:%S', vz.options.plot.xaxis.monthNames);
	var to = $.plot.formatDate(new Date(vz.options.plot.xaxis.max + vz.options.timezoneOffset), '%d. %b %h:%M:%S', vz.options.plot.xaxis.monthNames);
	$('#title').text(from + ' - ' + to);
}

/**
 * Draws plot to container
 */
vz.drawPlot = function () {
	vz.wui.updateHeadline();
	
	var data = new Array;
	vz.entities.each(function(entity, parent) {
		if (entity.active && entity.data && entity.data.count > 0) {
			data.push({
				data: entity.data.tuples,
				color: entity.color
			});
		}
	});
	
	if (data.length == 0) {
		$('#overlay').html('<img src="images/empty.png" alt="no data..." /><p>nothing to plot...</p>');
		data.push({});  // add empty dataset to show axes
	}
	else {
		$('#overlay').empty();
	}

	vz.options.plot.series.lines.show = (vz.options.render == 'lines');
	vz.options.plot.series.points.show = (vz.options.render == 'points');

	vz.plot = $.plot($('#flot'), data, vz.options.plot);
};

/**
 * Universal helper for backend ajax requests with error handling
 */
vz.load = function(context, identifier, data, success) {
	$.ajax({
		success: success,
		url: this.options.backendUrl + '/' + context + '/' + identifier + '.json',
		dataType: 'json',
		data: data,
		error: function(xhr) {
			json = JSON.parse(xhr.responseText);
			vz.wui.dialogs.error(xhr.statusText, json.exception.message, xhr.status); // TODO or throw exception?
		}
	});
};

/**
 * Parse URL GET parameters
 */
vz.parseUrlVars = function() {
	var vars = $.getUrlVars();
	for (var key in vars) {
		if (vars.hasOwnProperty(key)) {
			switch (key) {
				case 'uuid': // add optional uuid from url
					try {
						vz.uuids.add(vars[key]);
					} catch (exception) {
						vz.wui.dialogs.exception(exception);
					}
					break;
				case 'from':
					vz.options.plot.xaxis.min = parseInt(vars[key]);
					break;
				case 'to':
					vz.options.plot.xaxis.max = parseInt(vars[key]);
					break;
				case 'debug':
					$.getScript('javascripts/firebug-lite.js');
					break;
			}
		}
	}
};

/**
 * Load definitions from backend
 */
vz.definitions.load = function() {
	$.ajax({
		cache: true,
		dataType: 'json',
		url: vz.options.backendUrl + '/capabilities/definition/entity.json',
		success: function(json) {
			vz.definitions.entity = json.definition.entity
		}
	});

	$.ajax({
		cache: true,
		dataType: 'json',
		url: vz.options.backendUrl + '/capabilities/definition/property.json',
		success: function(json) {
			vz.definitions.property = json.definition.property
		}
	});
};

vz.definitions.get = function(section, iname) {
	for (var i in vz.definitions[section]) {
		alert(vz.definitions[section][i].name);
		if (vz.definitions[section][i].name == iname) {
			return definition;
		}
	}
}

/*
 * Error & Exception handling
 */

vz.wui.dialogs.error = function(error, description, code) {
	if (typeof code != undefined) {
		error = code + ': ' + error;
	}

	$('<div>')
	.append($('<span>').text(description))
	.dialog({
		title: error,
		width: 450,
		dialogClass: 'ui-error',
		resizable: false,
		modal: true,
		buttons: {
			Ok: function() {
				$( this ).dialog( "close" );
			}
		}
	});
};

vz.wui.dialogs.exception = function(exception) {
	this.error(exception.type, exception.message, exception.code);
};
