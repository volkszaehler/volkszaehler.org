/**
 * Some general functions we need for the frontend
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
 
var Exception = function(type, message, code) {
	return {
		type: type,
		message: message,
		code: code
	};
}
 
/**
 * Universal helper for middleware ajax requests with error handling
 */
vz.load = function(args) {
	$.extend(args, {
		accepts: 'application/json',
		error: function(xhr) {
			try {
				if (xhr.getResponseHeader('Content-type') == 'application/json') {
					var json = $.parseJSON(xhr.responseText);
				
					if (json.exception) {
						throw new Exception(json.exception.type, json.exception.message, (json.exception.code) ? json.exception.code : xhr.status);
					}
				}
				else {
					throw new Exception(xhr.statusText, 'Unknown middleware response', xhr.status)
				}
			}
			catch (e) {
				vz.wui.dialogs.exception(e);
			}
		}
	});
	
	if (args.url === undefined) { // local middleware by default
		args.url = vz.middleware[0].url;
	}
	
	if (args.url == vz.middleware[0].url) { // local request
		args.dataType = 'json';
	}
	else { // remote request
		args.dataType = 'jsonp';
		args.jsonp = 'padding';
	}
	
	if (args.controller !== undefined) {
		args.url += '/' + args.controller;
	}
	
	if (args.identifier !== undefined) {
		args.url += '/' + args.identifier;
	}
	
	args.url += '.json';
	
	return $.ajax(args);
};

/**
 * Parse URL GET parameters
 */
vz.parseUrlParams = function() {
	var vars = $.getUrlParams();
	var uuids = new Array;
	var save = false;
	
	for (var key in vars) {
		if (vars.hasOwnProperty(key)) {
			switch (key) {
				case 'uuid': // add optional uuid from url
					uuids = (typeof vars[key] == 'string') ? [vars[key]] : vars[key]; // handle multiple uuids
					break;
					
				case 'save': // save new uuids in cookie
					save = true;
					break;
					
				case 'from':
					vz.options.plot.xaxis.min = parseInt(vars[key]);
					break;
					
				case 'to':
					vz.options.plot.xaxis.max = parseInt(vars[key]);
					break;
			}
		}
	}
	
	uuids.each(function(index, uuid) {
		try {
			vz.entities.push(new Entity({
				middleware: vz.middleware[0].url,
				uuid: uuid,
				cookie: save
			}));
		} catch (exception) {
			/* ignore exception */
		}
	});
	
	if (save) {
		vz.entities.saveCookie();
	}
};

/**
 * Load capabilities from middleware
 */
vz.capabilities.load = function() {
	return vz.load({
		controller: 'capabilities',
		success: function(json) {
			$.extend(true, vz.capabilities, json.capabilities);
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
