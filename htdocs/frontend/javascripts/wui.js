/**
 * Javascript functions for the frontend
 * 
 * @author Florian Ziegler <fz@f10-home.de>
 * @author Justin Otherguy <justin@justinotherguy.org>
 * @author Steffen Vogel <info@steffenvogel.de>
 * @copyright Copyright (c) 2011, The volkszaehler.org project
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
	$('#entity-list').show(); // open entity list by default
	
	// buttons
	$('button, input[type=button],[type=image],[type=submit]').button();
	$('button[name=options-save]').click(vz.options.save);
	$('button[name=entity-add]').click(this.dialogs.init);
	$('#permalink').click(function() { window.location = vz.wui.getPermalink(); });
	$('#snapshot').click(function() { window.location = vz.wui.getSnaplink(); }).hide();
	
	// bind plot actions
	$('#controls button').click(this.handleControls);
	$('#controls').buttonset();
	
	// tuple resolution
	vz.options.tuples = Math.round($('#flot').width() / 4);
	$('#tuples').val(vz.options.tuples).change(function() {
		vz.options.tuples = $(this).val();
		vz.entities.loadData().done(vz.wui.drawPlot);
	});

	// middleware address
	$('#middleware-url')
		.val(vz.options.middlewareUrl)
		.change(function() {
			vz.options.middlewareUrl = $(this).val();
		});

	// auto refresh
	if (vz.options.refresh) {
		$('#refresh').attr('checked', true);
		vz.wui.setTimeout();
	}
	$('#refresh').change(function() {
		if (vz.options.refresh = $(this).attr('checked')) {
			vz.wui.refresh(); // refresh once
			vz.wui.setTimeout();
		} else {
			vz.wui.clearTimeout();
		}
	});
	
	// plot rendering
	$('#render-lines').attr('checked', (vz.options.render == 'lines'));
	$('#render-points').attr('checked', (vz.options.render == 'points'));
	$('input[name=render][type=radio]').change(function() {
		if ($(this).attr('checked')) {
			vz.options.render = $(this).val();
			vz.wui.drawPlot();
		}
	});
};

/**
 * Initialize dialogs
 */
vz.wui.dialogs.init = function() {
	// initialize dialogs
	$('#entity-add.dialog').dialog({
		title: 'Kanal hinzuf&uuml;gen',
		width: 530,
		resizable: false
	});
	$('#entity-add.dialog > div').tabs();
	
	// load public entities
	vz.load({
		controller: 'entity',
		success: function(json) {
			if (json.entities.length > 0) {
				json.entities.each(function(index, entity) {
					$('#entity-subscribe-public select#public').append(
						$('<option>').html(entity.title).data('entity', entity)
					);
				});
			}
		}
	});
	
	// show available entity types
	vz.capabilities.definitions.entities.each(function(index, def) {
		$('#entity-create select[name=type]').append(
			$('<option>').html(def.translation[vz.options.language]).data('definition', def).val(def.name)
		);
	});
	$('#entity-create option[value=power]').attr('selected', 'selected');

	/*$('#entity-create select[name=type] option:selected').data('definition').required.each(function(index, property) {
		$('#entity-create #properties').append(
			vz.capabilities.definitions.get('properties', property).getDOM()
		)
	});*/
	
	$('#entity-create-middlware').val(vz.options.middlewareUrl);
	
	// actions
	$('#entity-subscribe input[type=button]').click(function() {
		try {
			var uuid = $('#entity-subscribe input#uuid');
			vz.uuids.add(uuid.val());

			if ($('#entity-subscribe input.cookie').attr('checked')) {
				vz.uuids.save();
			}
			
			vz.entities.loadDetails().done(function() {
				vz.entities.showTable();
				vz.entities.loadData().done(vz.wui.drawPlot);
			}); // reload entity details and load data
		}
		catch (e) {
			vz.wui.dialogs.exception(e);
		}
		finally {
			$('#entity-add').dialog('close');
			$('#entity-add input[type!=button]').val(''); // reset form
			$('#entity-add input.cookie').attr('checked', false); // reset form
		}
	});
	
	$('#entity-subscribe-public input[type=button]').click(function() {
		var entity = $('#entity-subscribe-public select#public option:selected').data('entity');
	
		try {
			vz.uuids.add(entity.uuid);

			if ($('#entity-subscribe-public input.cookie').attr('checked')) {
				vz.uuids.save();
			}
			
			vz.entities.loadDetails().done(function() {
				vz.entities.showTable();
				vz.entities.loadData().done(vz.wui.drawPlot);
			}); // reload entity details and load data
		}
		catch (e) {
			vz.wui.dialogs.exception(e);
		}
		finally {
			$('#entity-add').dialog('close');
			$('#entity-add input[type!=button]').val(''); // reset form
			$('#entity-add input.cookie').attr('checked', false); // reset form
		}
	});
	
	$('#entity-create form').submit(function() {
		$(this).attr('action', $('#entity-create-middlware').val() + '/channel.json');
		$('#entity-add').dialog('close');
	});
	
	// update event handler
	$('button[name=entity-add]').unbind('click', this.init);
	$('button[name=entity-add]').click(function() {
		$('#entity-add.dialog').dialog('open');
	});
};

/**
 * Build link to current viewport
 *
 * @return string url
 */
vz.wui.getPermalink = function() {
	var uuids = new Array;
	vz.entities.each(function(entity, parent) {
		if (entity.active && entity.definition.model == 'Volkszaehler\\Model\\Channel') {
			uuids.push(entity.uuid);
		}
	});
	
	var params = $.param({
		from: Math.floor(vz.options.plot.xaxis.min),
		to: Math.ceil(vz.options.plot.xaxis.max),
		uuid: uuids.unique()
	});
	
	return window.location.protocol + '//' + window.location.host + window.location.pathname + '?' + params;
}

/**
 * Build link to rendered image of current viewport
 *
 * @return string url
 */
vz.wui.getSnaplink = function() {
	var uuids = new Array;
	vz.entities.each(function(entity, parent) {
		if (entity.active) {
			uuids.push(entity.uuid);
		}
	});
	
	return vz.options.middlewareUrl + '/data/' + uuids[0] + '.png?' + $.param({
		from: Math.floor(vz.options.plot.xaxis.min),
		to: Math.ceil(vz.options.plot.xaxis.max)
	});
}

vz.wui.zoom = function(from, to) {
	vz.options.plot.xaxis.min = from;
	vz.options.plot.xaxis.max = to;

	// we dont want to zoom/pan into the future
	if (vz.options.plot.xaxis.max > new Date().getTime()) {
		delta = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min;
		vz.options.plot.xaxis.max = new Date().getTime();
		vz.options.plot.xaxis.min = new Date().getTime() - delta;
	}
	
	vz.options.plot.yaxis.max = null; // autoscaling
	vz.options.plot.yaxis.min = 0; // fixed to 0
	
	vz.entities.loadData().done(vz.wui.drawPlot);
}

/**
 * Bind events to handle plot zooming & panning
 */
vz.wui.initEvents = function() {
	$('#plot')
		.bind("plotselected", function (event, ranges) {
			vz.wui.zoom(ranges.xaxis.from, ranges.xaxis.to);
		})
		/*.bind('plotpan', function (event, plot) {
			var axes = plot.getAxes();
			vz.options.plot.xaxis.min = axes.xaxis.min;
			vz.options.plot.xaxis.max = axes.xaxis.max;
			vz.options.plot.yaxis.min = axes.yaxis.min;
			vz.options.plot.yaxis.max = axes.yaxis.max;
		})
		.bind('mouseup', function(event) {
			vz.entities.loadData().done(vz.wui.drawPlot);
		})
		.bind('plotzoom', function (event, plot) {
			var axes = plot.getAxes();
			vz.options.plot.xaxis.min = axes.xaxis.min;
			vz.options.plot.xaxis.max = axes.xaxis.max;
			vz.options.plot.yaxis.min = axes.yaxis.min;
			vz.options.plot.yaxis.max = axes.yaxis.max;
			vz.entities.loadData().done(vz.wui.drawPlot);
		});*/
};

/**
 * Move & zoom in the plotting area
 */
vz.wui.handleControls = function () {
	var delta = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min;
	var middle = vz.options.plot.xaxis.min + delta/2;

	switch($(this).val()) {
		case 'move-last':
			vz.wui.zoom(
				new Date().getTime() - delta,
				new Date().getTime()
			);
			break;
		case 'move-back':
			vz.wui.zoom(
				vz.options.plot.xaxis.min - delta,
				vz.options.plot.xaxis.max - delta
			);
			break;
		case 'move-forward':
			vz.wui.zoom(
				vz.options.plot.xaxis.min + delta,
				vz.options.plot.xaxis.max + delta
			);
			break;
		case 'zoom-reset':
			vz.wui.zoom(
				middle - vz.options.defaultInterval/2,
				middle + vz.options.defaultInterval/2
			);
			break;
		case 'zoom-in':
			vz.wui.zoom(
				middle - delta/4,
				middle + delta/4
			);
			break;
		case 'zoom-out':
			vz.wui.zoom(
				middle - delta,
				middle + delta
			);
			break;
		case 'zoom-hour':
			hour = 60*60*1000;
			vz.wui.zoom(
				middle - hour/2,
				middle + hour/2
			);
			break;
		case 'zoom-day':
			var day = 24*60*60*1000;
			vz.wui.zoom(
				middle - day/2,
				middle + day/2
			);
			break;
		case 'zoom-week':
			var week = 7*24*60*60*1000;
			vz.wui.zoom(
				middle - week/2,
				middle + week/2
			);
			break;
		case 'zoom-month':
			var month = 30*24*60*60*1000;
			vz.wui.zoom(
				middle - month/2,
				middle + month/2
			);
			break;
		case 'zoom-year':
			var year = 365*24*60*60*1000;
			vz.wui.zoom(
				middle - year/2,
				middle + year/2
			);
			break;
	}
};

/**
 * Refresh plot with new data
 */
vz.wui.refresh = function() {
	var delta = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min;
	vz.wui.zoom( // move plot
		new Date().getTime() - delta,
		new Date().getTime()
	);
};

/**
 * Refresh graphs after timeout ms, with a minimum of vz.options.minTimeout ms
 */
vz.wui.setTimeout = function() {
	// clear an already set timeout
	if (vz.wui.timeout != null) {
		window.clearTimeout(vz.wui.timeout);
		vz.wui.timeout = null;
	}
	
	var t = Math.max((vz.options.plot.xaxis.max - vz.options.plot.xaxis.min) / vz.options.tuples, vz.options.minTimeout);
	vz.wui.timeout = window.setTimeout(vz.wui.refresh, t);
	
	$('#refresh-time').html('(' + Math.round(t / 1000) + ' s)');
}

/**
 * Stop auto-refresh of graphs
 */
vz.wui.clearTimeout = function(text) {
	$('#refresh-time').html(text || '');
	
	var rc = window.clearTimeout(vz.wui.timeout);
	vz.wui.timeout = null;
	return rc;
}

/**
 * Rounding precision
 *
 * Math.round rounds to whole numbers
 * to round to one decimal (e.g. 15.2) we multiply by 10,
 * round and reverse the multiplication again
 * therefore "vz.options.precision" needs
 * to be set to 1 (for 1 decimal) in that case
 */
vz.wui.formatNumber = function(number) {
	return Math.round(number * Math.pow(10, vz.options.precision)) / Math.pow(10, vz.options.precision);
}

vz.wui.updateHeadline = function() {
	var from = $.plot.formatDate(new Date(vz.options.plot.xaxis.min + vz.options.timezoneOffset), '%d. %b %h:%M:%S', vz.options.plot.xaxis.monthNames);
	var to = $.plot.formatDate(new Date(vz.options.plot.xaxis.max + vz.options.timezoneOffset), '%d. %b %h:%M:%S', vz.options.plot.xaxis.monthNames);
	$('#title').html(from + ' - ' + to);
}

/**
 * Overwritten each iterator to iterate recursively throug all entities
 */
vz.entities.each = function(cb) {
	for (var i = 0; i < this.length; i++) {
		this[i].each(cb);
	}
}

/**
 * Get all entity information from middleware
 */
vz.entities.loadDetails = function() {
	this.clear();
	
	var queue = new Array;
	
	vz.uuids.each(function(index, uuid) {
		queue.push(vz.load({
			controller: 'entity',
			identifier: uuid,
			success: function(json) {
				vz.entities.push(new Entity(json.entity));
			}
		}));
	});
	
	return $.when.apply($, queue);
};

/**
 * Create nested entity list
 *
 * @todo move to Entity class
 */
vz.entities.showTable = function() {
	$('#entity-list tbody').empty();

	vz.entities.sort(Entity.compare);
	
	var c = 0; // for colors
	vz.entities.each(function(entity, parent) {
		entity.color = vz.options.plot.colors[c++ % vz.options.plot.colors.length];
		
		$('#entity-list tbody').append(entity.getRow());
	});

	/*
	 * Initialize treeTable
	 * 
	 * http://ludo.cubicphuse.nl/jquery-plugins/treeTable/doc/index.html
	 * https://github.com/ludo/jquery-plugins/tree/master/treeTable
	 */
	// configure entities as draggable
	$('#entity-list tr.channel span.indicator, #entity-list tr.aggregator span.indicator').draggable({
		helper:  'clone',
		opacity: .75,
		refreshPositions: true, // Performance?
		revert: 'invalid',
		revertDuration: 300,
		scroll: true
	});

	// configure aggregators as droppable
	$('#entity-list tr.aggregator span.indicator').each(function() {
		$(this).parents('tr').droppable({
			//accept: 'tr.channel span.indicator, tr.aggregator span.indicator', // TODO
			drop: function(event, ui) {
				var child = $(ui.draggable.parents('tr')[0]).data('entity');
				var from = child.parent;
				var to = $(this).data('entity');
				
				$('#entity-move').dialog({ // confirm prompt
					resizable: false,
					modal: true,
					title: 'Verschieben',
					width: 400,
					buttons: {
						'Verschieben': function() {
							try {
								var queue = new Array;
								queue.push(to.addChild(child)); // add to new aggregator
					
								if (from !== undefined) {
									queue.push(from.removeChild(child)); // remove from aggregator
								}
								else {
									vz.uuids.remove(child.uuid); // remove from cookies
									vz.uuids.save();
								}
							} catch (e) {
								vz.wui.dialogs.exception(e);
							} finally {
								$.when(queue).done(function() {
									// wait for middleware
									vz.entities.loadDetails().done(vz.entities.showTable);
								});
								$(this).dialog('close');
							}
						},
						'Abbrechen': function() {
							$(this).dialog('close');
						}
					}
				});
			},
			hoverClass: 'accept',
			over: function(event, ui) {
				// make the droppable branch expand when a draggable node is moved over it
				if (this.id != $(ui.draggable.parents('tr')[0]).id && !$(this).hasClass('expanded')) {
					$(this).expand();
				}
			}
		});
	});

	// make visible that a row is clicked
	$('#entity-list table tbody tr').mousedown(function() {
		$('tr.selected').removeClass('selected'); // deselect currently selected rows
		$(this).addClass('selected');
	});

	// make sure row is selected when span is clicked
	$('#entity-list table tbody tr span').mousedown(function() {
		$($(this).parents('tr')[0]).trigger('mousedown');
	});
	
	$('#entity-list table').treeTable({
		treeColumn: 2,
		clickableNodeNames: true,
		initialState: 'expanded'
	});
};

/**
 * Load json data from the middleware
 *
 * @todo move to Entity class
 */
vz.entities.loadData = function() {
	$('#overlay').html('<img src="images/loading.gif" alt="loading..." /><p>loading...</p>');

	var queue = new Array;

	vz.entities.each(function(entity) {
		if (entity.active && entity.definition.model == 'Volkszaehler\\Model\\Channel') {
			queue.push(entity.loadData());
		}
	});
	
	return $.when.apply($, queue);
};

/**
 * Draws plot to container
 */
vz.wui.drawPlot = function () {
	vz.wui.updateHeadline();

	var series = new Array;
	vz.entities.each(function(entity) {
		if (entity.active && entity.data && entity.data.tuples && entity.data.tuples.length > 0) {
			var serie = {
				data: entity.data.tuples,
				color: entity.color,
				lines: {
					show: (vz.options.render == 'lines'),
					steps: (entity.definition.interpreter == 'Volkszaehler\\Interpreter\\MeterInterpreter')
				},
				points: { show: (vz.options.render == 'points') }
			};
			
			series.push(serie);
		}
	});
	
	if (series.length == 0) {
		$('#overlay').html('<img src="images/empty.png" alt="no data..." /><p>nothing to plot...</p>');
		series.push({}); // add empty dataset to show axes
	}
	else {
		$('#overlay').empty();
	}

	vz.plot = $.plot($('#flot'), series, vz.options.plot);
	
	// disable automatic refresh if we are in past
	if (vz.options.refresh && vz.options.plot.xaxis.max < new Date().getTime() - 1000) {
		vz.wui.clearTimeout('(suspended)');
	}
	else if (vz.options.refresh) {
		vz.wui.setTimeout();
	}
};

/*
 * Error & Exception handling
 */
 
var Exception = function(type, message, code) {
	return {
		type: type,
		message: message,
		code: code
	};
}

vz.wui.dialogs.error = function(error, description, code) {
	if (code !== undefined) {
		error = code + ': ' + error;
	}

	$('<div>')
	.append($('<span>').html(description))
	.dialog({
		title: error,
		width: 450,
		dialogClass: 'ui-error',
		resizable: false,
		modal: true,
		buttons: {
			Ok: function() {
				$(this).dialog('close');
			}
		}
	});
};

vz.wui.dialogs.exception = function(exception) {
	this.error(exception.type, exception.message, exception.code);
};
