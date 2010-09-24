var backend = '../backend';
var controller = 'php/channel.php';

var entities;
var properties;
var channels;

$(document).ready(function() {
	// get entities from backend
	$.getJSON(backend + '/capabilities/definition/entity.json', function(data) {
		entities = data.definition.entity; 
		for (var i in entities) {
			$('select, [name=type]').append('<option value="' + entities[i].name + '">' + entities[i].de_name + '</option>');
		}
	});
	
	// get properties from backend
	$.getJSON(backend + '/capabilities/definition/property.json', function(data) {
		properties = data.definition.property;
	});
	
	// get channels from controller
	$.getJSON(controller, showChannels);
	
	$('select').change(function(event) { showEntityForm(event.target.value); });
});


function showEntityForm(type) {
	$('#properties').empty();
	var type = getDefinition(entities, type);
	
	for (var i in type.optional) {
		var property = getDefinition(properties, type.optional[i]);
		
		if (property) {
			var input = getPropertyForm(property);
			$('#properties').append('<tr><td><label for="' + property.name + '">' + property.de_name + ':</label></td><td>' + input + '</td></tr>')
		}
	}
}

function deleteChannel(uuid) {
	$.get(controller, {operation: 'delete', uuid: uuid}, function(data) {
		
	})
}

function addChannel() {
	
}

function addChannelBackend(channel, backendUrl) {
	
}

function addChannelController(uuid) {
	
}

function getDefinition(definition, type) {
	for (var i in definition) {
		if (definition[i].name == type) {
			return definition[i];
		}
	}
}

function getPropertyForm(property) {
	var input;
	
	switch (property.type) {
	case 'string':
	case 'float':
	case 'integer':
		input = '<input type="text" name="' + property.name + '" maxlength="' + property.max + '" />';
		break;
		
	case 'text':
		input = '<textarea name="' + property.name + '"></textarea>';
		break;
		
	case 'boolean':
		input = '<input type="checkbox" name="' + property.name + '" value="true" />';
		
	case 'multiple':
		for (var k in property.options) {
			options.push('<option>' + property.options[k] + '<\option>');
		}
		input = '<select name="' + property.name + '">' + options.join() + '</select>';
		break;
	
		default:
			input = 'Error: unknown property type!';
	}
	
	return input;
}

function showChannels(data) {
	channels = data;
	for (var i in channels) {
		$('#channels').append('<tr><td>' + i + '</td><td>' + channels[i].uuid + '</td><td>' + channels[i].type + '</td><td>' + channels[i].port + '</td><td>' + channels[i].value + '</td><td><input type="button" value="löschen" /></td></tr>');
	}
}
