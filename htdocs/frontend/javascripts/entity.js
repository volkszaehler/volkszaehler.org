/**
 * Entity handling, parsing & validation
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
 * Entity constructor
 * @todo add validation
 */
var Entity = function(json, parent) {
	$.extend(true, this, json);
	this.parent = parent;
	
	if (this.active === undefined) {
		this.active = true; // active by default
	}
	
	if (this.children) {
		for (var i = 0; i < this.children.length; i++) {
			this.children[i] = new Entity(this.children[i], this);
		};
		
		this.children.sort(Entity.compare);
	}

	this.definition = vz.capabilities.definitions.get('entities', this.type);
};

/**
 * Show and edit entity details
 */
Entity.prototype.showDetails = function() {
	$('<div>')
	.addClass('details')
	.append(this.getDOM())
	.dialog({
		title: 'Details f&uuml;r ' + this.title,
		width: 480,
		resizable: false
	});
};

/**
 * Show from for new Channel
 * 
 * @todo implement/test
 */
Entity.prototype.getDOM = function(edit) {
	var table = $('<table><thead><tr><th>Eigenschaft</th><th>Wert</th></tr></thead></table>');
	var data = $('<tbody>');

	for (var property in this) {
		if (this.hasOwnProperty(property) && !['data', 'definition', 'children', 'parent'].contains(property)) {
			switch(property) {
				case 'type':
					var title = 'Typ';
					var value = this.definition.translation[vz.options.language];
					break;
					
				case 'uuid':
					var title = 'UUID';
					var value = '<a href="' + vz.options.backendUrl + '/entity/' + this[property] + '.json">' + this[property] + '</a>';
					break;
			
				case 'color':
					var title = 'Farbe';
					var value = '<span style="background-color: ' + this[property] + '">' + this[property] + '</span>';
					break;
					
				case 'public':
					var title = vz.capabilities.definitions.get('properties', property).translation[vz.options.language];
					var value = (this[property]) ? 'ja' : 'nein';
					break;
					
			
				case 'active':
					var title = 'Aktiv';
					var value = (this[property]) ? 'ja' : 'nein';
					break;

				default:
					var title = vz.capabilities.definitions.get('properties', property).translation[vz.options.language];
					var value = this[property];
			}

			data.append($('<tr>')
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
	}
	return table.append(data);
};

Entity.prototype.getRow = function() {
	var row =  $('<tr>')
		.addClass((this.parent) ? 'child-of-entity-' + this.parent.uuid : '')
		.addClass((this.definition.model == 'Volkszaehler\\Model\\Aggregator') ? 'aggregator' : 'channel')
		.attr('id', 'entity-' + this.uuid)
		.append($('<td>')
			.addClass('visibility')
			.css('background-color', this.color)
			.append($('<input>')
				.attr('type', 'checkbox')
				.attr('checked', this.active)
				.bind('change', this, function(event) {
					var state = $(this).attr('checked');
					
					event.data.each(function(entity, parent) {
						$('#entity-' + entity.uuid + ((parent) ? '.child-of-entity-' + parent.uuid : '') + ' input[type=checkbox]')
						.attr('checked', state);
						entity.active = state;
					});
					
					vz.wui.drawPlot();
				})
			)
		)
		.append($('<td>').addClass('expander'))
		.append($('<td>')
			.append($('<span>')
				.text(this.title)
				.addClass('indicator')
				.css('background-image', 'url(images/types/' + this.definition.icon + ')')
			)
		)
		.append($('<td>').text(this.definition.translation[vz.options.language])) // channel type
		.append($('<td>').addClass('min'))		// min
		.append($('<td>').addClass('max'))		// max
		.append($('<td>').addClass('average'))		// avg
		.append($('<td>').addClass('consumption'))	// consumption
		.append($('<td>').addClass('last'))		// last
		.append($('<td>')				// operations
			.addClass('ops')
			.append($('<input>')
				.attr('type', 'image')
				.attr('src', 'images/information.png')
				.attr('alt', 'details')
				.bind('click', this, function(event) {
					event.data.showDetails();
				})
			)
		)
		.data('entity', this);
			
	if (vz.uuids.contains(this.uuid)) { // removable from cookies?
		$('td.ops', row).prepend($('<input>')
			.attr('type', 'image')
			.attr('src', 'images/delete.png')
			.attr('alt', 'delete')
			.bind('click', this, function(event) {
				vz.uuids.remove(event.data.uuid);
				vz.uuids.save();
				
				vz.entities.remove(event.data);
				vz.entities.showTable();
				
				vz.wui.drawPlot();
			})
		);
	}
	
	return row;
};

Entity.prototype.loadData = function() {
	//var delta = vz.options.plot.xaxis.max - vz.options.plot.xaxis.min;
	//var offset = delta * 0.1;
	var offset = 1000*30*60; // load additional data to avoid paddings

	return vz.load({
		controller: 'data',
		identifier: this.uuid,
		context: this,
		data: {
			from: Math.floor(vz.options.plot.xaxis.min - offset), // TODO fuzy-logic to get enough data
			to: Math.ceil(vz.options.plot.xaxis.max + offset),
			tuples: vz.options.tuples
		},
		success: function(json) {
			this.data = json.data;
		
			if (this.data.count > 0) {
				if (this.data.min[1] < vz.options.plot.yaxis.min) { // allow negative values for temperature sensors
					vz.options.plot.yaxis.min = null;
				}
		
				// update details in table
				$('#entity-' + this.uuid + ' .min')
					.text(vz.wui.formatNumber(this.data.min[1]) + this.definition.unit)
					.attr('title', $.plot.formatDate(new Date(this.data.min[0]), '%d. %b %h:%M:%S', vz.options.plot.xaxis.monthNames));
				$('#entity-' + this.uuid + ' .max')
					.text(vz.wui.formatNumber(this.data.max[1]) + this.definition.unit)
					.attr('title', $.plot.formatDate(new Date(this.data.max[0]), '%d. %b %h:%M:%S', vz.options.plot.xaxis.monthNames));
				$('#entity-' + this.uuid + ' .average')
					.text(vz.wui.formatNumber(this.data.average) + this.definition.unit);
				$('#entity-' + this.uuid + ' .last')
					.text(vz.wui.formatNumber(this.data.tuples.last()[1]) + this.definition.unit);
				if (this.definition.interpreter == 'Volkszaehler\\Interpreter\\MeterInterpreter') { // sensors have no consumption
					var consumption = vz.wui.formatNumber((this.data.consumption > 1000) ? this.data.consumption / 1000 : this.data.consumption);
					var unit = ((this.data.consumption > 1000) ? ' k' : ' ') + this.definition.unit + 'h';
					var cost = (this.cost !== undefined) ? ' (' + vz.wui.formatNumber(this.cost * this.data.consumption) + ' €)' : '';
					
					$('#entity-' + this.uuid + ' .consumption').text(consumption + unit + cost);
				}
			}
			else { // no data available, clear table
				$('#entity-' + this.uuid + ' .min').text('').attr('title', '');
				$('#entity-' + this.uuid + ' .max').text('').attr('title', '');
				$('#entity-' + this.uuid + ' .average').text('');
				$('#entity-' + this.uuid + ' .last').text('');
				$('#entity-' + this.uuid + ' .consumption').text('');
			}
		}
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
		data: {
			uuid: child.uuid
		},
		type: 'post'
	});
}

/**
 * Remove entity from children
 */
Entity.prototype.removeChild = function(child) {
	return vz.load({
		controller: 'group',
		identifier: this.uuid,
		data: {
			uuid: child.uuid,
			operation: 'delete'
		}
	});
};

/**
 * Validate Entity for required and optional properties and their values
 *
 * @return boolean
 * @todo implement/test
 */
Entity.prototype.validate = function() {
	this.definition.required.each(function(index, property) {
		var propertyDefinition = vz.capabilities.definitions.get('properties', property);
		if (!validateProperty(property, form.elements[property.name].value)) {
			throw new Exception('EntityException', 'Invalid property: ' + property.name + ' = ' + form.elements[property.name].value);
		}
	});
	
	entity.optional.each(function(index, property) {
		var property = getDefinition(properties, property);
	});
	
	return true;
};

/**
 * Calls the callback function for the entity and all nested children
 * 
 * @param cb callback function
 */
Entity.prototype.each = function(cb) {
	cb(this, this.parent);
	
	if (this.children) {
		for (var i = 0; i < this.children.length; i++) {
			this.children[i].each(cb, this);	// call recursive
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
	if (a.definition.model == 'Volkszaehler\\Model\\Channel' && // Channels before Aggregators
		b.definition.model == 'Volkszaehler\\Model\\Aggregator')
	{	
		return 1;
	}
	else {
		return ((a.title < b.title) ? -1 : ((a.title > b.title) ? 1 : 0));
	}
}
