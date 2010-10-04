/**
 * Backend related javascript code
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
 * Get all entity information from backend
 */
function loadEntities() {
	$.each(vz.uuids, function(index, value) {
		$.getJSON(vz.options.backendUrl + '/entity/' + value + '.json', ajaxWait(function(json) {
			vz.entities.push(json.entity);
		}, showEntities, 'information'));
	});
}

/**
 * Create nested entity list
 * @param data
 */
function showEntities() {
	$('#entities tbody').empty();
	
	var i = 0;
	eachRecursive(vz.entities, function(entity, parent) {
		entity.active = true;	// TODO active by default or via backend property?
		entity.color = vz.options.plot.colors[i++ % vz.options.plot.colors.length];
	
		var row = $('<tr>')
			.addClass((parent) ? 'child-of-entity-' + parent.uuid : '')
			.attr('id', 'entity-' + entity.uuid)
			.append($('<td>')
				.css('background-color', entity.color)
				.css('width', 19)
				.append($('<input>')
					.attr('type', 'checkbox')
					.attr('checked', entity.active)
					.bind('change', entity, function(event) {
						event.data.active = $(this).attr('checked');
						loadData();
					})
				)
			)
			.append($('<td>')
				.css('width', 20)
			)
			.append($('<td>')
				.append($('<span>')
					.text(entity.title)
					.addClass('indicator')
					.addClass((entity.type == 'group') ? 'group' : 'channel')
				)
			)
			.append($('<td>').text(entity.type))
			.append($('<td>'))	// min
			.append($('<td>'))	// max
			.append($('<td>'))	// avg
			.append($('<td>')	// operations
				.addClass('ops')
				.append($('<input>')
					.attr('type', 'image')
					.attr('src', 'images/information.png')
					.attr('alt', 'details')
					.bind('click', entity, function(event) { showEntityDetails(event.data); })
				)
			);
				
		if (parent == null) {
			$('td.ops', row).prepend($('<input>')
				.attr('type', 'image')
				.attr('src', 'images/delete.png')
				.attr('alt', 'delete')
				.bind('click', entity, function(event) {
					removeUUID(event.data.uuid);
					loadEntities();
				})
			);
		}
		
		$('#entities tbody').append(row);
	});
	
	// http://ludo.cubicphuse.nl/jquery-plugins/treeTable/doc/index.html
	$('#entities table').treeTable({
		treeColumn: 2,
		clickableNodeNames: true
	});
	
	// load data and show plot
	loadData();
}

/**
 * Show and edit entity details
 * @param entity
 */
function showEntityDetails(entity) {
	var properties = $('<table>');
	
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
	
	$('<div>')
	.addClass('details')
	.append(properties)
	.dialog({
		title: 'Entity Details',
		width: 450
	});
}

function validateEntity(entity) {
	var def = getDefinition(vz.definitions.entities, entity.type);
	
	$.each(def.required, function(index, property) {
		var property = getDefinition(vz.definitions.properties, property);
		if (!validateProperty(property, form.elements[property.name].value)) {
			alert('Error: invalid property: ' + property.name + ' = ' + form.elements[property.name].value);
			return false;
		}
	});
	
	$.each(entity.optional, function(index, property) {
		var property = getDefinition(properties, property);
	});
	
	return true;
}

function validateProperty(property, value) {
	switch (property.type) {
		case 'string':
		case 'text':
			// TODO check pattern
			// TODO check string length
			return true;
			
		case 'float':
			// TODO check format
			// TODO check min/max
			return true;
			
		case 'integer':
			// TODO check format
			// TODO check min/max
			return true;
			
		case 'boolean':
			return value == '1' || value == '';
			
		case 'multiple':
			return $.inArray(value, property.options);
			
		default:
			alert('Error: unknown property!');
	}
}

/**
 * Show from for new Channel
 * 
 * @param type
 * @return
 */
function getEntityDOM(type) {
	$('#properties').empty();
	var entity = getDefinition(entities, type);
	
	$.each(entity.required, function(index, property) {
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
}

function getPropertyDOM(property) {
	switch (property.type) {
		case 'string':
		case 'float':
		case 'integer':
			return $('<input>')
				.attr('type', 'text')
				.attr('name=', property.name)
				.attr('maxlength', (property.type == 'string') ? property.max : 0);
			
		case 'text':
			return $('<textarea>')
				.attr('name', property.name);
			
		case 'boolean':
			return $('<input>')
				.attr('type', 'checkbox')
				.attr('name', property.name)
				.value(1);
			
		case 'multiple':
			var dom = $('<select>').attr('name', property.name)
			$.each(property.options, function(index, option) {
				dom.append($('<option>')
					.value(option)
					.text(option)
				);
			});
			return dom;
	
		default:
			throw {
				type: 'PropertyException',
				message: 'Unknown property type'
			};
	}
}