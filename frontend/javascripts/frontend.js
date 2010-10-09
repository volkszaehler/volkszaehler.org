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
vz.initInterface = function() {
	// initialize dropdown accordion
	$('#accordion h3').click(function() {
		$(this).next().toggle('fast');
		return false;
	}).next().hide();
	
	// make buttons fancy
	$('button, input[type=button]').button();
	
	$('#backendUrl').val(vz.options.backendUrl).change(function() {
		vz.options.backendUrl = $(this).val();
	});
	
	// tuple resolution
	$('#tuples').val(vz.options.tuples).change(function() {
		vz.options.tuples = $(this).val();
		vz.entities.loadData();
	});
	
	// plot rendering
	$('#rendering_lines')
		.attr('checked', vz.options.plot.series.lines.show)
		.change(function() {
			vz.options.plot.series.lines.show = $(this).attr('checked');
			vz.options.plot.series.points.show = !$(this).attr('checked');
			vz.drawPlot();
		});
	
	$('#rendering_points')
		.attr('checked', vz.options.plot.series.points.show)
		.change(function() {
			vz.options.plot.series.lines.show = !$(this).attr('checked');
			vz.options.plot.series.points.show = $(this).attr('checked');
			vz.drawPlot();
		});
	
	$('#timespan input').datepicker();
};

/**
 * Initialize dialogs
 */
vz.initDialogs = function() {
	// initialize dialogs
	$('#addUUID').dialog({
		autoOpen: false,
		title: 'UUID hinzufügen',
		width: 450,
		resizable: false
	});
	
	$('#newEntity').dialog({
		autoOpen: false,
		title: 'Entity erstellen',
		width: 400
	});
	
	// open uuid dialog
	$('button[name=addUUID]').click(function() {
		$('#addUUID').dialog('open');
	});
	
	// open entity dialog
	$('button[name=newEntity]').click(function() {
		$('#newEntity').dialog('open');
	});
	
	// add UUID
	$('#addUUID input[type=button]').click(function() {
		try {
			vz.uuids.add($('#addUUID input[type=text]').val());
			$('#addUUID input[type=text]').val('');
			$('#addUUID').dialog('close');
			vz.entities.loadDetails();
		}
		catch (e) {
			alert(e);	// TODO show error
		}
	});
};

/**
 * Bind events to handle plot zooming & panning
 */
vz.bindEvents = function() {
	// bind plot actions
	$('#move input[type=image]').click(vz.handleControls);
	
	
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
vz.refresh = function() {
	if ($('#refresh').attr('checked')) {
		var delta = vz.options.plot.xaxis.min - vz.options.plot.xaxis.max;
		vz.options.plot.xaxis.max = new Date().getTime();	// move plot
		vz.options.plot.xaxis.min = vz.options.plot.xaxis.max - delta;		// move plot
		vz.entities.loadData();
	}
};

/**
 * Move & zoom in the plotting area
 */
vz.handleControls = function () {
	var delta = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min;
	var middle = vz.options.plot.xaxis.min + delta/2;
	
	switch(this.value) {
		case 'move_last':
			vz.options.plot.xaxis.max = new Date().getTime();
			vz.options.plot.xaxis.min = new Date().getTime() - delta;
			break;
			
		case 'move_back':
			vz.options.plot.xaxis.min -= delta;
			vz.options.plot.xaxis.max -= delta;
			break;
		case 'move_forward':
			vz.options.plot.xaxis.min += delta;
			vz.options.plot.xaxis.max += delta;
			break;
		
		case 'zoom_reset':
			vz.options.plot.xaxis.min = middle - defaultInterval/2;
			vz.options.plot.xaxis.max =  middle + defaultInterval/2;
			break;
			
		case 'zoom_in':
			vz.options.plot.xaxis.min += delta/4;
			vz.options.plot.xaxis.max -= delta/4;
			break;
			
		case 'zoom_out':
			vz.options.plot.xaxis.min -= delta;
			vz.options.plot.xaxis.max += delta;
			break;
	}
	
	// update delta after zoom
	delta = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min;
	
	// we dont want to zoom/pan into the future
	if (vz.options.plot.xaxis.max + delta > new Date().getTime()) {
		vz.options.plot.xaxis.max = new Date().getTime();
		vz.options.plot.xaxis.min = new Date().getTime() - delta;
	}
	
	vz.entities.loadData();
};


/**
 * Get all entity information from backend
 */
vz.entities.loadDetails = function() {
	vz.entities.clear();
	vz.uuids.each(function(index, value) {
		vz.load('entity', value, {}, waitAsync(function(json) {
			if (json.exception) {
				alert(exception.message);
				return false;
			}
			vz.entities.push(new Entity(json.entity));
		}, vz.entities.show, 'information'));
	});
};

/**
 * Create nested entity list
 * @param data
 */
vz.entities.show = function() {
	$('#entities tbody').empty();
	
	var i = 0;
	vz.entities.each(function(entity, parent) {
		entity.color = vz.options.plot.colors[i++ % vz.options.plot.colors.length];
		entity.active = (entity.active) ? entity.active : true;
		
		var row = $('<tr>')
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
						vz.drawPlot();
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
				.addClass('ops')
				/*.append($('<input>')
					.attr('type', 'image')
					.attr('src', 'images/information.png')
					.attr('alt', 'details')
					.bind('click', entity, function(event) { event.data.showDetails(); })
				)*/
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
			
		$('#entities tbody').append(row);
	});
	
	// http://ludo.cubicphuse.nl/jquery-plugins/treeTable/doc/index.html
	$('#entities table').treeTable({
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
	for (var i = 0; i < vz.entities.length; i++) {
		vz.entities[i].each(cb);
	}
}

/**
 * Load json data from the backend
 */
vz.entities.loadData = function() {
	$('#plot').html('<div class="plotcenter"><img src="images/loading.gif" alt="loading..." /><p>Loading...</p></div>');
	vz.entities.each(function(entity, parent) {
		if (entity.active && entity.type != 'group') {
			vz.load('data', entity.uuid, { from: Math.floor(vz.options.plot.xaxis.min), to: Math.ceil(vz.options.plot.xaxis.max), tuples: vz.options.tuples }, waitAsync(function(json) {
				entity.data = json.data[0]; // TODO check uuid
			}, vz.drawPlot, 'data'));
		}
	});
};

/**
 * Draws plot to container
 */
vz.drawPlot = function () {
	var data = new Array;
	vz.entities.each(function(entity, parent) {
		if (entity.active && entity.data && entity.data.count > 0) {
			data.push({
				data: entity.data.tuples,
				color: entity.color
			});
		}
	});
	
	if (data.length > 0) {
		vz.plot = $.plot($('#plot'), data, vz.options.plot);
	}
	else {
		$('#plot').html('<div class="plotcenter"><img src="images/empty.png" alt="no data..." /><p>Nothing to plot...</p></div>');
	}
};

vz.load = function(context, identifier, data, success) {
	$.ajax({
		success: success,
		url: vz.options.backendUrl + '/' + context + '/' + identifier + '.json',
		dataType: 'json',
		data: data,
		error: function(xhr) {
			json = JSON.parse(xhr.responseText);
			alert(xhr.status + ': ' + xhr.statusText + '\n' + json.exception.message);
		}
	});
};
