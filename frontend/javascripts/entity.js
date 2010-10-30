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
var Entity = function(json) {
	for (var i in json) {
		switch(i) {
			case 'children':
				this.children = new Array;
				this.children.each = vz.entities.each;
				for (var j = 0; j < json.children.length; j++) {
					var child = new Entity(json.children[j]);
					this.children.push(child);
				}
				break;
				
			case 'type':
			case 'uuid':
			default:		// properties
				this[i] = json[i];
		}	
	}

	//this.definition = vz.definitions.get('entity', this.type);
};

/**
 * Show and edit entity details
 */
Entity.prototype.showDetails = function() {
	$('<div>')
	.addClass('details')
	.append(this.getDOM())
	.dialog({
		title: 'Entity Details',
		width: 450
	});
};

/**
 * Show from for new Channel
 * 
 * @todo implement/test
 */
Entity.prototype.getDOM = function() {
	var table = $('<table><thead><tr><th>Key</th><th>Value</th></tr></thead></table>');
	var data = $('<tbody>');

	for (var property in this) {
		if (this.hasOwnProperty(property) && property != 'data' && property != 'children') {
			data.append($('<tr>')
				.append($('<td>')
					.addClass('key')
					.text(property)
				)
				.append($('<td>')
					.addClass('value')
					.text(this[property])
				)
			);
		}
	}
	return table.append(data);
};

/**
 * Validate Entity for required and optional properties and their values
 * @return boolean
 * @todo implement/test
 */
Entity.prototype.validate = function() {
	var def = getDefinition(vz.definitions.entities, entity.type);
	
	def.required.each(function(index, property) {
		var property = getDefinition(vz.definitions.properties, property);
		if (!validateProperty(property, form.elements[property.name].value)) {
			throw 'Invalid property: ' + property.name + ' = ' + form.elements[property.name].value;
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
Entity.prototype.each = function(cb, parent) {
	cb(this, parent);
	
	if (this.children) {
		for (var i = 0; i < this.children.length; i++) {
			this.children[i].each(cb, this);	// call recursive
		}
	}
};
