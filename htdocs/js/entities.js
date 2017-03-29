/**
 * Entity collection handling, parsing & validation
 *
 * @author Florian Ziegler <fz@f10-home.de>
 * @author Justin Otherguy <justin@justinotherguy.org>
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Götz <cpuidle@gmx.de>
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
	var arr = [];

	this.each(function(entity) {
		if (entity.cookie === true) {
			arr.push(entity.uuid + '@' + entity.middleware + '@' + (entity.active ? '1' : '0'));
		}
	}, false); // non-recursive, i.e. only save root entities

	$.setCookie('vz_entities', arr.join('|'), {expires: expires});
};

/**
 * Load entities from JSON cookie
 */
vz.entities.loadCookie = function() {
	var cookie = $.getCookie('vz_entities');
	if (cookie) {
		var arr = cookie.split('|');
		arr.forEach(function(entry) {
			var entity = entry.split('@'),
					active = null;
			if (entity.length > 2)
				active = entity[2] == '1';
			vz.entities.push(new Entity({
				middleware: entity[1],
				uuid: entity[0],
				cookie: true,
				active: active
			}));
		});
	}
};

/**
 * Load JSON entity details from the middleware
 */
vz.entities.loadDetails = function() {
	var queue = [];
	vz.entities.each(function(entity) {
		// Use thenable form and skip default error handling to allow modifying deferred resolution for handling
		// invalid/deleted entity uuids. Otherwise frontend loading will stall.
		queue.push(entity.loadDetails(true).then(
			null,	// success - no changes needed
			function(xhr) {
				var exception = (xhr.responseJSON || {}).exception;
				// default error handling is skipped - be careful
				if (exception && exception.message.match(/^Invalid UUID|^No entity found with UUID/)) {
					vz.entities.splice(vz.entities.indexOf(entity), 1); // remove
					// return new resolved deferred
					$.Deferred().resolveWith(this, [xhr]);
				}
				return vz.load.errorHandler(xhr);
			}
		));
	}, true); // recursive
	return $.when.apply($, queue);
};

/**
 * Load JSON data from the middleware
 */
vz.entities.loadData = function() {
	var queue = [];
	vz.entities.each(function(entity) {
		queue.push(entity.loadData());
	}, true); // recursive
	return $.when.apply($, queue);
};

/**
 * Load total consumption for all entities that have the initialconsumption property defined
 */
vz.entities.loadTotalConsumption = function() {
	if (vz.options.totalsInterval) {
		var queue = [];
		vz.entities.each(function(entity) {
			queue.push(entity.loadTotalConsumption());
		}, true); // recursive

		// set timeout for next load once completed
		$.when.apply($, queue).done(function() {
			vz.entities.updateTableColumnVisibility();	// unhide total column
			window.setTimeout(vz.entities.loadTotalConsumption, vz.options.totalsInterval * 1000);
		});
	}
};

/**
 * Speedup middleware queries, requires options[aggregate] enabled
 * @return {string} group option or undefined
 */
vz.entities.speedupFactor = function() {
	var	group = vz.options.group,
		delta = (vz.options.plot.xaxis.max - vz.options.plot.xaxis.min) / 3.6e6;

	// explicit group set via url?
	if (group !== undefined)
		return group;

	if (delta > 24 * vz.options.tuples/vz.options.speedupFactor) {
		group = 'day';
	}
	else if (delta > vz.options.tuples/vz.options.speedupFactor) {
		group = 'hour';
	}
	return group;
};

/**
 * Overwritten each iterator to iterate recursively through all entities
 */
vz.entities.each = function(cb, recursive) {
	for (var i = 0; i < this.length; i++) {
		cb(this[i]);

		if (recursive && this[i] !== undefined) {
			this[i].eachChild(cb, true);
		}
	}
};

/**
 * Iterate each active channel containing data
 */
vz.entities.eachActiveChannel = function(cb) {
	this.each(function(entity) {
		if (entity.hasData() && entity.data && entity.data.tuples && entity.data.tuples.length > 0) {
			cb(entity);
		}
	}, true);
};

/**
 * Handle mouse drop operations for moving/cloning entities between groups
 */
vz.entities.dropTableHandler = function(from, to, child, clone) {
	if (clone) {
		child = $.extend(true, {}, child);
	}

	var queue = [];
	try {
		if (to) {
			queue.push(to.addChild(child)); // add to new aggregator
		}
		else { // add to root
			if ($.inArray(child, vz.entities) < 0)
				vz.entities.push(child);
			child.cookie = true;
			vz.entities.saveCookie();
		}

		if (!clone) {
			if (from) { // remove from an aggregator
				queue.push(from.removeChild(child));
			}
			else { // remove from root
				child.cookie = false;
				vz.entities.saveCookie();
				var idx = $.inArray(child, vz.entities);
				if (idx >= 0)
					vz.entities.splice(idx, 1);
			}
		}
	}
	catch (e) {
		vz.wui.dialogs.exception(e);
	}
	finally {
		// ...after updating entities
		$.when.apply($, queue).done(function() {
			var q = [], p = [];
			if (from) {
				// ...load new entity details
				q.push(from.loadDetails().done(function() {
					// ...and finally refresh data
					p.push(from.eachChild(function(child) {
						p.push(child.loadData());
					}, true).loadData());
				}));
			}
			if (to) {
				q.push(to.loadDetails().done(function() {
					p.push(to.eachChild(function(child) {
						p.push(child.loadData());
					}, true).loadData());
				}));
			}

			$.when.apply($, q).done(function() {
				vz.entities.showTable();
				$.when.apply($, p).done(vz.wui.drawPlot);
			});
		});
	}
};

/**
 * Create nested entity list
 * @todo move to Entity class
 */
vz.entities.showTable = function() {
	$('#entity-list tbody').empty();
	vz.entities.sort(Entity.compare);

	// add entities to table (recurse into aggregators)
	vz.entities.each(function(entity, parent) {
		if (entity.definition) { // skip bad entities, e.g. without data
			$('#entity-list tbody').append(entity.getDOMRow(parent));
		}
	}, true);

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

	// configure thead and aggregators as droppable
	$('#entity-list tr.aggregator span.indicator, #entity-list thead tr th:first').each(function() {
		$(this).parents('tr').droppable({
			accept: '#entity-list tr.channel span.indicator, #entity-list tr.aggregator span.indicator',
			drop: function(event, ui) {
				var child = $(ui.draggable.parents('tr')[0]).data('entity');
				if (child === null)
					return; // no data for the dropped object, probably not a row
				var from = child.parent;
				var to = $(this).data('entity');
				if (to === child)
					return; // drop on itself -> do nothing
				if (from === to)
					return; // drop into same group -> do nothing
				if (to && to.definition.model == 'Volkszaehler\\Model\\Aggregator' && $.inArray(child, to.children) >= 0)
					return;
				if (to && child.middleware !== to.middleware) {
					vz.wui.dialogs.error("Fehler", "Kanäle können nur in Gruppen der gleichen Middleware verschoben werden.");
					return;
				}

				$('#entity-move').dialog({ // confirm prompt
					resizable: false,
					modal: true,
					title: 'Verschieben',
					width: 400,
					buttons: {
						'Verschieben': function() {
							vz.entities.dropTableHandler(from, to, child, false);
							$(this).dialog('close');
						},
						'Kopieren': function() {
							vz.entities.dropTableHandler(from, to, child, true);
							$(this).dialog('close');
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
	$('#entity-list table tbody tr').click(function(ev) {
		var selected = $(this).data('entity').selected;

		if (ev.ctrlKey) {
			// partially remove previous selection
			if (selected) {
				$(this).removeClass('selected');
				$(this).data('entity').selected = false;
			}
		}
		else {
			// remove previous selection
			$('tr.selected').each(function(idx, el) {
				$(el).removeClass('selected');
				$(el).data('entity').selected = false;
			});
		}

		// add new selection
		if (!selected) {
			$(this).addClass('selected');
			$(this).data('entity').eachChild(function(child) {
				$('#entity-' + child.uuid).addClass('selected');
				// $('#entity-' + child.uuid + '.child-of-entity-' + child.parent.uuid).addClass('selected');
				child.selected = true;
			}, true).selected = true;
		}

		vz.wui.drawPlot();
	});

	$('#entity-list table').treeTable({
		treeColumn: 2,
		clickableNodeNames: true,
		initialState: 'expanded'
	});

	vz.entities.updateTableColumnVisibility();

	// display the data we have already
	vz.entities.each(function(entity) {
		entity.updateDOMRow();
	}, true); // recursive
};

/**
 * Apply active state to child entities and collapse root aggregator
 * @todo move to Entity class
 */
vz.entities.inheritVisibility = function() {
	vz.entities.each(function(entity, parent) {
		// inherit active state if parent
		if (entity.definition.model !== 'Volkszaehler\\Model\\Aggregator' && entity.parent !== undefined) {
			if (entity.active !== entity.parent.active) {
				entity.activate(entity.parent.active);
			}
		}

		// collapse aggregators if inactive
		if (entity.definition.model == 'Volkszaehler\\Model\\Aggregator' && entity.active === false) {
			entity.activate(false, entity.parent, true);
			$('#entity-' + entity.uuid + '.aggregator').removeClass('expanded').collapse();
		}
	}, true);
};

/**
 * Post-update entity list after adding/ removing/ updating entities
 *
 * @todo move to Entity class
 */
vz.entities.updateTableColumnVisibility = function() {
	// show/hide empty columns
	['consumption', 'cost', 'total'].forEach(function(column) {
		$('.' + column).css({
			display: ($('tbody .' + column).filter(function() {
									return (+$(this).data(column) || 0) > 0;
							 }).get().length === 0) ? 'none' : ''
		});
	});
};
