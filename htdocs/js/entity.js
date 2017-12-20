/**
 * Entity handling, parsing & validation
 *
 * @author Justin Otherguy <justin@justinotherguy.org>
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2017, The volkszaehler.org project
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
 * Entity constructor
 * @var data object properties etc.
 * @var middleware url (if not passed as data attribute)
 */
var Entity = function(data, middleware) {
	this.parseJSON($.extend({
		middleware: middleware
	}, data));
};

/**
 * @var static var to get total count of entity instances
 * Used to choose color
 */
Entity.colors = 0;

/**
 * Parse middleware response (recursive creation of children etc)
 * @var json object from middleware response
 */
Entity.prototype.parseJSON = function(json) {
	$.extend(true, this, json);

	// force axis assignment before plotting
	vz.options.plot.axesAssigned = false;

	// parse children
	if (this.children) {
		for (var i = 0; i < this.children.length; i++) {
			// ensure middleware gets inherited
			this.children[i] = new Entity(this.children[i], this.middleware);
			this.children[i].parent = this;
		}

		this.children.sort(Entity.compare);
	}

	// setting defaults
	if (this.type !== undefined) {
		this.definition = vz.capabilities.definitions.get('entities', this.type);

		if (this.style === undefined) {
			if (this.definition.style) {
				this.style = this.definition.style;
			}
			else {
				this.style = (this.definition.interpreter == 'Volkszaehler\\Interpreter\\SensorInterpreter') ? 'lines' : 'steps';
			}
		}
	}

	if (this.active === undefined || this.active === null) {
		this.active = true; // activate by default
	}

	if (this.color === undefined) {
		this.color = vz.options.plot.colors[Entity.colors++ % vz.options.plot.colors.length];
	}

	// store json data to be extensible by push updates
	if (this.data === undefined) {
		this.data = {
			tuples: [],
			// min, max remain undefined
		};
	}
};

/**
 * Get entity unit
 */
Entity.prototype.getUnit = function() {
	return this.definition.unit || this.unit || "";
};

/**
 * Assign entity an axis with matching unit
 */
Entity.prototype.assignMatchingAxis = function() {
	if (this.definition) {
		// find axis with matching unit
		if (vz.options.plot.yaxes.some(function(yaxis, idx) {
			if (yaxis.axisLabel === undefined || (this.getUnit() == yaxis.axisLabel)) { // unoccupied or matching unit
				this.assignedYaxis = idx + 1;
				return true;
			}
		}, this) === false) { // no more axes available
			this.assignedYaxis = vz.options.plot.yaxes.push($.extend({}, vz.options.plot.yaxes[1]));
		}

		vz.options.plot.yaxes[this.assignedYaxis-1].axisLabel = this.getUnit();
	}
};

/**
 * Allocate y-axis for entity
 */
Entity.prototype.assignAxis = function() {
	// assign y-axis
	if (this.yaxis === undefined || this.yaxis == 'auto') { // auto axis assignment
		this.assignMatchingAxis();
	}
	else { // forced axis assignment
		this.assignedYaxis = parseInt(this.yaxis); // string to int for multi-property

		while (vz.options.plot.yaxes.length < this.assignedYaxis) { // no more axes available
			// create new right-hand axis
			vz.options.plot.yaxes.push($.extend({}, vz.options.plot.yaxes[1]));
		}

		// check if axis already has auto-allocated entities
		var yaxis = vz.options.plot.yaxes[this.assignedYaxis-1];
		if (yaxis.forcedGroup === undefined) { // axis auto-assigned
			if (yaxis.axisLabel !== undefined && this.getUnit() !== yaxis.axisLabel) { // unit mismatch
				// move previously auto-assigned entities to different axis
				yaxis.axisLabel = '*'; // force unit mismatch
				vz.entities.each((function(entity) {
					if (entity.assignedYaxis == this.yaxis && (entity.yaxis === undefined || entity.yaxis == 'auto')) {
						entity.assignMatchingAxis();
					}
				}).bind(this), true); // bind to have callback->this = this
			}
		}

		yaxis.axisLabel = this.getUnit();
		yaxis.forcedGroup = this.yaxis;
	}

	this.updateAxisScale();
};

/**
 * Set axis minimum depending on data
 *
 * Note: axis.min can have the following values:
 *         - undefined: not initialized yet, will only happen during assignment of first entity to axis
 *         - null:      min value intentionally set to 'auto' to allow negative values
 *         - 0:         min value assumed to be '0' as long as no entity with negative values is encountered
 */
Entity.prototype.updateAxisScale = function() {
	if (this.assignedYaxis !== undefined && vz.options.plot.yaxes.length >= this.assignedYaxis) {
		if (vz.options.plot.yaxes[this.assignedYaxis-1].min === undefined) { // axis min still not set
			// avoid overriding user-defined options
			vz.options.plot.yaxes[this.assignedYaxis-1].min = 0;
		}
		if (this.data && this.data.tuples && this.data.tuples.length > 0) {
			// allow negative values, e.g. for temperature sensors
			if (this.data.min && this.data.min[1] < 0) { // set axis min to 'auto'
				vz.options.plot.yaxes[this.assignedYaxis-1].min = null;
			}
		}
	}
};

/**
 * WAMP session subscription and handler
 */
Entity.prototype.subscribe = function(session) {
	var mw = vz.middleware.find(this.middleware);
	if (mw && mw.session) {
		session = session || mw.session;
	}
	if (!session) return;

	session.subscribe(this.uuid, (function(args, json) {
		var push = JSON.parse(json);
		if (!push.data || push.data.uuid !== this.uuid || !vz.wui.tmaxnow) {
			return false;
		}

		// don't collect data if not subscribed
		if (!this.active || this.data === undefined) {
			return;
		}

		var delta = push.data;
		if (delta.tuples && delta.tuples.length) {
			// process updates only if newer than last known timestamp
			var last_ts = (this.data.tuples.length) ? this.data.tuples[this.data.tuples.length-1][0] : 0;
			for (var i=0; i<delta.tuples.length; i++) {
				// find first new timestamp
				if (delta.tuples[i][0] > last_ts) {
					// relevant slice
					var consumption = 0, deltaTuples = delta.tuples.slice(i);
					/* jshint loopfunc: true */
					deltaTuples.forEach((function(el, idx) {
						// min/max
						if (this.data.min === undefined || el[1] < this.data.min[1]) this.data.min = el;
						if (this.data.max === undefined || el[1] > this.data.max[1]) this.data.max = el;
						// consumption
						var tsdiff = (idx === 0) ? el[0] - last_ts : el[0] - deltaTuples[idx-1][0];
						consumption += el[1] * tsdiff;
					}).bind(this)); // bind to entity

					// update consumption
					consumption /= 3.6e6;
					if (this.data.consumption !== undefined) {
						this.data.consumption = (this.data.consumption || 0) + consumption;

						// calculate new left plot border and remove outdated tuples and consumption
						var left = deltaTuples[deltaTuples.length-1][0] - vz.options.plot.xaxis.max + vz.options.plot.xaxis.min;
						while (this.data.tuples.length && this.data.tuples[0][0] < left) {
							var first = this.data.tuples.shift();
							this.data.consumption -= first[1] * (first[0] - this.data.from) / 3.6e6;
							this.data.from = first[0];
						}
					}
					if (this.initialconsumption !== undefined) {
						this.totalconsumption = (this.totalconsumption || 0) + consumption;
					}

					// concatenate in-place
					Array.prototype.push.apply(this.data.tuples, deltaTuples);
					// update UI without reloading totals
					this.dataUpdated();

					vz.wui.zoomToPartialUpdate(deltaTuples[deltaTuples.length-1][0]);
					break;
				}
			}
		}
	}).bind(this)); // bind to Entity
};

/**
 * Cancel live update subscription from WAMP server
 */
Entity.prototype.unsubscribe = function() {
	var mw = vz.middleware.find(this.middleware);
	if (mw && mw.session) {
		try {
			mw.session.unsubscribe(this.uuid);
		}
		catch (e) {
			// handle double unsubscribe, e.g. if channel in multiple groups
			if (!e.match(/^not subscribed to topic/)) {
				throw(e);
			}
		}
	}
};

/**
 * Check if data can be loaded from entity
 */
Entity.prototype.hasData = function() {
	return this.active && this.definition && this.definition.model == 'Volkszaehler\\Model\\Channel';
};

/**
 * Update UI when data changes
 */
Entity.prototype.dataUpdated = function(data) {
	this.updateAxisScale();
	this.updateDOMRow();
};

/**
 * Query middleware for details
 * @return jQuery dereferred object
 */
Entity.prototype.loadDetails = function(skipDefaultErrorHandling) {
	delete this.children; // clear children first
	return vz.load({
		url: this.middleware,
		controller: 'entity',
		identifier: this.uuid,
		context: this
	}, skipDefaultErrorHandling).done(function(json) {
		// fix https://github.com/volkszaehler/volkszaehler.org/pull/560
		delete json.entity.active;
		this.parseJSON(json.entity);
		this.eachChild(function(child) {
			child.active = true;
		}, true); // recursive
	});
};

/**
 * Load data for current view from middleware
 * @return jQuery dereferred object
 */
Entity.prototype.loadData = function() {
	if (!this.hasData()) {
		return $.Deferred().resolve().promise();
	}
	return vz.load({
		controller: 'data',
		url: this.middleware,
		identifier: this.uuid,
		context: this,
		data: {
			from: Math.floor(vz.options.plot.xaxis.min),
			to: Math.ceil(vz.options.plot.xaxis.max),
			tuples: vz.options.group === undefined ? vz.options.tuples : Number.MAX_SAFE_INTEGER,
			group: vz.entities.speedupFactor()
		}
	}).done(function(json) {
		this.data = json.data;
		this.dataUpdated();
	});
};

/**
 * Load total consumption from middleware
 * @return jQuery dereferred object
 */
Entity.prototype.loadTotalConsumption = function() {
	if (this.initialconsumption === undefined) {
		return $.Deferred().resolve().promise();
	}
	return vz.load({
		controller: 'data',
		url: this.middleware,
		identifier: this.uuid,
		context: this,
		data: {
			from: 0,
			tuples: 1,
			group: 'day' // maximum sensible grouping level, first tuple dropped!
		}
	}).done(function(json) {
		// total observed consumption plus initial consumption value
		this.totalconsumption = (this.definition.scale || 1) * this.initialconsumption + json.data.consumption;
		// show in UI
		this.updateDOMRowTotal();
	});
};

/**
 * Show and edit entity details
 */
Entity.prototype.showDetails = function() {
	var entity = this;
	var dialog = $('<div>');

	dialog.addClass('details')
	.append(this.getDOMDetails())
	.dialog({
		title: 'Details für ' + this.title,
		width: 480,
		resizable: false,
		buttons : {
			'Daten': function() {
				var params = $.extend($.getUrlParams(), {
					from: Math.floor(vz.options.plot.xaxis.min),
					to: Math.ceil(vz.options.plot.xaxis.max)
				});
				window.open(entity.middleware + '/data/' + entity.uuid + '.json?' + $.param(params), '_blank');
			},
			'Löschen' : function() {
				$('#entity-delete').dialog({ // confirm prompt
					resizable: false,
					modal: true,
					title: 'Löschen',
					width: 400,
					buttons: {
						'Löschen': function() {
							entity.delete().done(function() {
								entity.cookie = false;
								vz.entities.saveCookie();

								vz.entities.each(function(it, parent) { // remove from tree
									if (entity.uuid == it.uuid) {
										var array = (parent) ? parent.children : vz.entities;
										array.splice(array.indexOf(it), 1); // remove
									}
								}, true);

								vz.entities.showTable();
								vz.wui.drawPlot();
								dialog.dialog('close');
							});

							$(this).dialog('close');
						},
						'Abbrechen': function() {
							$(this).dialog('close');
						}
					}
				});
			},
			'Bearbeiten': function() {
				$('#entity-edit form table .required').remove();
				$('#entity-edit form table .optional').remove();

				// add properties for entity
				vz.capabilities.definitions.entities.some(function(definition) {
					if (definition.name == entity.type) {
						// fix https://github.com/volkszaehler/volkszaehler.org/pull/560
						if (definition.optional.indexOf('active') >= 0) {
							definition.optional.splice(definition.optional.indexOf('active'), 1);
						}
						var container = $('#entity-edit table');
						vz.wui.dialogs.addProperties(container, definition.required, "required", entity);
						vz.wui.dialogs.addProperties(container, definition.optional, "optional", entity);
						return true;
					}
				});

				$('#entity-edit').dialog({
					resizable: false,
					modal: true,
					title: 'Bearbeiten von ' + entity.title,
					width: 600,
					buttons: {
						'Speichern': function() { // adapted from #entity-create
							var properties = {};

							$(this).find('form').serializeArrayWithCheckBoxes().forEach(function(value) {
								if (value.value !== '' || entity[value.name]) {
									properties[value.name] = value.value;
								}
							});

							vz.load({
								controller: 'entity',
								identifier: entity.uuid,
								url: entity.middleware,
								data: properties,
								method: 'PATCH', // edit
							}).done(function(json) {
								entity.parseJSON(json.entity); // update entity
								try {
									vz.entities.showTable();
									vz.entities.loadData().done(vz.wui.drawPlot);
								}
								catch (e) {
									vz.wui.dialogs.exception(e);
								}
								finally {
									$('#entity-edit').dialog('close');
									dialog.dialog('close'); // close parent dialog
								}
							});
						},
						'Abbrechen': function() {
							$(this).dialog('close');
						}
					}
				})
				.keypress(function(ev) {
					// submit form on enter
					if (ev.keyCode == $.ui.keyCode.ENTER) {
						$('#entity-edit').siblings('.ui-dialog-buttonpane').find('button:eq(0)').click();
					}
				});
			},
			'Schließen': function() {
				$(this).dialog('close');
			}
		},
		open: function() {
			$(this).siblings('.ui-dialog-buttonpane').find('button:eq(0)').focus();
		}
	});
};

/**
 * Show channel details for info dialog
 */
Entity.prototype.getDOMDetails = function(edit) {
	var table = $('<table><thead><tr><th>Eigenschaft</th><th>Wert</th></tr></thead></table>');
	var data = $('<tbody>');

	// general properties
	var general = ['uuid', 'middleware', 'type', /*'title', 'color', 'style', 'active',*/ 'cookie'];
	var sections = ['required', 'optional'];

	general.forEach(function(property) {
		var definition = vz.capabilities.definitions.get('properties', property);
		var title = (definition) ? definition.translation[vz.options.language] : property;
		var value = this[property];

		switch (property) {
			case 'type':
				title = 'Typ';
				var icon = this.definition.icon ? $('<img>')
						// attr('src', 'img/types/' + this.definition.icon)
						.attr('src', 'img/blank.png')
						.addClass('icon-' + this.definition.icon.replace('.png', ''))
						.css('margin-right', 4)
					: null;
				value = $('<span>')
					.text(this.definition.translation[vz.options.language])
					.prepend(icon ? icon : null);
				break;

			case 'middleware':
				title = 'Middleware';
				value = '<a href="' + this.middleware + '/capabilities.json">' + this.middleware + '</a>';
				break;

			case 'uuid':
				title = 'UUID';
				value = '<a href="' + this.middleware + '/entity/' + this.uuid + '.json">' + this.uuid + '</a>';
				break;

			case 'cookie':
				title = 'Cookie';
				// value = '<img src="img/' + ((this.cookie) ? 'tick' : 'cross') + '.png" alt="' + ((value) ? 'ja' : 'nein') + '" />';
				value = '<img src="img/blank.png" class="icon-' + ((this.cookie) ? 'tick' : 'cross') + '" alt="' + ((value) ? 'ja' : 'nein') + '" />';
				break;

			case 'active':
				// value = '<img src="img/' + ((this.active) ? 'tick' : 'cross') + '.png" alt="' + ((this.active) ? 'ja' : 'nein') + '" />';
				value = '<img src="img/blank.png" class="icon-' + ((this.active) ? 'tick' : 'cross') + '" alt="' + ((this.active) ? 'ja' : 'nein') + '" />';
				break;

			case 'style':
				switch (this.style) {
					case 'lines': value = 'Linien'; break;
					case 'steps': value = 'Stufen'; break;
					case 'points': value = 'Punkte'; break;
				}
				break;
		}

		data.append($('<tr>')
			.addClass('property')
			.addClass('general')
			.append($('<td>')
				.addClass('key')
				.text(title)
			)
			.append($('<td>')
				.addClass('value')
				.append(value)
			)
		);
	}, this);

	sections.forEach(function(section) {
		this.definition[section].forEach(function(property) {
			if (this.hasOwnProperty(property) && general.indexOf(property) < 0) {
				var definition = vz.capabilities.definitions.get('properties', property);
				var title = definition.translation[vz.options.language];
				var value = this[property];
				var prefix; // unit prefix

				if (definition.type == 'boolean') {
					// value = '<img src="img/' + ((value) ? 'tick' : 'cross') + '.png" alt="' + ((value) ? 'ja' : 'nein') + '" />';
					value = '<img src="img/blank.png" class="icon-' + ((this.active) ? 'tick' : 'cross') + '" alt="' + ((value) ? 'ja' : 'nein') + '" />';
				}

				switch (property) {
					case 'cost':
						prefix = (this.definition.scale == 1000) ? ' ct/k' : ' ct/'; // ct per Wh or kWh
						value = Number(value * 100).toFixed(2) + prefix + vz.wui.formatConsumptionUnit(this.getUnit());
						break;

					case 'resolution':
						prefix = (this.getUnit() && this.definition.scale == 1000) ? 'k' : ''; // per Wh or kWh
						value += '/' + prefix + vz.wui.formatConsumptionUnit(this.getUnit());
						break;

					case 'color':
						value = $('<span>')
							.text(this.color)
							.css('background-color', this.color)
							.css('padding-left', 5)
							.css('padding-right', 5);
						break;

					case 'style':
						switch (this.style) {
							case 'lines': value = 'Linien'; break;
							case 'steps': value = 'Stufen'; break;
							case 'points': value = 'Punkte'; break;
						}
						break;
				}

				data.append($('<tr>')
					.addClass('property')
					.addClass(section)
					.append($('<td>')
						.addClass('key')
						.text(title)
					)
					.append($('<td>')
						.addClass('value')
						.append(value)
					)
				);
			}
		}, this);
	}, this);
	return table.append(data);
};

/**
 * Get DOM for list of entities
 */
Entity.prototype.getDOMRow = function(parent) {
	// full or shortened type name
	var type = this.definition.translation[vz.options.language];
	if (vz.options.shortenLongTypes) type = type.replace(/\s*\(.+?\)/, '');

	var row = $('<tr>')
		.addClass((parent) ? 'child-of-entity-' + parent.uuid : '')
		.addClass((this.definition.model == 'Volkszaehler\\Model\\Aggregator') ? 'aggregator' : 'channel')
		.addClass('entity')
		.attr('id', 'entity-' + this.uuid)
		.append($('<td>')
			.addClass('visibility')
			.css('background-color', this.color)
			.append($('<input>')
				.attr('type', 'checkbox')
				.attr('checked', this.active)
				.bind('change', this, function(event) {
					var entity = event.data;
					entity.activate($(this).prop('checked'), null, true).done(vz.wui.drawPlot);
					vz.entities.saveCookie();
				})
			)
		)
		.append($('<td>').addClass('expander'))
		.append($('<td>')
			.append($('<span>')
				.addClass('indicator')
				.append($('<img>')
					.attr('src', 'img/blank.png')
					.addClass('icon-' + this.definition.icon.replace('.png', ''))
				)
				.append($('<span>')
					.text(this.title)
					// .addClass('indicator')
					// .css('background-image', this.definition.icon ? 'url(img/types/' + this.definition.icon + ')' : null)
				)
			)
		)
		.append($('<td>').addClass('type').text(type)) // channel type
		.append($('<td>').addClass('min'))		// min
		.append($('<td>').addClass('max'))		// max
		.append($('<td>').addClass('average'))		// avg
		.append($('<td>').addClass('last'))		// last value
		.append($('<td>').addClass('consumption'))	// consumption
		.append($('<td>').addClass('cost'))		// costs
		.append($('<td>').addClass('total'))	// total consumption
		.append($('<td>')				// operations
			.addClass('ops')
			.append($('<input>')
				.attr('type', 'image')
				.attr('src', 'img/blank.png')
				.addClass('icon-information')
				.attr('alt', 'details')
				.bind('click', this, function(event) {
					event.data.showDetails();
				})
			)
		)
		.data('entity', this);

	if (this.cookie) {
		$('td.ops', row).prepend($('<input>')
			.attr('type', 'image')
			.attr('src', 'img/blank.png')
			.addClass('icon-delete')
			.attr('alt', 'delete')
			.bind('click', this, function(event) {
				vz.entities.splice(vz.entities.indexOf(event.data), 1); // remove
				vz.entities.saveCookie();
				vz.entities.showTable();
				vz.wui.drawPlot();
			})
		);
	}

	return row;
};

Entity.prototype.activate = function(state, parent, recursive) {
	this.active = state;
	$('#entity-' + this.uuid + ((parent) ? '.child-of-entity-' + parent.uuid : '') + ' input[type=checkbox]').prop('checked', state);

	var queue = [];
	if (this.active) {
		queue.push(this.loadData()); // reload data
		// start live updates
		this.subscribe();
	}
	else {
		this.data = undefined; // clear data
		this.updateDOMRow();
		// stop live updates
		this.unsubscribe();
	}

	if (recursive) {
		this.eachChild(function(child, parent) {
			queue.push(child.activate(state, parent, false));
		}, true); // recursive!
	}

	return $.when.apply($, queue);
};

/**
 * Update UI with current entity values
 */
Entity.prototype.updateDOMRow = function() {
	var row = $('#entity-' + this.uuid);

	// clear table first
	$('.min', row).text('').attr('title', '');
	$('.max', row).text('').attr('title', '');
	$('.average', row).text('');
	$('.last', row).text('');
	$('.consumption', row).text('');
	$('.cost', row).text('');
	// $('.total', row).text('').data('total', null);

	if (this.data && this.data.rows > 0) { // update statistics if data available
		var yearMultiplier = 365*24*60*60*1000 / (this.data.to - this.data.from); // ms
		var unit = this.getUnit();

		// indicate stale data
		if (this.data.to)
			row.toggleClass('stale', vz.options.plot.xaxis.max - this.data.to > (vz.options.plot.stale || 24 * 3.6e6));

		if (this.data.min)
			$('.min', row)
			.text(vz.wui.formatNumber(this.data.min[1], unit))
			.attr('title', $.plot.formatDate(new Date(this.data.min[0]), '%d. %b %y %H:%M:%S', vz.options.monthNames, vz.options.dayNames, true));
		if (this.data.max)
			$('.max', row)
			.text(vz.wui.formatNumber(this.data.max[1], unit))
			.attr('title', $.plot.formatDate(new Date(this.data.max[0]), '%d. %b %y %H:%M:%S', vz.options.monthNames, vz.options.dayNames, true));
		if (this.data.average !== undefined)
			$('.average', row)
			.text(vz.wui.formatNumber(this.data.average, unit));
		if (this.data.tuples && this.data.tuples.length > 0)
			$('.last', row)
			.text(vz.wui.formatNumber(this.data.tuples[this.data.tuples.length-1][1], unit));

		if (this.data.consumption !== undefined) {
			var consumptionUnit = vz.wui.formatConsumptionUnit(unit);
			$('.consumption', row)
				.text(vz.wui.formatNumber(this.data.consumption, consumptionUnit))
				.attr('title', vz.wui.formatNumber(this.data.consumption * yearMultiplier, consumptionUnit) + '/Jahr');
		}

		if (this.cost) {
			var cost = this.cost * this.data.consumption / (this.definition.scale || 1);
			$('.cost', row)
				.data('cost', cost)
				.text(cost.toFixed(2) + ' €')
				.attr('title', (cost * yearMultiplier).toFixed(2) + ' €/Jahr');
		}
		else {
			$('.cost', row).data('cost', 0); // define value if cost property is being removed
		}
	}

	// show total value if populated
	this.updateDOMRowTotal();

	vz.entities.updateTableColumnVisibility();
};

/**
 * Update totals column after async refresh
 */
Entity.prototype.updateDOMRowTotal = function() {
	var row = $('#entity-' + this.uuid);
	if (this.totalconsumption) {
		var unit = vz.wui.formatConsumptionUnit(this.definition.unit);

		$('.total', row)
			.data('total', this.totalconsumption)
			.text(vz.wui.formatNumber(this.totalconsumption, unit, 'k'));
	}
	else {
		$('.total', row).data('total', 0).text('');
	}
};

/**
 * Permanently deletes this entity and its data from the middleware
 */
Entity.prototype.delete = function() {
	return vz.load({
		controller: 'entity',
		context: this,
		identifier: this.uuid,
		url: this.middleware,
		method: 'DELETE'
	});
};

/**
 * Add entity as child
 */
Entity.prototype.addChild = function(child) {
	if (this.definition.model != 'Volkszaehler\\Model\\Aggregator') {
		throw new Exception('EntityException', 'Entity is not an Aggregator');
	}

	return vz.load({
		controller: 'group',
		identifier: this.uuid,
		url: this.middleware,
		method: 'POST',
		data: {
			uuid: child.uuid
		}
	});
};

/**
 * Remove entity from children
 */
Entity.prototype.removeChild = function(child) {
	if (this.definition.model != 'Volkszaehler\\Model\\Aggregator') {
		throw new Exception('EntityException', 'Entity is not an Aggregator');
	}

	delete child.parent;

	return vz.load({
		controller: 'group',
		identifier: this.uuid,
		url: this.middleware,
		method: 'DELETE',
		data: {
			uuid: child.uuid
		}
	});
};

/**
 * Calls the callback function for the entity and all nested children
 *
 * @param cb callback function
 */
Entity.prototype.eachChild = function(cb, recursive) {
	if (this.children) {
		for (var i = 0; i < this.children.length; i++) {
			cb(this.children[i], this);

			if (recursive && this.children[i].children) {
				this.children[i].eachChild(cb, true); // call recursive
			}
		}
	}
};

/**
 * Compares two entities for sorting
 *
 * @static
 * @todo Channels before Aggregators
 */
Entity.compare = function(a, b) {
	if (a.definition === undefined)
		return -1;
	if (b.definition === undefined)
		return 1;
	// Channels before Aggregators
	if (a.definition.model == 'Volkszaehler\\Model\\Channel' && b.definition.model == 'Volkszaehler\\Model\\Aggregator')
		return -1;
	else if (a.definition.model == 'Volkszaehler\\Model\\Aggregator' && b.definition.model == 'Volkszaehler\\Model\\Channel')
		return 1;
	else
		return ((a.title < b.title) ? -1 : ((a.title > b.title) ? 1 : 0));
};
