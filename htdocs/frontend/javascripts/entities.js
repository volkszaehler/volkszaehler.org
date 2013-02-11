/**
 * 
 * 
 * @author Florian Ziegler <fz@f10-home.de>
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
 * Save minimal Entity in JSON cookie
 */
vz.entities.saveCookie = function() {
	var expires = new Date(2038, 0, 1); // some days before y2k38 problem
	var arr = new Array;
	
	this.each(function(entity) {
		if (entity.cookie === true) {
			arr.push(entity.uuid + '@' + entity.middleware);
		}
	}, true); // recursive!
	
	$.setCookie('vz_entities', arr.join('|'), {expires: expires});
};

/**
 * Load entities from JSON cookie
 */
vz.entities.loadCookie = function() {
	var cookie = $.getCookie('vz_entities');
	if (cookie) {
		var arr = cookie.split('|');
		arr.each(function(index, entry) {
			var entity = entry.split('@');
			vz.entities.push(new Entity({
				middleware: entity[1],
				uuid: entity[0],
				cookie: true
			}));
		});
	}
};

/**
 * Load JSON data from the middleware
 */
vz.entities.loadData = function() {
	$('#overlay').html('<img src="images/loading.gif" alt="loading..." /><p>loading...</p>');

	var queue = new Array;
	this.each(function(entity) {
		if (entity.active && entity.definition.model == 'Volkszaehler\\Model\\Channel') {
			queue.push(entity.loadData());
		}
	}, true); // recursive!
	
	return $.when.apply($, queue);
};

/**
 * Overwritten each iterator to iterate recursively throug all entities
 */
vz.entities.each = function(cb, recursive) {
	for (var i = 0; i < this.length; i++) {
		cb(this[i]);
		
		if (recursive && this[i] !== undefined) {
			this[i].each(cb, true);
		}
	}
}

/**
 * Create nested entity list
 *
 * @todo move to Entity class
 */
vz.entities.showTable = function() {
	$('#entity-list tbody').empty();

	vz.entities.sort(Entity.compare);
	
	this.each(function(entity, parent) {
		$('#entity-list tbody').append(entity.getDOMRow(parent));
	}, true); // recursive!

	/*
	 * Initialize treeTable
	 * 
	 * http://ludo.cubicphuse.nl/jquery-plugins/treeTable/doc/index.html
	 * https://github.com/ludo/jquery-plugins/tree/master/treeTable
	 */
	// configure entities as draggable
	$('#entity-list tr.channel span.indicator, #entity-list tr.aggregator span.indicator').draggable({
		helper:  'clone',
		opacity: 0.75,
		refreshPositions: true, // Performance?
		revert: 'invalid',
		revertDuration: 300,
		scroll: true
	});

	// configure aggregators as droppable
	$('#entity-list tr.aggregator span.indicator').each(function() {
		$(this).parents('tr').droppable({
			//accept: 'tr.channel span.indicator, tr.aggregator span.indicator', // TODO
			drop: function(event, ui) {
				var child = $(ui.draggable.parents('tr')[0]).data('entity');
				//var from = child.parent;
				var to = $(this).data('entity');
				
				$('#entity-move').dialog({ // confirm prompt
					resizable: false,
					modal: true,
					title: 'Verschieben',
					width: 400,
					buttons: {
						'Verschieben': function() {
							try {
								var queue = new Array;
								queue.push(to.addChild(child)); // add to new aggregator
					
								if (from !== undefined) {
				//					queue.push(from.removeChild(child)); // remove from aggregator
								}
								else {
									child.cookie = false; // remove from cookies
									vz.entities.saveCookie();
								}
							} catch (e) {
								vz.wui.dialogs.exception(e);
							} finally {
								$.when(queue).done(function() { // wait for middleware
				//					$.when(from.loadDetails(), to.loadDetails).done(vz.entities.showTable);
								});
								$(this).dialog('close');
							}
						},
						'Abbrechen': function() {
							$(this).dialog('close');
						}
					}
				});
			},
			hoverClass: 'accept',
			over: function(event, ui) {
				// make the droppable branch expand when a draggable node is moved over it
				if (this.id != $(ui.draggable.parents('tr')[0]).id && !$(this).hasClass('expanded')) {
					$(this).expand();
				}
			}
		});
	});

	// make visible that a row is clicked
	$('#entity-list table tbody tr').mousedown(function() {
		var selected = $('tr.selected');
		selected.removeClass('selected'); // deselect currently selected rows
		if ($(this).attr('id') != selected.attr('id'))
			$(this).addClass('selected'); // select if a different row was clicked
	});

	// make sure row is selected when span is clicked
/*	$('#entity-list table tbody tr span').mousedown(function() {
		$($(this).parents('tr')[0]).trigger('mousedown');
	}); */
	
	$('#entity-list table').treeTable({
		treeColumn: 2,
		clickableNodeNames: true,
		initialState: 'expanded'
	});
};
