/**
 * Some general functions we need for the frontend
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
 
/**
 * Helper function to wait for multiple ajax requests to complete
 */
vz.wait = function(callback, finished, identifier) {
	if (!vz.wait.counter) { vz.wait.counter = new Array(); }
	if (!vz.wait.counter[identifier]) { vz.wait.counter[identifier] = 0; }
	
	vz.wait.counter[identifier]++;
	
	return function (data, textStatus) {
		callback(data, textStatus);
		
		if (!--vz.wait.counter[identifier]) {
			finished();
		}
	};
};

/**
 * Universal helper for backend ajax requests with error handling
 */
vz.load = function(args) {
	$.extend(args, {
		url: this.options.backendUrl,
		dataType: 'json',
		error: function(xhr) {
			json = JSON.parse(xhr.responseText);
			vz.wui.dialogs.error(xhr.statusText, json.exception.message, xhr.status);
		}
	});
	
	if (args.context) {
		args.url += '/' + args.context;
	}
	if (args.identifier) {
		args.url += '/' + args.identifier;
	}
	args.url += '.json';

	$.ajax(args);
};

/**
 * Parse URL GET parameters
 */
vz.parseUrlParams = function() {
	var vars = $.getUrlParams();
	for (var key in vars) {
		if (vars.hasOwnProperty(key)) {
			switch (key) {
				case 'uuid': // add optional uuid from url
					var uuids = (typeof vars[key] == 'string') ? [vars[key]] : vars[key]; // handle multiple uuids
					uuids.each(function(index, uuid) {
						try { vz.uuids.add(uuid); } catch (exception) { /* ignore exception */ }
					});
					break;
					
				case 'from':
					vz.options.plot.xaxis.min = parseInt(vars[key]);
					break;
					
				case 'to':
					vz.options.plot.xaxis.max = parseInt(vars[key]);
					break;
					
				case 'debug':
					$.getScript('javascripts/firebug-lite.js');
					break;
			}
		}
	}
};

/**
 * Load capabilities from backend
 */
vz.capabilities.load = function() {
	vz.load({
		context: 'capabilities',
		identifier: 'definitions',
		success: function(json) {
			$.extend(true, vz.capabilities, json.capabilities);
		
			// load entity details
			vz.entities.loadDetails();
		}
	});
};

/**
 * Lookup definition
 */
vz.capabilities.definitions.get = function(section, name) {
	for (var i in this[section]) {
		if (this[section][i].name == name) {
			return this[section][i];
		}
	}
}
