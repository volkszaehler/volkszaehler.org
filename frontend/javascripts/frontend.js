/**
 * Javascript functions for the frontend
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

/*
 * Frontend related functions
 */

/**
 * Initialize the WUI (Web User Interface)
 */
vz.initInterface = function() {
	// make the whole frontend resizable
	/*$('#content').resizable({
		alsoResize: $('#plot'),
		//ghost: true,
		//animate: true,
		autoHide: true
	});*/
	
	// initialize dropdown accordion
	$('#accordion h3').click(function() {
		$(this).next().toggle('fast');
		return false;
	}).next().hide();
	
	// make buttons fancy
	$('button, input[type=button]').button();
	
	// open UUID dialog
	$('button[name=addUUID]').click(function() {
		$('#addUUID').dialog({
			title: 'UUID hinzufügen',
			width: 400
		});
	});
	
	// open new entity dialog
	$('button[name=newEntity]').click(function() {
		$('#newEntity').dialog({
			title: 'Entity erstellen',
			width: 400
		});
	});
	
	// add UUID
	$('#addUUID input[type=button]').click(function() {
		vz.uuids.add($('#addUUID input[type=text]').val());
		$('#addUUID').dialog('close');
		vz.entities.load();
	})
	
	// bind plot actions
	$('#move input').click(vz.handleControls);
	
	// options
	/*$('input[name=trendline]').attr('checked', vz.options.plot.seriesDefaults.trendline.show).change(function() {
		vz.options.plot.seriesDefaults.trendline.show = $(this).attr('checked');
		drawPlot();
	});*/
	
	$('input[name=backendUrl]').val(vz.options.backendUrl).change(function() {
		vz.options.backendUrl = $(this).val();
	});
	
	$('#tuples input').val(vz.options.tuples).change(function() {
		vz.options.tuples = $(this).val();
	});
	
	$('#tuples .slider').slider({
		min: 1,
		max: 1000,
		step: 10
	});
	
	$('#refresh .slider').slider({
		min: 500,
		max: 60000,
		step: 500
	});
};

/**
 * Refresh plot with new data
 */
vz.refresh = function() {
	if ($('input[name=refresh]').attr('checked')) {
		var delta = vz.to - vz.from;
		vz.to = new Date().getTime();	// move plot
		vz.from = vz.to - delta;		// move plot
		loadData();
	}
};

/**
 * Move & zoom in the plotting area
 */
vz.handleControls = function () {
	var delta = vz.to - vz.from;
	var middle = Math.round(vz.from + delta/2);
	
	switch(this.value) {
		case 'move_last':
			vz.to = new Date().getTime();
			vz.from = vz.to - delta;
			break;
			
		case 'move_back':
			vz.from -= delta;
			vz.to -= delta;
			break;
		case 'move_forward':
			vz.from += delta;
			vz.to += delta;
			break;
		
		case 'zoom_reset':
			vz.from = middle - Math.floor(defaultInterval/2);
			vz.to =  middle + Math.ceil(defaultInterval/2);
			break;
			
		case 'zoom_in':
			vz.from += Math.floor(delta/4);
			vz.to -= Math.ceil(delta/4);
			break;
			
		case 'zoom_out':
			vz.from -= delta;
			vz.to += delta;
			break;
			
		case 'refresh':
			// do nothing; just loadData()
	}
	
	vz.data.load();
};


/**
 * Get all entity information from backend
 */
vz.entities.load = function() {
	vz.entities.clear();
	vz.uuids.each(function(index, value) {
		$.getJSON(vz.options.backendUrl + '/entity/' + value + '.json', ajaxWait(function(json) {
			vz.entities.push(new Entity(json.entity));
		}, vz.entities.show, 'information'));
	});
};

/**
 * Create nested entity list
 * @param data
 */
vz.entities.show = function() {
	$('#entities tbody').empty();
	
	var i = 0;
	vz.entities.each(function(index, entity) {	// loop through all entities
		entity.each(function(entity, parent) {	// loop through all children of entities (recursive)
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
							vz.data.load();
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
						.bind('click', entity, function(event) { event.data.showDetails(); })
					)
				);
					
			if (parent == null) {
				$('td.ops', row).prepend($('<input>')
					.attr('type', 'image')
					.attr('src', 'images/delete.png')
					.attr('alt', 'delete')
					.bind('click', entity, function(event) {
						vz.uuids.remove(event.data.uuid);
						vz.entities.load();
					})
				);
			}
			
			$('#entities tbody').append(row);
		});
	});
	
	// http://ludo.cubicphuse.nl/jquery-plugins/treeTable/doc/index.html
	$('#entities table').treeTable({
		treeColumn: 2,
		clickableNodeNames: true
	});
	
	// load data and show plot
	vz.data.load();
};

/**
 * Load json data with given time window
 */
vz.data.load = function() {
	vz.data.clear();
	vz.entities.each(function(index, entity) {
		entity.each(function(entity, parent) {
			if (entity.active && entity.type != 'group') {
				$.getJSON(vz.options.backendUrl + '/data/' + entity.uuid + '.json', { from: vz.from, to: vz.to, tuples: vz.options.tuples }, ajaxWait(function(json) {
					vz.data.push({
						data: json.data[0].tuples,	// TODO check uuid
						color: entity.color
					});
				}, vz.drawPlot, 'data'));
			}
		});
	});
};

vz.drawPlot = function () {
	vz.options.plot.xaxis.min = vz.from;
	vz.options.plot.xaxis.max = vz.to;
	
	vz.plot = $.plot($('#plot'), vz.data, vz.options.plot);
};
