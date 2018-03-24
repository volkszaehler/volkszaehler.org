/**
 * Javascript functions for the frontend
 *
 * @author Florian Ziegler <fz@f10-home.de>
 * @author Justin Otherguy <justin@justinotherguy.org>
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
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
	vz.wui.resizePlot();

	// resize handling
	$(window).resize(vz.wui.resizePlot);
	$('#accordion h3').click(function() {
		// capture window height before toggling section to avoid miscalculation due to scrollbar flickering
		var windowHeight = $(window).height();
		$(this).next().toggle(0, function() {
			vz.wui.resizePlot(null, windowHeight);
		});
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

    // bind display mode change
	$('#display select').change(function(event) {
		var val = $(this).val().split('-');
		var mode = val.pop();
		var keepPeriodStartFixed = vz.options.mode !== mode;
		vz.wui.changeDisplayMode(mode);
		if (val.length) {
			vz.wui.handleControls('zoom-' + val.pop(), keepPeriodStartFixed);
		}
		else {
			var to = Math.min(new Date().getTime(), vz.options.plot.xaxis.max);
			vz.wui.zoom(vz.options.plot.xaxis.min, to);
		}
	});

	// bind plot actions
	$('#controls button').click(this.handleControls);
	$('#controls').controlgroup();

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
vz.wui.resizePlot = function(evt, windowHeight) {
	// resize container depending on window vs. content height
	var delta = (windowHeight || $(window).height()) - $('html').height();
	$('#flot').height(Math.max($('#flot').height() + delta, vz.options.plot.minHeight || 300));
	vz.options.tuples = Math.round($('#flot').width() / 3);

	if (vz.plot && vz.plot.resize) {
		vz.plot.resize();
		vz.plot.setupGrid();
		vz.plot.draw();
	}
	else {
		// draw empty plot startup
		vz.wui.drawPlot();
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
				$.cachedScript('js/canvas/Blob.js'),
				$.cachedScript('js/canvas/canvas-toBlob.js'),
				$.cachedScript('js/canvas/FileSaver.js'))
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

	// push updates
	entity.subscribe();

	// load data including children
	var queue = [entity.loadData()];

	entity.eachChild(function(child) {
		queue.push(child.loadData());
		child.subscribe();
	}, true); // recursive

	$.when.apply($, queue).then(function() {
		vz.wui.drawPlot();
		entity.loadTotalConsumption().done(vz.entities.updateTableColumnVisibility);
		entity.eachChild(function(child) {
			child.loadTotalConsumption();
		}, true); // recursive
	});
};

/**
 * Initialize dialogs
 */
vz.wui.dialogs.init = function() {
	// add middlewares
	vz.middleware.forEach(function(middleware, idx) {
		$('#entity-public-middleware, #entity-create-middleware').append($('<option>').val(middleware.url).text(middleware.title));
	});

	// initialize dialogs
	$('#entity-add.dialog').dialog({
		title: unescape('Kanal hinzufügen'),
		width: 650,
		resizable: false,
		open: function() {
			// populate and refresh public entities
			var middleware = vz.middleware.find($('#entity-public-middleware option:selected').val());
			populateEntities(middleware);
			middleware.loadEntities().done(function(middleware) {
				populateEntities(middleware);
			});
		}
	});
	$('#entity-add.dialog > div').tabs({
		create: function(ev, ui) {
			$(ui.panel).find('.defaultfocus').focus();
		},
		activate: function(ev, ui) {
			if (ui.newPanel.attr('id') == 'entity-create') {
				$(ui.newPanel).find('input[name=title]').focus();
			}
			else {
				$(ui.newPanel).find('.defaultfocus').focus();
			}
		}
	});

	// show available entity types
	vz.capabilities.definitions.entities.forEach(function(def) {
		$('#entity-create select[name=type]').append(
			$('<option>')
				.html(def.translation[vz.options.language])
				.data('definition', def)
				.val(def.name)
				.css('background-image', def.icon ? 'url(img/types/' + def.icon : null)
		);
	});
	$('#entity-create option[value=power]').attr('selected', 'selected');

	// set defaults
	$('#entity-subscribe-middleware').val(vz.options.middleware[0].url);
	$('#entity-subscribe-cookie').attr('checked', 'checked');
	$('#entity-public-cookie').attr('checked', 'checked');

	// actions
	$('#entity-public-middleware').change(function() {
		populateEntities(vz.middleware.find($('#entity-public-middleware option:selected').val()));
	});

	$('#entity-subscribe form').submit(function() {
		if (!$('#entity-subscribe-uuid').val()) {
			return false;
		}

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

		return false;
	});

	$('#entity-public form').submit(function() {
		// clone entity from data attribute and activate it
		var entity = $.extend({}, $('#entity-public-entity option:selected').data('entity'));
		if ($.isEmptyObject(entity)) return;

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

		return false;
	});

	function populateEntities(middleware) {
		$('#entity-public-entity').empty();
		middleware.public.forEach(function(entity) {
			$('#entity-public-entity').append(
				$('<option>').html(entity.title).val(entity.uuid).data('entity', entity)
			);
		});
	}

	$('#entity-create select').change(function() {
		$('#entity-create .required, #entity-create .optional').remove();

		var container = $('#entity-create table');
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
		$(this).serializeArray().forEach(function(value) {
			if (value.value !== '') {
				properties[value.name] = value.value;
			}
		});

		vz.load({
			controller: (def.model !== 'Volkszaehler\\Model\\Aggregator') ? 'channel' : 'aggregator',
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

				// show the channel UUID to the user
				$('<div>').append(
					$('<p>').html(entity.uuid)
				).dialog({
					title: 'Kanal UUID',
					width: 450,
					resizable: false,
					modal: true,
					buttons: {
						Ok: function() {
							$(this).dialog('close');
							$(this).remove();
						}
					}
				});
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
 * Initialize authorization dialog
 */
vz.wui.initAuth = function() {
	$('#authorization').dialog({
		title: unescape('Login'),
		width: 650,
		modal: true,
		autoOpen: false,
		resizable: false,
		open: function() {
			var middleware = $('#authorization').data('middleware');
			$('#authorization .middleware').text(vz.getMiddleware(middleware).title);
			$('#authorization input[name=username]').select();
		}
	});

	$('#authorization').submit(function(e) {
		var middleware = $('#authorization').data('middleware');
		var args = {
			url: middleware + '/auth.json',
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify({
				username: $('#authorization input[name=username]').val(),
				password: $('#authorization input[name=password]').val()
			})
		};

		$.ajax(args).then(
			function(json) {
				$('#authorization').dialog('close');

				if (json.authtoken) {
					vz.setMiddlewareAuth(middleware, json.authtoken);
					window.location.reload(false);	// reload
				}
			},
			function(xhr) {
				vz.wui.dialogs.middlewareException({
					type: "Login Failure",
					message: xhr.statusText
				}, xhr.requestUrl);
			}
		);

		return false;
	});
};

/**
 * Show available properties for selected type
 */
vz.wui.dialogs.addProperties = function(container, proplist, className, entity) {
	proplist.forEach(function(def) {
		// hide properties from blacklist
		var val = (entity && typeof entity[def] !== undefined) ? entity[def] : null;
		if ((typeof val === 'undefined' || val === null) && vz.options.hiddenProperties.indexOf(def) >= 0) {
			return; // hide less commonly used properties
		}

		vz.capabilities.definitions.properties.forEach(function(propdef, propindex) {
			if (def == propdef.name) {
				var cntrl = null;
				var row = $('<tr>').append(
					$('<td>').text(propdef.translation[vz.options.language])
				);

				switch (propdef.type) {
					case 'float':
					case 'integer':
					case 'string':
						cntrl = $('<input>').attr("type", "text");
						break;

					case 'text':
						cntrl = $('<textarea>');
						break;

					case 'boolean':
						cntrl = $('<input>').attr("type", "checkbox").val("1"); // boolean value
						break;

					case 'multiple':
						cntrl = $('<select>');
						propdef.options.forEach(function(optdef, optindex) {
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
						$.cachedScript('js/jquery/jquery.simple-color.min.js').done(function() {
							// cntrl.attr('id', 'colorValue');
							cntrl.simpleColor({
								cellWidth: 18,
								cellHeight: 18,
								chooserCSS: { "border-color": "#a7a7a7", "z-index": 20 }, // above slider
								displayCSS: { "border-color": "#a7a7a7" }, // similar to style.css
								boxWidth: 50,
								columns: 19,
								colors: ['ffebee','fce4ec','f3e5f5','ede7f6','e8eaf6','e3f2fd','e1f5fe','e0f7fa','e0f2f1','e8f5e9','f1f8e9','f9fbe7','fffde7','fff8e1','fff3e0','fbe9e7','efebe9','eceff1','ffffff','ffcdd2','f8bbd0','e1bee7','d1c4e9','c5cae9','bbdefb','b3e5fc','b2ebf2','b2dfdb','c8e6c9','dcedc8','f0f4c3','fff9c4','ffecb3','ffe0b2','ffccbc','d7ccc8','cfd8dc','f5f5f5','ef9a9a','f48fb1','ce93d8','b39ddb','9fa8da','90caf9','81d4fa','80deea','80cbc4','a5d6a7','c5e1a5','e6ee9c','fff59d','ffe082','ffcc80','ffab91','bcaaa4','b0bec5','e0e0e0','e57373','f06292','ba68c8','9575cd','7986cb','64b5f6','4fc3f7','4dd0e1','4db6ac','81c784','aed581','dce775','fff176','ffd54f','ffb74d','ff8a65','a1887f','90a4ae','bdbdbd','ef5350','ec407a','ab47bc','7e57c2','5c6bc0','42a5f5','29b6f6','26c6da','26a69a','66bb6a','9ccc65','d4e157','ffee58','ffca28','ffa726','ff7043','8d6e63','78909c','9e9e9e','f44336','e91e63','9c27b0','673ab7','3f51b5','2196f3','03a9f4','00bcd4','009688','4caf50','8bc34a','cddc39','ffeb3b','ffc107','ff9800','ff5722','795548','607d8b','757575','e53935','d81b60','8e24aa','5e35b1','3949ab','1e88e5','039be5','00acc1','00897b','43a047','7cb342','c0ca33','fdd835','ffb300','fb8c00','f4511e','6d4c41','546e7a','616161','d32f2f','c2185b','7b1fa2','512da8','303f9f','1976d2','0288d1','0097a7','00796b','388e3c','689f38','afb42b','fbc02d','ffa000','f57c00','e64a19','5d4037','455a64','424242','c62828','ad1457','6a1b9a','4527a0','283593','1565c0','0277bd','00838f','00695c','2e7d32','558b2f','9e9d24','f9a825','ff8f00','ef6c00','d84315','4e342e','37474f','212121','b71c1c','880e4f','4a148c','311b92','1a237e','0d47a1','01579b','006064','004d40','1b5e20','33691e','827717','f57f17','ff6f00','e65100','bf360c','3e2723','263238','000000']
							});
						});
						break;
				}

				if (cntrl !== null) {
					container.append(
						row
							.addClass(className)
							.append($('<td>').append(
								cntrl.attr("name", propdef.name)
							))
					);
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
			vz.wui.period = null;
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
			vz.options.plot.hoverPos = pos;
			if (vz.entities && vz.entities.length && !vz.wui.updateLegendTimeout) {
				vz.wui.updateLegendTimeout = setTimeout(function() {
					if (vz.wui.updateLegendTimeout) {
						vz.wui.updateLegend(vz.options.plot.hoverPos, item);
					}
				}, 50);
			}
		})
		.bind("plotleave", vz.wui.plotLeave);
};

/**
 * Move & zoom in the plotting area
 *
 * @param ev either click event or literal button event value
 */
vz.wui.handleControls = function(action, keepPeriodStartFixed) {
	var delta = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min,
			middle = vz.options.plot.xaxis.min + delta/2,
			startOfPeriodLocale;

	var control = typeof action == 'string' ? action : $(this).val();

	switch (control) {
		case 'move-last':
			startOfPeriodLocale = vz.wui.period == 'week' ? 'isoweek' : vz.wui.period;
			vz.wui.zoom(
				/* jshint laxbreak: true */
				vz.wui.period && moment(vz.options.plot.xaxis.min)
					.startOf(startOfPeriodLocale)
					.isSame(moment(vz.options.plot.xaxis.min))
					? moment().startOf(startOfPeriodLocale).valueOf()
					: moment().valueOf() - delta,
				moment().valueOf()
			);
			break;
		case 'move-back':
			if (vz.wui.period) {
				vz.wui.zoom(
					moment(vz.options.plot.xaxis.min).subtract(1, vz.wui.period).valueOf(),
					vz.options.plot.xaxis.min
				);
			}
			else {
				vz.wui.zoom(
					vz.options.plot.xaxis.min - delta,
					vz.options.plot.xaxis.max - delta
				);
			}
			break;
		case 'move-forward':
			// don't move into the future
			if (vz.wui.tmaxnow)
				break;
			if (vz.wui.period) {
				vz.wui.zoom(
					vz.options.plot.xaxis.max,
					Math.min(
						// prevent adjusting left boundary in zoom function
						moment(vz.options.plot.xaxis.max).add(1, vz.wui.period).valueOf(),
						moment().valueOf()
					)
				);
			}
			else {
				vz.wui.zoom(
					vz.options.plot.xaxis.min + delta,
					vz.options.plot.xaxis.max + delta
				);
			}
			break;
		case 'zoom-reset':
			vz.wui.period = null;
			vz.wui.zoom(
				middle - vz.options.defaultInterval/2,
				middle + vz.options.defaultInterval/2
			);
			break;
		case 'zoom-in':
			vz.wui.period = null;
			if (vz.wui.tmaxnow)
				vz.wui.zoom(moment().valueOf() - delta/2, moment().valueOf());
			else
				vz.wui.zoom(middle - delta/4, middle + delta/4);
			break;
		case 'zoom-out':
			vz.wui.period = null;
			vz.wui.zoom(
				middle - delta,
				middle + delta
			);
			break;
		case 'zoom-hour':
		case 'zoom-day':
		case 'zoom-week':
		case 'zoom-month':
		case 'zoom-year':
			var period = control.split('-')[1], min, max;
			periodLocale = period == 'week' ? 'isoweek' : period;

			if (vz.wui.tmaxnow) {
				/* jshint laxbreak: true */
				min = period === vz.wui.period &! keepPeriodStartFixed
					? moment().subtract(1, period).valueOf()
					: moment().startOf(periodLocale).valueOf();
				max = moment().valueOf();
			}
			else {
				min = moment(middle).startOf(periodLocale).valueOf();
				max = moment(middle).startOf(periodLocale).add(1, period).valueOf();
			}

			vz.wui.period = period;
			vz.wui.zoom(min, Math.min(max, moment().valueOf()));
			break;
	}
};

/**
 * Timestamp rounding for group mode
 */
vz.wui.adjustTimestamp = function(ts, middle) {
	var periodLocale = vz.options.mode == 'week' ? 'isoweek' : vz.options.mode;
	ts = moment(ts);

	/* jshint laxbreak: true */
	ts = middle
		? moment((ts.startOf(periodLocale).valueOf() + ts.endOf(periodLocale).valueOf()) / 2)
		: ts.startOf(periodLocale);

	return ts.valueOf();
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

	if (vz.wui.isConsumptionMode()) {
		vz.options.plot.xaxis.min = vz.wui.adjustTimestamp(vz.options.plot.xaxis.min);
		vz.options.plot.xaxis.max = vz.wui.adjustTimestamp(vz.options.plot.xaxis.max);

		var periodLocale = vz.options.mode == 'week' ? 'isoweek' : vz.options.mode;
		if (moment(vz.options.plot.xaxis.max).endOf(periodLocale).isAfter(moment())) {
			vz.options.plot.xaxis.max = moment(vz.options.plot.xaxis.max).add(1, periodLocale).valueOf();
		}
	}

	vz.wui.tmaxnow = (vz.options.plot.xaxis.max >= (now - 1000));

	if (vz.options.plot.xaxis.min < 0) {
		vz.options.plot.xaxis.min = 0;
	}

	vz.entities.loadData().done(vz.wui.drawPlot);
};

/**
 * Handle display mode control
 */
vz.wui.changeDisplayMode = function(mode) {
	$("#display option[value=" + mode + "]").prop('selected', 'selected');
	vz.options.mode = mode;
	vz.options.plot.axesAssigned = false; // force axis assignment
};

/**
 * Get consumption mode
 */
vz.wui.isConsumptionMode = function() {
	return (vz.options.mode && vz.options.mode != 'current');
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
 * Determine matching SI unit prefix
 */
vz.wui.scaleNumberAndUnit = function(number, unit, maxPrefix) {
	var siPrefixes = ['', 'k', 'M', 'G', 'T'],
			siIndex = 0,
			scaler = 1;
	var siMaxIndex = maxPrefix ? siPrefixes.indexOf(maxPrefix) :
			(maxPrefix === false ? 0 : siPrefixes.length-1);

	// flow unit or air pressure?
	if (['h', 'l', 'm3', 'm^3', 'm³', 'l/h', 'm3/h', 'm/h^3', 'm³/h', '°C', 'K', 'hPa', 'cd', 'km/h', 'u/min', 'rpm'].indexOf(unit) >= 0) {
		// don't scale...
		siMaxIndex = 0;

		// ...unless for l->m3 conversion
		if ((unit == 'l' || unit == 'l/h') && Math.abs(number) > 1000) {
			unit = 'm³' + unit.substring(1);
			number /= 1000;
		}
	}

	while (siIndex < siMaxIndex && Math.abs(number) > 1000) {
		scaler /= 1000;
		number /= 1000;
		siIndex++;
	}

	return {
		unit: unit,
		prefix: siPrefixes[siIndex],
		number: number,
		scaler: scaler
	};
};

/**
 * Number formatting
 *
 * vz.options.precision determines precision for single-digit numbers
 * Numbers with more significant digits are displayed at lower precision
 *
 * @param number to round
 * @param SI unit to use- may be adjusted in case of l/m3
 * @param maximum SI unit prefix to use (or false to disable SI scaling)
 */
vz.wui.formatNumber = function(number, unit, maxPrefix) {
	// determine si unit and adjust value
	var si = vz.wui.scaleNumberAndUnit(number, unit, maxPrefix);

	// precision, +1 digit for numbers < 1.0
	var precision = (Math.abs(si.number) < vz.options.minNumber) ? 0 :
		 Math.max(0, vz.options.precision - Math.max(-1, Math.floor(Math.log(Math.abs(si.number))/Math.LN10)));

	if (vz.options.maxPrecision[unit] !== undefined) {
		precision = Math.min(vz.options.maxPrecision[unit], precision);
	}

	// rounding, si prefix and unit
	number = si.number.toFixed(precision);
	if (si.unit || si.prefix) {
		number += ' ' + si.prefix + si.unit;
	}

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
 * Update headline on zoom
 */
vz.wui.updateHeadline = function() {
	var delta = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min,
			format = '%a %e. %b %Y',
			from = vz.options.plot.xaxis.min,
			to = vz.options.plot.xaxis.max;

	if (delta < 3*24*3600*1000) {
		format += ' %H:%M'; // under 3 days
		if (delta < 5*60*1000) format += ':%S'; // under 5 minutes
	}
	else {
		// formatting days only- remove 1ms to display previous day as "to"
		to--;
	}

	// timezone-aware dates if timezone-js is included
	from = $.plot.formatDate(
		$.plot.dateGenerator(from, vz.options.plot.xaxis),
		format, vz.options.monthNames, vz.options.dayNames, true
	);
	to = $.plot.formatDate(
		$.plot.dateGenerator(to, vz.options.plot.xaxis),
		format, vz.options.monthNames, vz.options.dayNames, true
	);

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

	var series = [];
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
			var linewidth = parseFloat(vz.options.linewidth || vz.options[entity.uuid == vz.wui.selectedChannel ? 'lineWidthSelected' : 'lineWidthDefault']);

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

			series.push(serie);
		}
	}, true);

	if (series.length === 0) {
		$('#overlay').html('<img src="img/empty.png" alt="no data..." /><p>nothing to plot...</p>');
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
	if (code) {
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
		close: function() {
			vz.wui.errorDialog = false;
		},
		buttons: {
			Ok: function() {
				$(this).dialog('close');
			}
		},
		open: function() {
			$(this).next().find('button:eq(0)').focus();
		}
	});
};

vz.wui.dialogs.exception = function(exception) {
	if (vz.wui.errorDialog) return; // only one error dialog at a time
	this.error(exception.type, exception.message, exception.code);
};

vz.wui.dialogs.middlewareException = function(xhr) {
	if (xhr.responseJSON && xhr.responseJSON.exception)
		// middleware exception
		this.exception(new Exception(xhr.responseJSON.exception.type, "<a href='" + xhr.requestUrl + "' style='text-decoration:none'>" + xhr.requestUrl + "</a>:<br/><br/>" + xhr.responseJSON.exception.message));
	else
		// network exception
		this.exception(new Exception("Network Error", xhr.statusText));
};
