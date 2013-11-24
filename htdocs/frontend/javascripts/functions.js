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
};

/**
 * Build link to rendered image of current viewport
 *
 * @return string url
 */
vz.getLink = function(format) {
	var entities = new Array;
	vz.entities.each(function(entity, parent) {
		if (entity.active) {
			entities.push(entity);
		}
	}, true); // recursive!
	
	var entity = entities[0]; // TODO handle other entities
	
	return entity.middleware + '/data/' + entity.uuid + '.' + format + '?' + $.param({
		from: Math.floor(vz.options.plot.xaxis.min),
		to: Math.ceil(vz.options.plot.xaxis.max)
	});
};

/**
 * Build link to current viewport
 *
 * @return string url
 */
vz.getPermalink = function() {
	var uuids = new Array;
	vz.entities.each(function(entity, parent) {
		if (entity.active) {
			if (entity.middleware != vz.options.localMiddleware) {
				uuids.push(entity.uuid + '@' + 	entity.middleware);
			}
			else {
				uuids.push(entity.uuid);
			}
		}
	});
	
	var params = {
		from: Math.floor(vz.options.plot.xaxis.min),
		to: Math.ceil(vz.options.plot.xaxis.max),
		uuid: uuids.unique()
	};
	
	return window.location.protocol + '//' + window.location.host + window.location.pathname + '?' + $.param(params);
};
 
/**
 * Universal helper for middleware ajax requests with error handling
 */
vz.load = function(args) {
	$.extend(args, {
		accepts: 'application/json',
 		beforeSend: function (xhr, settings) {
 			// remember URL for potential error messages
 			xhr.requestUrl = settings.url;
 		},
		error: function(xhr) {
			try {
				if (xhr.getResponseHeader('Content-type') == 'application/json') {
					var json = $.parseJSON(xhr.responseText);
				
					if (json.exception) {
						var msg = xhr.requestUrl + ':<br/><br/>' + json.exception.message;
						throw new Exception(json.exception.type, msg, (json.exception.code) ? json.exception.code : xhr.status);
					}
				}
				else {
					var msg = xhr.requestUrl + ':<br/><br/>Unknown middleware response';
					if (xhr.responseText) {
						msg += '<br/><br/>' + $(xhr.responseText).text().substring(0,300);
					}
					throw new Exception(xhr.statusText, msg, xhr.status)
				}
			}
			catch (e) {
				vz.wui.dialogs.exception(e);
			}
		}
	});
	
	if (args.url === undefined) { // local middleware by default
		args.url = vz.options.localMiddleware;
	}
	
	if (args.url == vz.options.localMiddleware) { // local request
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
	
	if (args.data === undefined) {
		args.data = { };
	}
	
	if (args.type) {
		var operationMapping = {
			post:	'add',
			delete:	'delete',
			get:	'get',
			pull:	'edit'
		};
	
		args.data.operation = operationMapping[args.type.toLowerCase()];
		delete args.type; // this makes jquery append the data to the query string
	}
	
	return $.ajax(args);
};

/**
 * Parse URL GET parameters
 */
vz.parseUrlParams = function() {
	var vars = $.getUrlParams();
	var entities = new Array;
	var save = false;
	
	for (var key in vars) {
		if (vars.hasOwnProperty(key)) {
			switch (key) {
				case 'uuid': // add optional uuid from url
					entities = (typeof vars[key] == 'string') ? [vars[key]] : vars[key]; // handle multiple uuids
					break;
					
				case 'save': // save new uuids in cookie
					save = vars[key];
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
	
	entities.each(function(index, identifier) {
		var identifier = identifier.split('@');
		var uuid = identifier[0];
		var middleware = (identifier.length > 1) ? identifier[1] : vz.options.localMiddleware;
		
		vz.entities.push(new Entity({
			uuid: uuid,
			middleware: middleware,
			cookie: save
		}));
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
};
