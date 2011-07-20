/**
 * Entity handling, parsing & validation
 * 
 * @author Justin Otherguy <justin@justinotherguy.org>
 * @author Steffen Vogel <info@steffenvogel.de>
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
 * @todo add validation
 */
var Entity = function(json) {
	this.parseJSON(json);

	if (this.active === undefined) {
		this.active = true; // activate by default
	}

	
};

Entity.prototype.parseJSON = function(json) {
	$.extend(true, this, json);

	if (this.children) {
		for (var i = 0; i < this.children.length; i++) {
			this.children[i].middleware = this.middleware; // children inherit parent middleware		
			this.children[i] = new Entity(this.children[i]);
		}
		
		this.children.sort(Entity.compare);
	}
	
	if (this.type !== undefined) {
		this.definition = vz.capabilities.definitions.get('entities', this.type);
	}
};

/**
 * Query middleware for details
 */
Entity.prototype.loadDetails = function() {
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

Entity.prototype.loadData = function() {
	return vz.load({
		controller: 'data',
		url: this.middleware,
		identifier: this.uuid,
		context: this,
		data: {
			from: Math.floor(vz.options.plot.xaxis.min),
			to: Math.ceil(vz.options.plot.xaxis.max),
			tuples: vz.options.tuples
		},
		success: function(json) {
			this.data = json.data;
		
			if (this.data.count > 0) {
				if (this.data.min[1] < vz.options.plot.yaxis.min) { // allow negative values for temperature sensors
					vz.options.plot.yaxis.min = null;
				}
			}
			
			this.updateDOMRow();
		}
	});
};

/**
 * Show and edit entity details
 */
Entity.prototype.showDetails = function() {
	var entity = this;

	$('<div>')
	.addClass('details')
	.append(this.getDOMDetails())
	.dialog({
		title: 'Details f&uuml;r ' + this.title,
		width: 480,
		resizable: false,
		buttons : {
			'Schließen': function() {
				$(this).dialog('close');
			},
			'Löschen' : function() {
				vz.load({ // TODO encapsulate in own method
					controller: 'entity',
					context: this,
					identifier: entity.uuid,
					url: entity.middleware,
					type: 'DELETE',
					success: function() {
						$(this).dialog('close');
					}
				});
			}
		}
	});
};

/**
 * Show from for new Channel
 * 
 * @todo implement/test
 */
Entity.prototype.getDOMDetails = function(edit) {
	var table = $('<table><thead><tr><th>Eigenschaft</th><th>Wert</th></tr></thead></table>');
	var data = $('<tbody>');
	
	// general properties
	var general = ['uuid', 'middleware', 'type', 'color', 'cookie'];
	var sections = ['required', 'optional'];
	
	general.each(function(index, property) {
		switch(property) {
			case 'type':
				var title = 'Typ';
				var value = this.definition.translation[vz.options.language];
				break;
			
			case 'middleware':
				var title = 'Middleware';
				var value = '<a href="' + this.middleware + '/capabilities.json">' + this.middleware + '</a>';
				break;
			
			case 'uuid':
				var title = 'UUID';
				var value = '<a href="' + this.middleware + '/entity/' + this.uuid + '.json">' + this.uuid + '</a>';
				break;
	
			case 'color':
				var title = 'Farbe';
				var value = '<span style="background-color: ' + this.color + '">' + this.color + '</span>';
				break;
				
			case 'cookie':
				var title = 'Cookie';
				value = '<img src="images/' + ((this.cookie) ? 'tick' : 'cross') + '.png" alt="' + ((value) ? 'ja' : 'nein') + '" />';
				break;
			case 'active':
				var title = 'Aktiv';
				var value = '<img src="images/' + ((this.active) ? 'tick' : 'cross') + '.png" alt="' + ((this.active) ? 'ja' : 'nein') + '" />';
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
			if (this.hasOwnProperty(property)) {
				var definition = vz.capabilities.definitions.get('properties', property);
				var title = definition.translation[vz.options.language];
				var value = this[property];
		
				if (definition.type == 'boolean') {
					value = '<img src="images/' + ((value) ? 'tick' : 'cross') + '.png" alt="' + ((value) ? 'ja' : 'nein') + '" />';
				}
					
				if (property == 'cost') {
					value = (value * 1000 * 100) + ' ct/k' + this.definition.unit + 'h'; // ct per kWh
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
					var state = $(this).attr('checked');
					
					event.data.active = state;
					$('#entity-' + event.data.uuid + ((parent) ? '.child-of-entity-' + parent.uuid : '') + ' input[type=checkbox]');
					
					event.data.each(function(entity, parent) {
						$('#entity-' + entity.uuid + ((parent) ? '.child-of-entity-' + parent.uuid : '') + ' input[type=checkbox]')
						.attr('checked', state);
						entity.active = state;
					}, true); // recursive!
					
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
		.append($('<td>').addClass('last'))		// last value
		.append($('<td>').addClass('consumption'))	// consumption
		.append($('<td>').addClass('cost'))		// costs
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
	
	if (this.cookie) {		
		$('td.ops', row).prepend($('<input>')
			.attr('type', 'image')
			.attr('src', 'images/delete.png')
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

Entity.prototype.updateDOMRow = function() {
	var row = $('#entity-' + this.uuid);
	
	var delta = this.data.to - this.data.from;
	var year = 365*24*60*60*1000;

	if (this.data.count > 0) { // update statistics if data available
		$('.min', row)
			.text(vz.wui.formatNumber(this.data.min[1], true) + this.definition.unit)
			.attr('title', $.plot.formatDate(new Date(this.data.min[0]), '%d. %b %y %h:%M:%S', vz.options.plot.xaxis.monthNames));
		$('.max', row)
			.text(vz.wui.formatNumber(this.data.max[1], true) + this.definition.unit)
			.attr('title', $.plot.formatDate(new Date(this.data.max[0]), '%d. %b %y %h:%M:%S', vz.options.plot.xaxis.monthNames));
		$('.average', row)
			.text(vz.wui.formatNumber(this.data.average, true) + this.definition.unit);
		$('.last', row)
			.text(vz.wui.formatNumber(this.data.tuples.last()[1], true) + this.definition.unit);
		
		$('.consumption', row)
			.text(vz.wui.formatNumber(this.data.consumption, true) + this.definition.unit + 'h')
			.attr('title', vz.wui.formatNumber(this.data.consumption * (year/delta), true) + this.definition.unit + 'h' + '/Jahr');
		
		if (this.cost) {
			$('.cost', row)
				.text(vz.wui.formatNumber(this.cost * this.data.consumption) + ' €')
				.attr('title', vz.wui.formatNumber(this.cost * this.data.consumption * (year/delta)) + ' €/Jahr');
		}
	}
	else { // no data available, clear table
		$('.min', row).text('').attr('title', '');
		$('.max', row).text('').attr('title', '');
		$('.average', row).text('');
		$('.last', row).text('');
		$('.consumption', row).text('');
		$('.cost', row).text('');
	}
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
		url: this.middleware,
		data: {
			uuid: child.uuid,
			operation: 'delete'
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
			
			if (recursive) {
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
	if (a.definition.model == 'Volkszaehler\\Model\\Channel' && // Channels before Aggregators
		b.definition.model == 'Volkszaehler\\Model\\Aggregator')
	{	
		return 1;
	}
	else {
		return ((a.title < b.title) ? -1 : ((a.title > b.title) ? 1 : 0));
	}
}
