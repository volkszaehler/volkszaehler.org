/**
 * Javascript controller webinterface
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package controller
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * 
 * @todo use prototypes
 */
/*
 * This file is part of volkzaehler.org
 *
 * volkzaehler.org is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * volkzaehler.org is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
 */

const backend = '../backend';
const controller = 'php/channel.php';

var entities;
var properties;
var channels;

$(document).ready(function() {
	fetchEntities();
	fetchProperties();
	fetchChannels();
	
	// bind type dropdown to showEntityForm()
	$('#new select').change(function(event) { showEntityForm(event.target.value); });

	// bind button to addChannel()
	$('#new form').submit(function(event) {
		event.preventDefault();

		addChannel(this);
		
		return false;
	});
});

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

function showChannels() {
	$('#list table').empty();
	$('#list table').append('<tr><th>#</th><th>UUID</th><th>Typ</th><th>Port</th><th>Value</th></tr>');
	
	$.each(channels, function(index, channel) {
		$('#list table').append('<tr><td>' + index + '</td><td>' + channel.uuid + '</td><td>' + channel.type + '</td><td>' + channel.port + '</td><td>' + channel.value + '</td><td><input type="button" value="löschen" onclick="deleteChannel(\'' + channel.uuid + '\')" /></td></tr>');
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
 * Create and return a "version 4" RFC-4122 UUID string
 * 
 * @todo remove after got backend handling working
 */
function randomUUID() {
	var s = [], itoh = '0123456789ABCDEF';

	// make array of random hex digits. The UUID only has 32 digits in it, but we
	// allocate an extra items to make room for the '-'s we'll be inserting.
	for (var i = 0; i <36; i++) s[i] = Math.floor(Math.random()*0x10);

	// conform to RFC-4122, section 4.4
	s[14] = 4;  // Set 4 high bits of time_high field to version
	s[19] = (s[19] & 0x3) | 0x8;  // Specify 2 high bits of clock sequence

	// convert to hex chars
	for (var i = 0; i <36; i++) s[i] = itoh[s[i]];

	// insert '-'s
	s[8] = s[13] = s[18] = s[23] = '-';

	return s.join('');
}
