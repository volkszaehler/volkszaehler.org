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
		entity.color = vz.options.plot.seriesColors[i++ % vz.options.plot.seriesColors.length];
		
		vz.plot.series[vz.plot.series.length] = vz.plot.seriesDefault; 
		
		
		$('#entities tbody').append(
			$('<tr>')
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
					.css('text-align', 'right')
					.append($('<input>')
						.attr('type', 'image')
						.attr('src', 'images/information.png')
						.attr('alt', 'details')
						.bind('click', entity, function(event) { showEntityDetails(event.data); })
					)
					.append($('<input>')
						.attr('type', 'image')
						.attr('src', 'images/delete.png')
						.attr('alt', 'delete')
						.bind('click', entity, function(event) { removeUUID(event.data.uuid); })
					)
				)
		);
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

function validateChannel(form) {
	var entity = getDefinition(entities, form.type.value);
	
	$.each(entity.required, function(index, property) {
		var property = getDefinition(properties, property);
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
 * Get entities from backend
 */
function fetchEntities() {
	$.getJSON(backend + '/capabilities/definition/entity.json', function(data) {
		entities = data.definition.entity;
		
		// Add channel types to dropdown
		$('#new select').empty();
		$.each(entities, function(index, entity) {
			if (entity.model == 'Volkszaehler\\Model\\Channel') {
				$('#new select').append('<option value="' + entity.name + '">' + entity.translation.de + '</option>');
			}
		});
		
		// show related properties
		$('#new select').trigger('change');
	});
}

/**
 * Get properties from backend
 */
function fetchProperties() {
	$.getJSON(backend + '/capabilities/definition/property.json', function(data) {
		properties = data.definition.property;
		
		// show related properties
		$('#new select').trigger('change');
	});
}

/**
 * Get channels from controller
 */
function fetchChannels() {
	$.getJSON(controller, function(data) {
		channels = data;
		
		// add fetched channels to table
		showChannels();
	});
}

/**
 * Show from for new Channel
 * 
 * @param type
 * @return
 */
function showEntityForm(type) {
	$('#properties').empty();
	var entity = getDefinition(entities, type);
	
	$.each(entity.required, function(index, property) {
		var property = getDefinition(properties, property);
		
		if (property) {
			$('#properties').append('<tr class="required"><td><label for="' + property.name + '">' + property.translation.de + ':</label></td><td>' + getPropertyForm(property) + '</td><td>(*)</td></tr>');
		}
	});
	
	$.each(entity.optional, function(index, property) {
		var property = getDefinition(properties, property);
		
		if (property) {
			$('#properties').append('<tr class="optional"><td><label for="' + property.name + '">' + property.translation.de + ':</label></td><td>' + getPropertyForm(property) + '</td></tr>');
		}
	});
}

/**
 * @param uuid
 * @return
 */
function deleteChannel(uuid) {
	$.getJSON(controller, { operation: 'delete', uuid: uuid }, function(data) {
		channels = data;
		showChannels();
	});
}

function addChannel(form) {
	var uuid = false;
	
	if (validateChannel(form)) {
		//if (uuid = addChannelBackend(form)) {
			if (addChannelController(form, randomUUID)) {	//uuid)) {
				fetchChannels();
				return true;
			}
			else {
				//removeChannelBackend(uuid);
				alert('Error: adding channel to controller');
			}
		/*}
		else {
			alert('Error: adding channel to backend');
		}*/
	}
	else {
		alert('Please correct your input');
	}
}

function addChannelController(form, uuid) {
	$.getJSON(controller, { operation: 'add', uuid: uuid, port: form.port.value, type: form.type.value }, function(data) {
		channels = data;
		showChannels();
	});
	
	return true; // TODO
}

function addChannelBackend(form) {
	$.getJSON(backend + '/channel.json', { operation: 'add' }, function(data) {
		
	});
	
	return true; // TODO
}

function getDefinition(definition, type) {
	for (var i in definition) {
		if (definition[i].name == type) {
			return definition[i];
		}
	}
}

function getPropertyForm(property) {
	switch (property.type) {
		case 'string':
		case 'float':
		case 'integer':
			return '<input type="text" name="' + property.name + '" ' + ((property.type == 'string') ? 'maxlength="' + property.max + '" ' : '') + '/>';
			
		case 'text':
			return '<textarea name="' + property.name + '"></textarea>';
			
		case 'boolean':
			return '<input type="checkbox" name="' + property.name + '" value="1" />';
			
		case 'multiple':
			$.each(property.options, function(index, option) {
				options.push('<option value="' + option + '">' + option + '<\option>');
			});
			return '<select name="' + property.name + '">' + options.join() + '</select>';
	
		default:
			alert('Error: unknown property!');
	}
}