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
	$.extend(true, this, json);

	if (this.children) {
		for (var i in this.children) {
			this.children[i] = new Entity(this.children[i]);
		}
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
Entity.prototype.getDOM = function() {
	var table = $('<table><thead><tr><th>Key</th><th>Value</th></tr></thead></table>');
	var data = $('<tbody>');

	for (var property in this) {
		if (this.hasOwnProperty(property) && !['data', 'definition', 'children'].contains(property)) {
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
