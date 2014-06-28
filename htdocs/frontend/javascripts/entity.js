/**
 * Entity handling, parsing & validation
 *
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
 * Entity constructor
 * @var data object properties etc.
 */
var Entity = function(data) {
	this.parseJSON(data);
};

/**
 * @var static var to get total count of entity instances
 * Used to choose color
 */
Entity.colors = 0;

/**
 * Parse middleware response (recursive creation of children etc)
 * @var object from middleware response
 */
Entity.prototype.parseJSON = function(json) {
	$.extend(true, this, json);

	// force axis assignment before plotting
	vz.options.plot.axesAssigned = false;

	// parse children
	if (this.children) {
		for (var i = 0; i < this.children.length; i++) {
			// @todo check if setting middleware is really possible here
			this.children[i].middleware = this.middleware; // children inherit parent middleware
			this.children[i] = new Entity(this.children[i]);
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

	if (this.active === undefined) {
		this.active = true; // activate by default
	}

	if (this.color === undefined) {
		this.color = vz.options.plot.colors[Entity.colors++ % vz.options.plot.colors.length];
	}
};

/**
 * Set middleware on entity and and inherit to children
 * @var middleware url
 */
Entity.prototype.setMiddleware = function(middleware) {
	this.middleware = middleware;
	this.each(function(child, parent) {
		child.middleware = middleware;
	}, true); // recursive!
};

/**
 * Assign entity an axis with matching unit
 */
Entity.prototype.assignMatchingAxis = function() {
	if (this.definition) {
		// find axis with matching unit
		if (vz.options.plot.yaxes.some(function(yaxis, idx) {
			if (yaxis.axisLabel == undefined || (this.definition.unit == yaxis.axisLabel)) { // unoccupied or matching unit
				this.assignedYaxis = idx + 1;
				return true;
			}
		}, this) === false) { // no more axes available
			this.assignedYaxis = vz.options.plot.yaxes.push({ position: 'right' });
		}

		vz.options.plot.yaxes[this.assignedYaxis-1].axisLabel = this.definition.unit;
	}
};

/**
 * Allocate y-axis for entity
 */
Entity.prototype.assignAxis = function() {
	// assign y-axis
	if (this.yaxis == undefined || this.yaxis == 'auto') { // auto axis assignment
		this.assignMatchingAxis();
	}
	else { // forced axis assignment
		this.assignedYaxis = parseInt(this.yaxis); // string to int for multi-property

		while (vz.options.plot.yaxes.length < this.assignedYaxis) { // no more axes available
			vz.options.plot.yaxes.push({ position: 'right' });
		}

		// check if axis already has auto-allocated entities
		var yaxis = vz.options.plot.yaxes[this.assignedYaxis-1];
		if (yaxis.forcedGroup == undefined) { // axis auto-assigned
			if (yaxis.axisLabel !== undefined && this.definition.unit !== yaxis.axisLabel) { // unit mismatch
				// move previously auto-assigned entities to different axis
				yaxis.axisLabel = '*'; // force unit mismatch
				vz.entities.each((function(entity) {
					if (entity.assignedYaxis == this.yaxis && (entity.yaxis == undefined || entity.yaxis == 'auto')) {
						entity.assignMatchingAxis();
					}
				}).bind(this), true); // bind to have callback->this = this
			}
		}

		yaxis.axisLabel = this.definition.unit;
		yaxis.forcedGroup = this.yaxis;
	}

	this.updateAxisScale();
};

/**
 * Set axis minimum depending on data
 */
Entity.prototype.updateAxisScale = function() {
	if (this.assignedYaxis !== undefined && vz.options.plot.yaxes.length >= this.assignedYaxis) {
		if (vz.options.plot.yaxes[this.assignedYaxis-1].min === null) {
			// avoid overriding user-defined options
			vz.options.plot.yaxes[this.assignedYaxis-1].min = 0;
		}
		if (this.data && this.data.tuples && this.data.tuples.length > 0) {
			// allow negative values, e.g. for temperature sensors
			if (this.data.min && this.data.min[1] < 0) {
				vz.options.plot.yaxes[this.assignedYaxis-1].min = null;
			}
		}
	}
};

/**
 * Query middleware for details
 * @return jQuery dereferred object
 */
Entity.prototype.loadDetails = function() {
	delete this.children; // clear children first
	return vz.load({
		url: this.middleware,
		controller: 'entity',
		identifier: this.uuid,
		context: this,
		success: function(json) {
			this.parseJSON(json.entity);
		}
	});
};

/**
 * Update entity data from middleware result and set axes accordingly
 */
Entity.prototype.updateData = function(data) {
	this.data = data;

	this.updateAxisScale();
	this.updateDOMRow();

	// load totals whenever data changes - this happens async to updateDOMRow()
	if (this.initialconsumption !== undefined) {
		this.loadTotalConsumption();
	}
};

/**
 * Load total consumption from middleware
 * @return jQuery dereferred object
 */
Entity.prototype.loadTotalConsumption = function() {
	return vz.load({
		controller: 'data',
		url: this.middleware,
		identifier: this.uuid,
		context: this,
		data: {
			from: 0,
			tuples: 1,
			group: 'month' // maximum sensible grouping level
		},
		success: function(json) {
			var consumption = 1000 * this.initialconsumption + json.data.consumption;

			var row = $('#entity-' + this.uuid);
			$('.total', row)
				.data('total', consumption)
				.text(vz.wui.formatNumber(consumption, 'k') + vz.wui.formatConsumptionUnit(this.definition.unit));

			// unhide total column
			vz.entities.updateTable();
		}
	});
};

/**
 * Load data for current view from middleware
 * @return jQuery dereferred object
 */
Entity.prototype.loadData = function() {
	return vz.load({
		controller: 'data',
		url: this.middleware,
		identifier: this.uuid,
		context: this,
		data: {
			from: Math.floor(vz.options.plot.xaxis.min),
			to: Math.ceil(vz.options.plot.xaxis.max),
			tuples: vz.options.tuples,
			group: vz.entities.speedupFactor()
		},
		success: function(json) {
			this.updateData(json.data);
		}
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
										array.remove(it);
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
				vz.capabilities.definitions.entities.some(function(entities) {
					if (entities.name == entity.type) {
						var container = $('#entity-edit form table');
						vz.wui.dialogs.addProperties(container, entities.required, "required", entity);
						vz.wui.dialogs.addProperties(container, entities.optional, "optional", entity);
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

							$(this).find('form').serializeArrayWithCheckBoxes().each(function(index, value) {
								if (value.value !== '' || entity[value.name]) {
									properties[value.name] = value.value;
								}
							});

							vz.load({
								controller: 'entity',
								identifier: entity.uuid,
								url: entity.middleware,
								data: properties,
								type: 'PULL', // edit
								success: function(json) {
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
								}
							});
						},
						'Abbrechen': function() {
							$(this).dialog('close');
						}
					}
				});
			},
			'Schließen': function() {
				$(this).dialog('close');
			}
		}
	});
};

/**
 * Show from for new Channel
 * used to create info dialog
 */
Entity.prototype.getDOMDetails = function(edit) {
	var table = $('<table><thead><tr><th>Eigenschaft</th><th>Wert</th></tr></thead></table>');
	var data = $('<tbody>');

	// general properties
	var general = ['uuid', 'middleware', 'type', /*'title', 'color', 'style', 'active',*/ 'cookie'];
	var sections = ['required', 'optional'];

	general.each(function(index, property) {
		var definition = vz.capabilities.definitions.get('properties', property);
		var title = (definition) ? definition.translation[vz.options.language] : property;
		var value = this[property];

		switch (property) {
			case 'type':
				title = 'Typ';
				var icon = this.definition.icon ? $('<img>')
						// attr('src', 'images/types/' + this.definition.icon)
						.attr('src', 'images/blank.png')
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
				// value = '<img src="images/' + ((this.cookie) ? 'tick' : 'cross') + '.png" alt="' + ((value) ? 'ja' : 'nein') + '" />';
				value = '<img src="images/blank.png" class="icon-' + ((this.cookie) ? 'tick' : 'cross') + '" alt="' + ((value) ? 'ja' : 'nein') + '" />';
				break;

			case 'active':
				// value = '<img src="images/' + ((this.active) ? 'tick' : 'cross') + '.png" alt="' + ((this.active) ? 'ja' : 'nein') + '" />';
				value = '<img src="images/blank.png" class="icon-' + ((this.active) ? 'tick' : 'cross') + '" alt="' + ((this.active) ? 'ja' : 'nein') + '" />';
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

	sections.each(function(index, section) {
		this.definition[section].each(function(index, property) {
			if (this.hasOwnProperty(property) && !general.contains(property)) {
				var definition = vz.capabilities.definitions.get('properties', property);
				var title = definition.translation[vz.options.language];
				var value = this[property];

				if (definition.type == 'boolean') {
					// value = '<img src="images/' + ((value) ? 'tick' : 'cross') + '.png" alt="' + ((value) ? 'ja' : 'nein') + '" />';
					value = '<img src="images/blank.png" class="icon-' + ((this.active) ? 'tick' : 'cross') + '" alt="' + ((value) ? 'ja' : 'nein') + '" />';
				}

				switch (property) {
					case 'cost':
						if (this.definition.unit == 'W') {
							value = Number(value * 1000 * 100).toFixed(2) + ' ct/k' + vz.wui.formatConsumptionUnit(this.definition.unit); // ct per kWh
						}
						else {
							value = Number(value * 100).toFixed(2) + ' ct/' + vz.wui.formatConsumptionUnit(this.definition.unit); // ct per m3 etc
						}
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
	var row =  $('<tr>')
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
				})
			)
		)
		.append($('<td>').addClass('expander'))
		.append($('<td>')
			.append($('<span>')
				.addClass('indicator')
				.append($('<img>')
					.attr('src', 'images/blank.png')
					.addClass('icon-' + this.definition.icon.replace('.png', ''))
				)
				.append($('<span>')
					.text(this.title)
					// .addClass('indicator')
					// .css('background-image', this.definition.icon ? 'url(images/types/' + this.definition.icon + ')' : null)
				)
			)
		)
		.append($('<td>').text(this.definition.translation[vz.options.language])) // channel type
		.append($('<td>').addClass('min'))		// min
		.append($('<td>').addClass('max'))		// max
		.append($('<td>').addClass('average'))		// avg
		.append($('<td>').addClass('last'))		// last value
		.append($('<td>').addClass('consumption'))	// consumption
		.append($('<td>').addClass('total'))	// total consumption
		.append($('<td>').addClass('cost'))		// costs
		.append($('<td>')				// operations
			.addClass('ops')
			.append($('<input>')
				.attr('type', 'image')
				.attr('src', 'images/blank.png')
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
			.attr('src', 'images/blank.png')
			.addClass('icon-delete')
			.attr('alt', 'delete')
			.bind('click', this, function(event) {
				vz.entities.remove(event.data);
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
	var queue = [];

	$('#entity-' + this.uuid + ((parent) ? '.child-of-entity-' + parent.uuid : '') + ' input[type=checkbox]').prop('checked', state);

	if (this.active) {
		queue.push(this.loadData()); // reload data
	}
	else {
		this.data = undefined; // clear data
		this.updateDOMRow();
	}

	if (recursive) {
		this.each(function(child, parent) {
			queue.push(child.activate(state, parent, true));
		}, true); // recursive!
	}

	return $.when.apply($, queue);
};

Entity.prototype.updateDOMRow = function() {
	var row = $('#entity-' + this.uuid);

	// clear table first
	$('.min', row).text('').attr('title', '');
	$('.max', row).text('').attr('title', '');
	$('.average', row).text('');
	$('.last', row).text('');
	$('.consumption', row).text('');
	$('.cost', row).text('');
	$('.total', row).text('').data('total', null);

	if (this.data && this.data.rows > 0) { // update statistics if data available
		var delta = this.data.to - this.data.from;
		var year = 365*24*60*60*1000;

		if (this.data.min)
			$('.min', row)
			.text(vz.wui.formatNumber(this.data.min[1], true) + this.definition.unit)
			.attr('title', $.plot.formatDate(new Date(this.data.min[0]), '%d. %b %y %H:%M:%S', vz.options.monthNames, vz.options.dayNames, true));
		if (this.data.max)
			$('.max', row)
			.text(vz.wui.formatNumber(this.data.max[1], true) + this.definition.unit)
			.attr('title', $.plot.formatDate(new Date(this.data.max[0]), '%d. %b %y %H:%M:%S', vz.options.monthNames, vz.options.dayNames, true));
		if (this.data.average)
			$('.average', row)
			.text(vz.wui.formatNumber(this.data.average, true) + this.definition.unit);
		if (this.data.tuples && this.data.tuples.last)
			$('.last', row)
			.text(vz.wui.formatNumber(this.data.tuples.last()[1], true) + this.definition.unit);

		if (this.data.consumption) {
			var unit = vz.wui.formatConsumptionUnit(this.definition.unit);
			$('.consumption', row)
				.text(vz.wui.formatNumber(this.data.consumption, true) + unit)
				.attr('title', vz.wui.formatNumber(this.data.consumption * (year/delta), true) + unit + '/Jahr');
		}

		if (this.cost) {
			$('.cost', row)
				.data('cost', this.cost * this.data.consumption)
				.text(vz.wui.formatNumber(this.cost * this.data.consumption) + ' €')
				.attr('title', vz.wui.formatNumber(this.cost * this.data.consumption * (year/delta)) + ' €/Jahr');
		}
		else {
			$('.cost', row).data('cost', 0); // define value if cost property is being removed
		}
	}

	vz.entities.updateTable();
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
		type: 'DELETE'
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
		type: 'POST',
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
		type: 'DELETE',
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
Entity.prototype.each = function(cb, recursive) {
	if (this.children) {
		for (var i = 0; i < this.children.length; i++) {
			cb(this.children[i], this);

			if (recursive && this.children[i] !== undefined) {
				this.children[i].each(cb, true); // call recursive
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
