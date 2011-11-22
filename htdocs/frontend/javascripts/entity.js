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

	// parse children
	if (this.children) {
		for (var i = 0; i < this.children.length; i++) {
			this.children[i].middleware = this.middleware; // children inherit parent middleware		
			this.children[i] = new Entity(this.children[i]);
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
 * Query middleware for details
 * @return jQuery dereferred object
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
			tuples: vz.options.tuples
		},
		success: function(json) {
			this.data = json.data;

			if (this.data.tuples && this.data.tuples.length > 0) {
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
	var dialog = $('<div>');
	
	dialog.addClass('details')
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
	var general = ['title', 'type', 'uuid', 'middleware', 'color', 'style', 'active', 'cookie'];
	var sections = ['required', 'optional'];
	
	general.each(function(index, property) {
		var definition = vz.capabilities.definitions.get('properties', property);
		var title = (definition) ? definition.translation[vz.options.language] : property;
		var value = this[property];
		
		switch(property) {
			case 'type':
				var title = 'Typ';
				var icon = $('<img>').
					attr('src', 'images/types/' + this.definition.icon)
					.css('margin-right', 4);
				var value = $('<span>')
					.text(this.definition.translation[vz.options.language])
					.prepend(icon);
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
				var value = $('<span>')
					.text(this.color)
					.css('background-color', this.color)
					.css('padding-left', 5)
					.css('padding-right', 5);
				break;
				
			case 'cookie':
				var title = 'Cookie';
				value = '<img src="images/' + ((this.cookie) ? 'tick' : 'cross') + '.png" alt="' + ((value) ? 'ja' : 'nein') + '" />';
				break;
			case 'active':
				var value = '<img src="images/' + ((this.active) ? 'tick' : 'cross') + '.png" alt="' + ((this.active) ? 'ja' : 'nein') + '" />';
				break;
			case 'style':
				switch (this.style) {
					case 'lines': var value = 'Linien'; break;
					case 'steps': var value = 'Stufen'; break;
					case 'points': var value = 'Punkte'; break;
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

Entity.prototype.activate = function(state, parent, recursive) {
	this.active = state;
	var queue = new Array;
	
	$('#entity-' + this.uuid + ((parent) ? '.child-of-entity-' + parent.uuid : '') + ' input[type=checkbox]').attr('checked', state);
					
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
}

Entity.prototype.updateDOMRow = function() {
	var row = $('#entity-' + this.uuid);

	if (this.data && this.data.rows > 0) { // update statistics if data available
		var delta = this.data.to - this.data.from;
		var year = 365*24*60*60*1000;
	
		$('.min', row)
			.text(vz.wui.formatNumber(this.data.min[1], true) + this.definition.unit)
			.attr('title', $.plot.formatDate(new Date(this.data.min[0]), '%d. %b %y %h:%M:%S', vz.options.monthNames, vz.options.dayNames, true));
		$('.max', row)
			.text(vz.wui.formatNumber(this.data.max[1], true) + this.definition.unit)
			.attr('title', $.plot.formatDate(new Date(this.data.max[0]), '%d. %b %y %h:%M:%S', vz.options.monthNames, vz.options.dayNames, true));
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
}

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
}

/**
 * Remove entity from children
 */
Entity.prototype.removeChild = function(child) {
	if (this.definition.model != 'Volkszaehler\\Model\\Aggregator') {
		throw new Exception('EntityException', 'Entity is not an Aggregator');
	}

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
	if (a.definition.model == 'Volkszaehler\\Model\\Channel' && // Channels before Aggregators
		b.definition.model == 'Volkszaehler\\Model\\Aggregator')
	{	
		return 1;
	}
	else {
		return ((a.title < b.title) ? -1 : ((a.title > b.title) ? 1 : 0));
	}
}
