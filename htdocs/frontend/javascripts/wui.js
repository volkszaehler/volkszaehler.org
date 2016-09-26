/**
 * Javascript functions for the frontend
 *
 * @author Florian Ziegler <fz@f10-home.de>
 * @author Justin Otherguy <justin@justinotherguy.org>
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Götz <cpuidle@gmx.de>
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
	vz.wui.initEvents();

	// resize handling
	$(window).resize(vz.wui.resizePlot);
	$('#accordion h3').click(function() {
		$(this).next().toggle(0, vz.wui.resizePlot);
		return false;
	}).next().hide();
	$('#entity-list').show(); // open entity list by default

	// buttons
	$('button, input[type=button],[type=image],[type=submit]').button();
	$('button[name=options-save]').click(vz.options.saveCookies);
	$('button[name=entity-add]').click(this.dialogs.init);

	$('#export select').change(function(event) {
		vz.wui.exportData($(this).val());
		$(this).val('default');
	});

	// bind plot actions
	$('#controls button').click(this.handleControls);
	$('#controls').buttonset();

	// auto refresh
	$('#refresh').prop('checked', vz.options.refresh);
	if (vz.options.refresh) {
		vz.wui.tmaxnow = true;
		vz.wui.setTimeout();
	}
	$('#refresh').change(function() {
		vz.options.refresh = $(this).prop('checked');
		if (vz.options.refresh) {
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
 * Adjust plot when screen size changes
 */
vz.wui.resizePlot = function() {
	// resize container depending on window vs. content height
	var delta = $(window).height() - $('html').height();
	$('#flot').height(Math.max($('#flot').height() + delta, vz.options.plot.minHeight || 300));
	vz.options.tuples = Math.round($('#flot').width() / 3);
	if (vz.plot && vz.plot.resize) {
		vz.plot.resize();
		vz.plot.setupGrid();
		vz.plot.draw();
	}
};

/**
 * Export data
 */
vz.wui.exportData = function(value) {
	switch (value) {
		case 'permalink':
			window.location = vz.getPermalink();
			break;
		case 'png':
			$.when(
				$.cachedScript('javascripts/canvas/Blob.js'),
				$.cachedScript('javascripts/canvas/canvas-toBlob.js'),
				$.cachedScript('javascripts/canvas/FileSaver.js'))
			.done(function() {
				// will prompt the user to save the image as PNG
				vz.plot.getCanvas().toBlob(function(blob) {
					saveAs(blob, 'Screenshot.png');
				});
			});
			break;
		case 'csv':
		case 'json':
		case 'xml':
			window.location = vz.getLink(value);
			break;
	}
};

/**
 * Add entity after UI has already been initialized
 * Triggers refresh of entity data, plot and axes
 */
vz.wui.addEntity = function(entity) {
	vz.entities.push(entity);
	vz.entities.saveCookie();
	vz.entities.showTable();
	vz.options.plot.axesAssigned = false; // force axis assignment

	// load data including children
	var queue = [];
	queue.push(entity.loadData());
	entity.each(function(child) {
		queue.push(child.loadData());
	}, true); // recursive

	$.when.apply($, queue).then(function() {
		vz.wui.drawPlot();
		entity.loadTotalConsumption();
		entity.each(function(child) {
			child.loadTotalConsumption();
		}, true); // recursive
	});
};

/**
 * Initialize dialogs
 */
vz.wui.dialogs.init = function() {
	// initialize dialogs
	$('#entity-add.dialog').dialog({
		title: unescape('Kanal hinzuf%FCgen'),
		width: 650,
		resizable: false
	});
	$('#entity-add.dialog > div').tabs({
		activate: function(event, ui) { // lazy loading public entities
			if (ui.newTab.attr('aria-controls') == 'entity-public') {
				// populate MW entities (vz.middleware[0] is the default local)
				vz.middleware.forEach(function(middleware, idx) {
					vz.load({
						controller: 'entity',
						url: middleware.url
					}).done(function(json) {
							var public = [];
							json.entities.each(function(index, json) {
								var entity = new Entity(json, middleware.url);
								public.push(entity);
							});

							public.sort(Entity.compare);
							vz.middleware[idx].public = public;

							if (idx === 0) {
								populateEntities(vz.middleware[idx]);
							}
					});
				});
			}
		}
	});

	// show available entity types
	vz.capabilities.definitions.entities.each(function(index, def) {
		$('#entity-create select[name=type]').append(
			$('<option>')
				.html(def.translation[vz.options.language])
				.data('definition', def)
				.val(def.name)
				.css('background-image', def.icon ? 'url(images/types/' + def.icon : null)
		);
	});
	$('#entity-create option[value=power]').attr('selected', 'selected');

	// set defaults
	$('#entity-subscribe-middleware').val(vz.options.middleware[0].url);
	// add middlewares
	vz.middleware.forEach(function(middleware, idx) {
		$('#entity-public-middleware').append($('<option>').val(middleware.url).text(middleware.title));
	});
	$('#entity-create-middleware').val(vz.options.middleware[0].url);
	$('#entity-subscribe-cookie').attr('checked', 'checked');
	$('#entity-public-cookie').attr('checked', 'checked');

	// actions
	$('#entity-public-middleware').change(function() {
		var title = $('#entity-public-middleware option:selected').text();
		vz.middleware.forEach(function(middleware) {
			// populate entities for selected middleware
			if (middleware.title == title) {
				populateEntities(middleware);
			}
		});
	});

	$('#entity-subscribe input[type=button]').click(function() {
		try {
			var entity = new Entity({
				uuid: $('#entity-subscribe-uuid').val(),
				cookie: Boolean($('#entity-subscribe-cookie').prop('checked')),
				middleware: $('#entity-subscribe-middleware').val()
			});

			entity.loadDetails().done(function(json) {
				vz.wui.addEntity(entity);
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
		// get entity from data attribute
		var entity = $('#entity-public-entity option:selected').data('entity');
		try {
			entity.cookie = Boolean($('#entity-public-cookie').prop('checked'));
			vz.wui.addEntity(entity);
		}
		catch (e) {
			vz.wui.dialogs.exception(e);
		}
		finally {
			$('#entity-add').dialog('close');
		}
	});

	function populateEntities(middleware) {
		var public = middleware.public;

		$('#entity-public-entity').empty();
		public.each(function(index, entity) {
			$('#entity-public-entity').append(
				$('<option>').html(entity.title).val(entity.uuid).data('entity', entity)
			);
		});
	}

	$('#entity-create select').change(function() {
		$('#entity-create form table .required').remove();
		$('#entity-create form table .optional').remove();

		var container = $('#entity-create form table');
		var entityDefinition = vz.capabilities.definitions.entities[$(this)[0].selectedIndex];
		vz.wui.dialogs.addProperties(container, entityDefinition.required, "required");
		vz.wui.dialogs.addProperties(container, entityDefinition.optional, "optional");

		// set default style
		if (entityDefinition.style) {
			$(container).find('select[name=style] option[value=' + entityDefinition.style + ']').attr('selected', 'selected');
		}
	});
	$('#entity-create select').change();

	$('#entity-create form').submit(function() {
		var def = $('select[name=type] option:selected', this).data('definition');
		var properties = {};

		// serializeArray instead of serializeArrayWithCheckBoxes is sufficient as non-active checkboxes don't need to create properties
		$(this).serializeArray().each(function(index, value) {
			if (value.value !== '') {
				properties[value.name] = value.value;
			}
		});

		vz.load({
			controller: (def.model == 'Volkszaehler\\Model\\Channel') ? 'channel' : 'aggregator',
			url: $('#entity-create-middleware').val(),
			data: properties,
			method: 'POST'
		}).done(function(json) {
			var entity = new Entity(json.entity, $('#entity-create-middleware').val());

			try {
				entity.cookie = Boolean($('#entity-create-cookie').prop('checked'));
				vz.wui.addEntity(entity);
			}
			catch (e) {
				vz.wui.dialogs.exception(e);
			}
			finally {
				$('#entity-add').dialog('close');
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

/**
 * Show available properties for selected type
 */
vz.wui.dialogs.addProperties = function(container, proplist, className, entity) {
	proplist.each(function(index, def) {

		// hide properties from blacklist
		var val = (entity && typeof entity[def] !== undefined) ? entity[def] : null;
		if ((typeof val === 'undefined' || val === null) && vz.options.hiddenProperties.indexOf(def) >= 0) {
			return; // hide less commonly used properties
		}

		vz.capabilities.definitions.properties.each(function(propindex, propdef) {
			if (def == propdef.name) {
				var cntrl = null;
				var row = $('<tr>')
					.addClass("property")
					.append(
						$('<td>').text(propdef.translation[vz.options.language])
					);

				switch (propdef.type) {
					case 'float':
					case 'integer':
					case 'string':
						cntrl = $('<input size="36">').attr("type", "text");
						break;

					case 'text':
						cntrl = $('<textarea>');
						break;

					case 'boolean':
						cntrl = $('<input>').attr("type", "checkbox").val("1"); // boolean value
						break;

					case 'multiple':
						cntrl = $('<select>').attr("Size", "1");
						propdef.options.each(function(optindex, optdef) {
							cntrl.append(
								$('<option>').html(optdef).val(optdef)
							);
						});
						break;
				}

				// editing?
				if (entity && cntrl !== null) {
					// set current value
					switch (propdef.type) {
						case 'float':
						case 'integer':
						case 'string':
						case 'text':
							cntrl.val(val);
							break;

						case 'boolean':
							cntrl.attr('checked', val);
							break;

						case 'multiple':
							cntrl.find('option[value="' + val + '"]').attr('selected', 'selected');
							break;
					}
				}

				switch (propdef.name) {
					case 'fillstyle':
						cntrl = $('<div id="slider"></div>').slider({
							value: (entity) ? entity[def] : 0,
							min: propdef.min,
							max: propdef.max,
							step: (propdef.max - propdef.min) / 20,
							slide: function(event, ui) {
								$('.simpleColorChooser').hide();
								$('#slider input').val(ui.value);
							}
						})
						.append($('<input>')
							.attr('type', 'hidden').attr("name", propdef.name)
							.val((entity) ? entity[def] : 0)
						);
						break;

					case 'color':
						cntrl = $('<input>')
							.attr('type', 'hidden').attr("name", propdef.name)
							.val((entity) ? entity[def] : 'aqua');
						$.cachedScript('javascripts/jquery/jquery.simple-color.min.js').done(function() {
							// cntrl.attr('id', 'colorValue');
							cntrl.simpleColor({
								cellWidth: 16,
								cellHeight: 16,
								chooserCSS: { "border-color": "#a7a7a7", "z-index": 20 }, // above slider
								displayCSS: { "border-color": "#a7a7a7" } // similar to style.css
							});
						});
						break;
				}

				if (cntrl !== null) {
					row.addClass(className);
					cntrl.attr("name", propdef.name);
					row.append($('<td>').append(cntrl));
					container.append(row);
				}

				return false;
			}
		});
	});
};

/**
 * Extend from..to range to match push updates and redraw
 */
vz.wui.zoomToPartialUpdate = function(to) {
	if (vz.wui.tmaxnow) {
		// move chart display window
		var delta = to - vz.options.plot.xaxis.max;
		vz.options.plot.xaxis.max = to;
		vz.options.plot.xaxis.min += delta;

		// draw after timeout
		vz.wui.pushRedrawTimeout = window.setTimeout(function() {
			vz.wui.pushRedrawTimeout = null;
			vz.wui.drawPlot();
		}, vz.options.pushRedrawTimeout);
	}
	else {
		window.clearTimeout(vz.wui.pushRedrawTimeout);
	}
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
		.bind("plothover", function (event, pos, item) {
			// $('#title').html("pos "+pos.x + " - event-data: "+event.data);
			if (!vz.entities || !vz.entities.length)
				return; // no channels -> nothing to do
			vz.wui.latestPosition = pos;
			if (!vz.wui.updateLegendTimeout)
				vz.wui.updateLegendTimeout = setTimeout(vz.wui.updateLegend, 50);
		});
};

/**
 * Update legend on move hover
 */
vz.wui.updateLegend = function() {
	vz.wui.updateLegendTimeout = null;
	var pos = vz.wui.latestPosition;

	var axes = vz.plot.getAxes();
	if (pos.x < axes.xaxis.min || pos.x > axes.xaxis.max ||
		pos.y < axes.yaxis.min || pos.y > axes.yaxis.max)
		return;

	var i, j, dataset = vz.plot.getData();
	for (i = 0; i < dataset.length; ++i) {
		var series = dataset[i];

		if (!series.data.length)
			continue;

		// find the nearest points, x-wise
		for (j = 0; j < series.data.length; ++j)
			if (series.data[j][0] > pos.x)
				break;
		var y;
		if (series.lines.steps) {
			var p = series.data[j-1];
			if (p)
				y = p[1];
			else
				y = null;
		} else if (series.lines.states) {
			y = null;
			if (j < series.data.length) {
				var p3 = series.data[j];
				if (p3)
					y = p3[1];
			}
		} else { // no steps -> interpolate
			var p1 = series.data[j - 1], p2 = series.data[j];
			if (p1 == null || p2 == null) // jshint ignore:line
				y = null;
			else
				y = p1[1] + (p2[1] - p1[1]) * (pos.x - p1[0]) / (p2[0] - p1[0]);
		}

		var legend = $('.legend .legendLabel');
		if (y === null) {
			legend.eq(i).text(series.title);
		} else {
			// use plot wrapper instead of `new Date()` for timezone support
			var d = $.plot.dateGenerator(pos.x, vz.options.plot.xaxis);
			var delta = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min;
			var format = (delta > 1*24*3600*1000) ? '%d.%m.%y - %H:%M' : '%H:%M:%S';
			legend.eq(i).text(series.title + ": " + $.plot.formatDate(d,format) + " - " + vz.wui.formatNumber(y, series.unit));
		}
	}

	// update opaque background sizing
	$('.legend > div').css({ width: $('.legend table').css('width') });
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
			if (vz.wui.tmaxnow)
				vz.wui.zoom(now - 3600*1000, now);
			else
				vz.wui.zoom(
					new Date(d.getFullYear(), d.getMonth(), d.getDate(), d.getHours()).getTime(),
					new Date(d.getFullYear(), d.getMonth(), d.getDate(), d.getHours()+1).getTime()
				);
			break;
		case 'zoom-day':
			if (vz.wui.tmaxnow)
				vz.wui.zoom(now - 24*3600*1000, now);
			else
				vz.wui.zoom(
					new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime(),
					new Date(d.getFullYear(), d.getMonth(), d.getDate()+1).getTime()
				);
			break;
		case 'zoom-week':
			if (vz.wui.tmaxnow)
				vz.wui.zoom(now - 7*24*3600*1000, now);
			else
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
 * Zoom plot to target timeframe
 */
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

	vz.entities.loadData().done(vz.wui.drawPlot);
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
	if (vz.wui.timeout !== null) {
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
vz.wui.formatNumber = function(number, unit, prefix) {
	prefix = prefix || true; // default on
	var siPrefixes = ['k', 'M', 'G', 'T'];
	var siIndex = 0,
			maxIndex = (typeof prefix == 'string') ? siPrefixes.indexOf(prefix)+1 : siPrefixes.length;

	// flow unit or air pressure?
	if (['l', 'm3', 'm^3', 'm³', 'l/h', 'm3/h', 'm/h^3', 'm³/h', 'hPa'].indexOf(unit) >= 0) {
		// don't scale...
		maxIndex = -1;

		// ...unless for l->m3 conversion
		if (Math.abs(number) > 1000 && (unit == 'l' || unit == 'l/h')) {
			unit = 'm³' + unit.substring(1);
			number /= 1000;
		}
	}

	while (prefix && Math.abs(number) > 1000 && siIndex < maxIndex) {
		number /= 1000;
		siIndex++;
	}

	// avoid infinities/NaN
	if (number < 0 || number > 0) {
		var precision = Math.max(0, vz.options.precision - Math.floor(Math.log(Math.abs(number))/Math.LN10));
		number = Math.round(number * Math.pow(10, precision)) / Math.pow(10, precision); // rounding
	}

	// avoid almost zero
	if (Math.abs(number) < Math.pow(10, -vz.options.precision)) {
		number = 0;
	}

	if (prefix)
		number += (siIndex > 0) ? ' ' + siPrefixes[siIndex-1] : ' ';
	else
		number += ' ';

	if (unit) number += unit;

	return number;
};

/**
 * Convert units into hourly consumption unit
 */
vz.wui.formatConsumptionUnit = function(unit) {
	var suffix = '/h';
	if (unit.indexOf(suffix, unit.length - suffix.length) !== -1) {
		unit = unit.substring(0, unit.length - suffix.length);
	}
	else if (unit !== 'h') {
		unit += 'h';
	}

	return unit;
};

/**
 * Flot tickFormatter extension to apply axis labels
 * Copied from jquery.flot.js
 */
vz.wui.tickFormatter = function (value, axis, tickIndex, ticks) {
	// return label instead of last tick
	if (ticks && tickIndex === ticks.length-1 && axis.options.axisLabel) {
		return '[' + axis.options.axisLabel + ']';
	}

	var factor = axis.tickDecimals ? Math.pow(10, axis.tickDecimals) : 1;
	var formatted = "" + Math.round(value * factor) / factor;

	if (axis.tickDecimals !== null) {
		var decimal = formatted.indexOf(".");
		var precision = decimal == -1 ? 0 : formatted.length - decimal - 1;
		if (precision < axis.tickDecimals) {
			return (precision ? formatted : formatted + ".") + ("" + factor).substr(1, axis.tickDecimals - precision);
		}
	}

	return formatted;
};

/**
 * Update headline on zoom
 */
vz.wui.updateHeadline = function() {
	var delta = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min;
	var format = '%a %e. %b %Y';

	if (delta < 3*24*3600*1000) format += ' %H:%M'; // under 3 days
	if (delta < 5*60*1000) format += ':%S'; // under 5 minutes

	// timezone-aware dates if timezon-js is inlcuded
	var from = $.plot.dateGenerator(vz.options.plot.xaxis.min, vz.options.plot.xaxis);
	var to = $.plot.dateGenerator(vz.options.plot.xaxis.max, vz.options.plot.xaxis);

	from = $.plot.formatDate(from, format, vz.options.monthNames, vz.options.dayNames, true);
	to = $.plot.formatDate(to, format, vz.options.monthNames, vz.options.dayNames, true);
	$('#title').html(from + ' - ' + to);
};

/**
 * Draws plot to container
 */
vz.wui.drawPlot = function () {
	vz.options.interval = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min;
	vz.wui.updateHeadline();

	// assign entities to axes
	if (vz.options.plot.axesAssigned === false) {
		vz.entities.each(function(entity) {
			entity.assignAxis();
		}, true);

		vz.options.plot.axesAssigned = true;
	}

	var series = [], index = 0;
	vz.entities.each(function(entity) {
		if (entity.active && entity.definition && entity.definition.model == 'Volkszaehler\\Model\\Channel' &&
				entity.data && entity.data.tuples && entity.data.tuples.length > 0) {
			var i, maxTuples = 0;

			// work on copy here to be able to redraw
			var tuples = entity.data.tuples.map(function(t) {
				return t.slice(0);
			});


			var style = vz.options.style || entity.style;
			var fillstyle = parseFloat(vz.options.fillstyle || entity.fillstyle);
			var linewidth = parseFloat(vz.options.linewidth || vz.options[index == vz.wui.selectedChannel ? 'lineWidthSelected' : 'lineWidthDefault']);

			// mangle data for "steps" curves by shifting one ts left ("step-before")
			if (style == "steps") {
				tuples.unshift([entity.data.from, 1, 1]); // add new first ts
				for (i=0; i<tuples.length-1; i++) {
					tuples[i][1] = tuples[i+1][1];
				}
			}

			// remove number of datapoints from each tuple to avoid flot fill error
			if (fillstyle || entity.gap) {
				for (i=0; i<tuples.length; i++) {
					maxTuples = Math.max(maxTuples, tuples[i][2]);
					delete tuples[i][2];
				}
			}

			var serie = {
				data: tuples,
				color: entity.color,
				label: entity.title,
				title: entity.title,
				unit : entity.definition.unit,
				lines: {
					show:       style == 'lines' || style == 'steps' || style == 'states',
					steps:      style == 'steps' || style == 'states',
					fill:       fillstyle !== undefined ? fillstyle : false,
					lineWidth:  linewidth
				},
				points: {
					show:       style == 'points'
				},
				yaxis: entity.assignedYaxis
			};

			// disable interpolation when data has gaps
			if (entity.gap) {
				var minGapWidth = (entity.data.to - entity.data.from) / tuples.length;
				serie.xGapThresh = Math.max(entity.gap * 1000 * maxTuples, minGapWidth);
				vz.options.plot.xaxis.insertGaps = true;
			}

			// use this index for setting vz.wui.selectedChannel
			entity.index = index++;

			series.push(serie);
		}
	}, true);

	if (series.length === 0) {
		$('#overlay').html('<img src="images/empty.png" alt="no data..." /><p>nothing to plot...</p>');
		series.push({}); // add empty dataset to show axes
	}
	else {
		$('#overlay').empty();
	}

	vz.plot = $.plot($('#flot'), series, vz.options.plot);

	// remember legend container for updating
	vz.wui.legend = $('.legend .legendLabel');

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

	// make error messages singleton (suppress follow-on errors)
	vz.wui.errorDialog = true;

	$('<div>').append(
		$('<span>').html(description)
	).dialog({
		title: error,
		width: 450,
		dialogClass: 'ui-error',
		resizable: false,
		modal: true,
		buttons: {
			Ok: function() {
				vz.wui.errorDialog = false;
				$(this).dialog('close');
			}
		}
	});
};

vz.wui.dialogs.exception = function(exception) {
	if (vz.wui.errorDialog) return; // only one error dialog at a time
	this.error(exception.type, exception.message, exception.code);
};

vz.wui.dialogs.middlewareException = function(exception, url) {
	var msg = exception.message;
	if (url) {
		msg = "<a href='" + url + "' style='text-decoration:none'>" + url + "</a>:<br/><br/>" + msg;
	}
	this.exception(new Exception("Middleware Error (" + exception.type + ")", msg));
};
