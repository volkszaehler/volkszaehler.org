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
	vz.options.tuples = Math.round($('#flot').width() / 3);
	
	// initialize dropdown accordion
	$('#accordion h3').click(function() {
		$(this).next().toggle('fast', function() {
			// resizing plot: workaround for #76
			vz.plot.resize();
			vz.plot.setupGrid();
			vz.plot.draw();
		});
		
		return false;
	}).next().hide();
	$('#entity-list').show(); // open entity list by default
	
	// buttons
	$('button, input[type=button],[type=image],[type=submit]').button();
	$('button[name=options-save]').click(vz.options.saveCookies);
	$('button[name=entity-add]').click(this.dialogs.init);
	
	$('#export select').change(function(event) {
		switch ($(this).val()) {
			case 'permalink':
				window.location = vz.getPermalink();
				break;
			case 'png':
			case 'csv':
			case 'xml':
				window.location = vz.getLink($(this).val());
				break;
				
		}
		$('#export option[value=default]').attr('selected', true);
	});
	
	// bind plot actions
	$('#controls button').click(this.handleControls);
	$('#controls').buttonset();
	
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
	
	// toggle all channels
	$('#entity-toggle').click(function() {
		vz.entities.each(function(entity, parent) {
			entity.activate(!entity.active, parent, false).done(vz.wui.drawPlot);
		}, true);
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
	$('#entity-add.dialog > div').tabs({
		show: function(event, ui) { // lazy loading public entities
			if (ui.index != 1) {
				return; // abort, we are not in public tab
			}
		
			vz.load({
				controller: 'entity',
				success: function(json) {
					var public = new Array;
					json.entities.each(function(index, json) {
						public.push(new Entity(json));
					});

					public.sort(Entity.compare);
					vz.middleware[0].public = public;
			
					$('#entity-public-entity').empty();
					public.each(function(index, entity) {
						$('#entity-public-entity').append(
							$('<option>').html(entity.title).val(entity.uuid).data('entity', entity)
						);
					});
				}
			});
		}
	});
	
	// show available entity types
	vz.capabilities.definitions.entities.each(function(index, def) {
		$('#entity-create select[name=type]').append(
			$('<option>')
				.html(def.translation[vz.options.language])
				.data('definition', def)
				.val(def.name)
				.css('background-image', 'url(images/types/' + def.icon)
		);
	});
	$('#entity-create option[value=power]').attr('selected', 'selected');

	// set defaults
	$('#entity-subscribe-middleware').val(vz.options.localMiddleware);
	$('#entity-public-middleware').append($('<option>').val(vz.options.localMiddleware).text('Local (default)'));
	$('#entity-create-middleware').val(vz.options.localMiddleware);
	$('#entity-subscribe-cookie').attr('checked', 'checked');
	$('#entity-public-cookie').attr('checked', 'checked');
	
	// actions
	$('#entity-subscribe input[type=button]').click(function() {
		try {
			var entity = new Entity({
				uuid: $('#entity-subscribe-uuid').val(),
				cookie: Boolean($('#entity-subscribe-cookie').attr('checked'))
			});
			
			if (middleware = $('#entity-subscribe-middleware').val()) {
				entity.middleware = middleware;
			}
			
			entity.loadDetails().done(function() {
				vz.entities.push(entity);
				vz.entities.saveCookie();
				vz.entities.showTable();
				vz.entities.loadData().done(vz.wui.drawPlot);
			}); // reload entity details and load data
		}
		catch (e) {
			vz.wui.dialogs.exception(e);
		}
		finally {
			$('#entity-add').dialog('close');
		}
	});
	
	$('#entity-public input[type=button]').click(function() {
		var entity = $('#entity-public-entity option:selected').data('entity');
	
		try {
			entity.cookie = Boolean($('#entity-public-cookie').attr('checked'));
			entity.middleware = $('#entity-public-middleware option:selected').val();
			
			vz.entities.push(entity);
			vz.entities.saveCookie();
			vz.entities.showTable();
			vz.entities.loadData().done(vz.wui.drawPlot);
		}
		catch (e) {
			vz.wui.dialogs.exception(e);
		}
		finally {
			$('#entity-add').dialog('close');
		}
	});
	
	$('#entity-create form').submit(function() {
		var def = $('select[name=type] option:selected', this).data('definition');

		vz.load({
			controller: (def.model == 'Volkszaehler\\Model\\Channel') ? 'channel' : 'aggregator',
			url: $('#entity-create-middleware').val(),
			data: $(this).serialize(),
			type: 'POST',
			success: function(json) {
				var entity = new Entity(json.entity);
				
				try {
					entity.cookie = Boolean($('#entity-create-cookie').attr('checked'));
					entity.middleware = $('#entity-create-middleware').val();

					vz.entities.push(entity);
					vz.entities.saveCookie();
					vz.entities.showTable();
					vz.entities.loadData().done(vz.wui.drawPlot);
				}
				catch (e) {
					vz.wui.dialogs.exception(e);
				}
				finally {	
					$('#entity-add').dialog('close');
				}
			}
		});
		
		return false;
	});
	
	// update event handler after lazy loading
	$('button[name=entity-add]').unbind('click', this.init);
	$('button[name=entity-add]').click(function() {
		$('#entity-add.dialog').dialog('open');
	});
};

vz.wui.zoom = function(from, to) {

	// we cannot zoom/pan into the future
	var now = new Date().getTime();
	if (to > now) {
		var delta = to - from;
		vz.options.plot.xaxis.min = now - delta;
		vz.options.plot.xaxis.max = now;
	} else {
		vz.options.plot.xaxis.min = from;
		vz.options.plot.xaxis.max = to;
	}

	vz.wui.tmaxnow = (vz.options.plot.xaxis.max >= (now - 1000));
	
	if (vz.options.plot.xaxis.min < 0) {
		vz.options.plot.xaxis.min = 0;
	}
	
	vz.options.plot.yaxis.max = null; // autoscaling
	vz.options.plot.yaxis.min = 0; // fixed to 0
	
	vz.entities.loadData().done(vz.wui.drawPlot);
};

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
	var d = new Date(middle);
	var now = new Date().getTime();

	switch($(this).val()) {
		case 'move-last':
			vz.wui.zoom(now-delta, now);
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
			if (vz.wui.tmaxnow)
				vz.wui.zoom(now - delta/2, now);
			else
				vz.wui.zoom(middle - delta/4, middle + delta/4);
			break;
		case 'zoom-out':
			vz.wui.zoom(
				middle - delta,
				middle + delta
			);
			break;
		case 'zoom-hour':
			vz.wui.zoom(
				new Date(d.getFullYear(), d.getMonth(), d.getDate(), d.getHours()).getTime(),
				new Date(d.getFullYear(), d.getMonth(), d.getDate(), d.getHours()+1).getTime()
			);
			break;
		case 'zoom-day':
			vz.wui.zoom(
				new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime(),
				new Date(d.getFullYear(), d.getMonth(), d.getDate()+1).getTime()
			);
			break;
		case 'zoom-week':
			vz.wui.zoom(
				new Date(d.getFullYear(), d.getMonth(), d.getDate()-d.getDay()+1).getTime(), // start from monday
				new Date(d.getFullYear(), d.getMonth(), d.getDate()-d.getDay()+8).getTime()
			);
			break;
		case 'zoom-month':
			vz.wui.zoom(
				new Date(d.getFullYear(), d.getMonth(), 1).getTime(),
				new Date(d.getFullYear(), d.getMonth()+1, 1).getTime()
			);
			break;
		case 'zoom-year':
			vz.wui.zoom(
				new Date(d.getFullYear(), 0, 1).getTime(),
				new Date(d.getFullYear()+1, 0, 1).getTime()
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
};

/**
 * Stop auto-refresh of graphs
 */
vz.wui.clearTimeout = function(text) {
	$('#refresh-time').html(text || '');
	
	var rc = window.clearTimeout(vz.wui.timeout);
	vz.wui.timeout = null;
	return rc;
};

/**
 * Rounding precision
 *
 * Math.round rounds to whole numbers
 * to round to one decimal (e.g. 15.2) we multiply by 10,
 * round and reverse the multiplication again
 * therefore "vz.options.precision" needs
 * to be set to 1 (for 1 decimal) in that case
 */
vz.wui.formatNumber = function(number, prefix) {
	var siPrefixes = ['k', 'M', 'G', 'T'];
	var siIndex = 0;
	
	while (prefix && number > 1000 && siIndex < siPrefixes.length-1) {
		number /= 1000;
		siIndex++;
	}
	
	number = Math.round(number * Math.pow(10, vz.options.precision)) / Math.pow(10, vz.options.precision); // rounding
	
	if (prefix) {
		number += (siIndex > 0) ? ' ' + siPrefixes[siIndex-1] : ' ';
	}

	return number;
};

vz.wui.updateHeadline = function() {
	var delta = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min;
	var format = '%B %d. %b %y';
	
	if (delta < 3*24*3600*1000) format += ' %h:%M'; // under 3 days
	if (delta < 5*60*1000) format += ':%S'; // under 5 minutes
	
	var from = $.plot.formatDate(new Date(vz.options.plot.xaxis.min), format, vz.options.monthNames, vz.options.dayNames, true);
	var to = $.plot.formatDate(new Date(vz.options.plot.xaxis.max), format, vz.options.monthNames, vz.options.dayNames, true);
	$('#title').html(from + ' - ' + to);
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
					show: (entity.style == 'lines' || entity.style == 'steps'),
					steps: (entity.style == 'steps')
				},
				points: {
					show: (entity.style == 'points')
				}
			};
			
			series.push(serie);
		}
	}, true); // recursive!
	
	if (series.length == 0) {
		$('#overlay').html('<img src="images/empty.png" alt="no data..." /><p>nothing to plot...</p>');
		series.push({}); // add empty dataset to show axes
	}
	else {
		$('#overlay').empty();
	}

	vz.plot = $.plot($('#flot'), series, vz.options.plot);
	
	// disable automatic refresh if we are in past
	if (vz.options.refresh) {
		if (vz.wui.tmaxnow) {
			vz.wui.setTimeout();
		} else {
			vz.wui.clearTimeout('(suspended)');
		}
	} else {
		vz.wui.clearTimeout();
	}
};

/*
 * Error & Exception handling
 */

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
