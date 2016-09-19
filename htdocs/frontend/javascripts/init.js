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
	entities: [],			// entity properties + data
	middleware: [],		// array of all known middlewares
	wui: {						// web user interface
		dialogs: { },
		timeout: null
	},
	capabilities: {		// debugging and runtime information from middleware
		definitions: {}	// definitions of entities & properties
	},
	plot: { },				// flot instance
	options: { }			// options loaded from cookies in options.js
};

/**
 * Executed on document loaded complete
 * this is where it all starts...
 */
$(document).ready(function() {
	window.onerror = function(errorMsg, url, lineNumber) {
		vz.wui.dialogs.error('Javascript Runtime Error', errorMsg);
	};

	// add timezone-js support
	if (timezoneJS !== undefined && timezoneJS.Date !== undefined) {
		timezoneJS.timezone.zoneFileBasePath = "tz";
		timezoneJS.timezone.defaultZoneFile = [];
		timezoneJS.timezone.init({ async: false });
	}

	// middleware(s)
	vz.options.middleware.forEach(function(middleware) {
		vz.middleware.push($.extend(middleware, {
			public: [ ], // public entities
			session: null // WAMP session
			/* capabilities: { } */
		}));
	});

	// TODO make language/translation dependent (vz.options.language)
	vz.options.plot.xaxis.monthNames = vz.options.monthNames;
	vz.options.plot.xaxis.dayNames = vz.options.dayNames;

	// clear cookies and localStorage cache
	var params = $.getUrlParams();
	if (params.hasOwnProperty('reset') && params.reset) {
		$.setCookie('vz_entities', null);
		try {
			localStorage.removeItem('vz.capabilities');
		}
		catch (e) { }
	}

	// start loading cookies/url params
	vz.entities.loadCookie(); // load uuids from cookie
	vz.options.loadCookies(); // load options from cookie

	// set x axis limits _after_ loading options cookie
	vz.options.plot.xaxis.max = new Date().getTime();
	vz.options.plot.xaxis.min = vz.options.plot.xaxis.max - vz.options.interval;

	// parse additional url params (new uuid etc e.g. for permalink) after loading defaults
	vz.parseUrlParams();

	// initialize user interface (may need to wait for onLoad on Safari)
	vz.wui.init();

	// chaining ajax request with jquery deferred object
	vz.capabilities.load().done(function() {
		vz.entities.loadDetails().done(function() {
			if (vz.entities.length === 0) {
				vz.wui.dialogs.init();
			}

			// create table and apply initial state
			vz.entities.showTable();
			vz.entities.inheritVisibility();

			vz.entities.loadData().done(function() {
				vz.wui.drawPlot();
				vz.entities.loadTotalConsumption();
			});

			// create WAMP sessions for each middleware
			vz.middleware.each(function(idx, middleware) {
				var parser = document.createElement('a');
				parser.href = middleware.url;
				var host = parser.hostname || location.host; // location object for IE
				var protocol = (parser.protocol || location.protocol).toLowerCase().indexOf("https") === 0 ? "wss" : "ws";
				var uri = protocol + "://" + host;

				// try uri if nothing configured - requires Apache ProxyPass, see
				// https://github.com/volkszaehler/volkszaehler.org/issues/382
				if (isNaN(parseFloat(middleware.live))) {
					if (parser.port) {
						uri += ":" + parser.port;
					}
					// if Apache ProxyPass is used, connect with http(s) but always forward to unencrypted port
					uri += "/ws"; // parser.pathname.replace(/(\.\.\/)?middleware.php$/, "ws")
					console.info("Live updates not configured. Trying default path at " + uri);
				}
				else {
				 	// use dedicated port
				 	uri += ":" + middleware.live;
				}

				// connect and store session
				new ab.connect(uri, function(session) {
					console.log("Autobahn connected to " + uri + " (" + session.sessionid() + ")");
					middleware.session = session;

					// subscribe entities
					vz.entities.each(function(entity) {
						if (entity.active && entity.middleware.indexOf(middleware.url) >= 0) {
							entity.subscribe(session);
						}
					}, true);
				}, function(code, reason) {
					console.log("Autobahn disconnected (" + code + ", " + reason + ")");
					delete middleware.session;
				});
			});
		});
	});
});
