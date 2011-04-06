/**
 * Initialization and configuration of frontend
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

/**
 * volkszaehler.org namespace
 *
 * holds all data, options and functions for the frontend
 * we dont want to pollute the global namespace
 */
var vz = {
	// entity properties + data
	entities: new Array, // TODO new Entity?

	// web user interface
	wui: {
		dialogs: { },
		timeout: null
	},
	
	// known UUIDs in the browser
	uuids: new Array,
	
	// flot instance
	plot: { },
	
	// debugging and runtime information from middleware
	capabilities: {
		definitions: { } // definitions of entities & properties
	},

	// options loaded from cookies in options.js
	options: { }
};

/**
 * Executed on document loaded complete
 * this is where it all starts...
 */
$(document).ready(function() {
	// late binding
	$(window).resize(function() {
		vz.options.tuples = Math.round($('#flot').width() / 3);
		$('#tuples').val(vz.options.tuples);
		vz.wui.drawPlot();
	});
	
	window.onerror = function(errorMsg, url, lineNumber) {
		vz.wui.dialogs.error('Javascript Runtime Error', errorMsg);
	};

	vz.uuids.load(); // load uuids from cookie
	vz.options.load(); // load options from cookie
	vz.parseUrlParams(); // parse additional url params (new uuid etc..)

	// initialize user interface
	vz.wui.init();
	vz.wui.initEvents();

	if (vz.uuids.length == 0) {
		$('#entity-add').dialog('open');
	}
	
	// chaining ajax request with jquery deferred object
	vz.capabilities.load().done(function() {
		if (vz.capabilities.formats.contains('png')) {
			$('#snapshot').show();
		}
		
		vz.entities.loadDetails().done(function(a, b, c, d) {
			vz.entities.showTable();
			vz.entities.loadData().done(vz.wui.drawPlot);
		});
	});
});	

