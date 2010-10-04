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
function initInterface() {
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
		addUUID($('#addUUID input[type=text]').val());
		$('#addUUID').dialog('close');
		loadEntities();
	})
	
	// bind plot actions
	$('#move input').click(handleControls);
	
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
}

/**
 * Refresh plot with new data
 */
function refreshWindow() {
	if ($('input[name=refresh]').attr('checked')) {
		var delta = vz.to - vz.from;
		vz.to = new Date().getTime();	// move plot
		vz.from = vz.to - delta;		// move plot
		loadData();
	}
}

/**
 * Move & zoom in the plotting area
 */
function handleControls() {
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
	
	loadData();
}

/**
 * Load json data with given time window
 */
function loadData() {
	eachRecursive(vz.entities, function(entity, parent) {
		if (entity.active && entity.type != 'group') {
			$.getJSON(vz.options.backendUrl + '/data/' + entity.uuid + '.json', { from: vz.from, to: vz.to, tuples: vz.options.tuples }, ajaxWait(function(json) {
				vz.data.push({
					data: json.data[0].tuples,	// TODO check uuid
					color: entity.color
				});
			}, drawPlot, 'data'));
		}
	});
}

function drawPlot() {
	vz.options.plot.xaxis.min = vz.from;
	vz.options.plot.xaxis.max = vz.to;
	
	vz.plot = $.plot($('#plot'), vz.data, vz.options.plot);
}

