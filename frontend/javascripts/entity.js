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
 * Entity
 */
var Entity = function(json) {
	for (var i in json) {
		switch(i) {
			case 'children':
				this.children = new Array;
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
};

/**
 * Show and edit entity details
 * @param entity
 */
Entity.prototype.showDetails = function(entity) {
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
 * @param type
 * @todo
 */
Entity.prototype.getDOM = function(type) {
	var properties = $('<table><thead><th>Key</th><th>Value</th></thead></table');
	
	$.each(entity, function(key, value) {
		properties.append($('<tr>')
			.append($('<td>')
				.addClass('key')
				.text(key)
			)
			.append($('<td>')
				.addClass('value')
				.text(value)
			)
		);
	});
	
	var entity = getDefinition(entities, type);
	
	entity.required.each(function(index, property) {
		var property = getDefinition(properties, property);
		
		if (property) {
			$('#properties')
				.append($('<tr>')
					.addClass('required')
					.append($('<td>')
						.append($('<label>')
							.attr('for', property.name)
							.text(property.translation.de + ':')
						)
					)
					.append($('<td>').append(getPropertyDOM(property)))
					.append($('<td>').text('(*)'))
				);
		}
	});
	
	// TODO optional properties
};

Entity.prototype.validate = function(entity) {
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

Entity.prototype.each = function(cb, parent) {
	cb(this, parent);
	
	if (this.children) {
		for (var i = 0; i < this.children.length; i++) {
			this.children[i].each(cb, this);	// call recursive
		}
	}
};