/**
 * Initialization and configuration of frontend
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

// volkszaehler.org namespace (holds all data, options and functions for the frontend)
// we dont want to pollute the global namespace
var vz = {
	// entity properties + data
	entities: new Array,

	// web user interface
	wui: {
		dialogs: { }
	},
	
	// known UUIDs in the browser
	uuids: new Array,
	
	// flot instance
	plot: { },
	
	// definitions of entities & properties
	// for validation, translation etc..
	definitions: { },

	// options loaded from cookies in options.js
	options: { }
};

// check for debugging & load firebug
if ($.getUrlVar('debug')) {
	$.getScript('javascripts/firebug-lite.js');
}

// executed on document loaded complete
// this is where it all starts...
$(document).ready(function() {
	$(window).unload(function() {
		vz.uuids.save();
		vz.options.save();
	});

	$(window).resize(function() {
		vz.options.tuples = Math.round($('#flot').width() / 3);
		$('#tuples').val(vz.options.tuples);
		vz.drawPlot();
	});

	// parse uuids & options from cookie
	vz.uuids.load();
	//vz.options.load();

	// initialize user interface
	vz.wui.init();
	vz.wui.initEvents();
	vz.wui.dialogs.init();
	
	// add optional uuid from url
	if($.getUrlVar('uuid')) {
		vz.uuids.add($.getUrlVar('uuid'));
	}
	
	if (vz.uuids.length == 0) {
		$('#addUUID').dialog('open');
	}
	
	vz.definitions.load();
	vz.entities.loadDetails();
});
