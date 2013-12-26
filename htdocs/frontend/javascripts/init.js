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
	entities: new Array,	// entity properties + data
	middleware: new Array,	// array of all known middlewares
	wui: {			// web user interface
		dialogs: { },
		timeout: null
	},
	capabilities: {		// debugging and runtime information from middleware
		definitions: {}	// definitions of entities & properties
	},
	plot: { },		// flot instance
	options: { }		// options loaded from cookies in options.js
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
		if (vz && vz.plot) {
			if (vz.plot.resize)
				vz.plot.resize();
			if (vz.plot.setupGrid)
				vz.plot.setupGrid();
			if (vz.plot.draw)
				vz.plot.draw();
		}
	});
	
	window.onerror = function(errorMsg, url, lineNumber) {
		vz.wui.dialogs.error('Javascript Runtime Error', errorMsg);
	};
	
	// initialize variables
	vz.middleware.push({ // default middleware
		url: vz.options.localMiddleware,
		title: 'Local (default)',
		public: [ ] // public entities
		/* capabilities: { } */
	});

	// remote middleware(s)
	vz.options.remoteMiddleware.forEach(function(middleware) {
		vz.middleware.push({
			url: middleware.url,
			title: middleware.title,
			public: [ ] // public entities
			/* capabilities: { } */
		});
	});
	
	// TODO make language/translation dependent (vz.options.language)
	vz.options.plot.xaxis.monthNames = vz.options.monthNames;
	vz.options.plot.xaxis.dayNames = vz.options.dayNames;
	
	// start loading cookies/url params
	vz.entities.loadCookie(); // load uuids from cookie
	vz.options.loadCookies(); // load options from cookie
	
	// set x axis limits _after_ loading options cookie
	vz.options.plot.xaxis.max = new Date().getTime(); 
	vz.options.plot.xaxis.min = vz.options.plot.xaxis.max - vz.options.interval;

	// parse additional url params (new uuid etc e.g. for permalink) after loading defaults
	vz.parseUrlParams();

	// initialize user interface
	vz.wui.init();
	vz.wui.initEvents();
	
	// chaining ajax request with jquery deferred object
	vz.capabilities.load().done(function() {
		if (vz.capabilities.formats.contains('png')) {
			$('#export option[value=png]').removeAttr('disabled');
		}
		
		var queue = new Array;
		vz.entities.each(function(entity) {
			var p = $.when(entity.loadDetails()).then(
				function(x) { return {result:x, resolved:true}; },
				function(x) { return (new $.Deferred).resolve({result:x, resolved:false}); }
			);
			queue.push(p);
		}, true);
		
		var cb = function(x) {
			if (vz.entities.length == 0) {
				vz.wui.dialogs.init();
			}
		
			vz.entities.showTable();
			vz.entities.loadData().done(vz.wui.drawPlot);
		};
		$.when.apply($, queue).done(cb);
	});
});	

