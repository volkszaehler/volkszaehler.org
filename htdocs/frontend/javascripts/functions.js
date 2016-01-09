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
	var entities = [];
	var middleware = '';
	vz.entities.each(function(entity, parent) {
		if (entity.active) {
			if (entities.length === 0) {
				middleware = entity.middleware;
			}
			if (entity.middleware == middleware) {
				entities.push(entity);
			}
			else {
				// TODO add warning for entities from secondary MW
			}
		}
	}, true); // recursive!

	return entities[0].middleware + '/data.' + format + '?' + $.param({
		from: Math.floor(vz.options.plot.xaxis.min),
		to: Math.ceil(vz.options.plot.xaxis.max),
		uuid: entities.map(function(entity) {
			return entity.uuid;
		})
	});
};

/**
 * Build link to current viewport
 *
 * @return string url
 */
vz.getPermalink = function() {
	var uuids = [];
	vz.entities.each(function(entity, parent) {
		if (entity.active) {
			uuids.push(entity.uuid + '@' + 	entity.middleware);
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
		}
	});

	if (args.url === undefined) { // local middleware by default
		args.url = vz.options.middleware[0].url;
	}

	if (args.url == vz.options.middleware[0].url) { // local request
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

	return $.ajax(args).then(function(json) {
		// success
		if (json.exception) {
			// handle json exceptions sent with HTTP status 200
			vz.wui.dialogs.exception(new Exception(json.exception.type, args.url + ':<br/><br/>' + json.exception.message));
			return $.Deferred().reject();
		}
		return $.Deferred().resolveWith(this, [json]);
	}, function(xhr) {
		// error
		var msg;
		if (xhr.getResponseHeader('Content-type') == 'application/json') {
			var json = $.parseJSON(xhr.responseText);

			if (json.exception) {
				msg = xhr.requestUrl + ':<br/><br/>' + json.exception.message;
				vz.wui.dialogs.exception(new Exception(json.exception.type, msg, (json.exception.code) ? json.exception.code : xhr.status));
			}
		}
		else {
			msg = "<a href='" + xhr.requestUrl + "' style='text-decoration:none'>" + xhr.requestUrl + "</a>";
			if (xhr.responseText) {
				msg += '<br/><br/>' + $(xhr.responseText).text().substring(0,300);
			}

			var title = "Network Error";
			if (xhr.status > 0) {
				title += " (" + xhr.status + " " + xhr.statusText + ")";
			}
			else if (xhr.statusText !== "") {
				title += " (" + xhr.statusText + ")";
			}

			vz.wui.dialogs.exception(new Exception(title, msg));
		}
		return $.Deferred().reject();
	});
};

/**
 * Parse URL GET parameters
 */
vz.parseUrlParams = function() {
	var vars = $.getUrlParams();
	var entities = [];
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
				case 'to':
					// disable automatic refresh
					vz.options.refresh = false;
					// ms or speaking timestamp
					var ts = (/^-?[0-9]+$/.test(vars[key])) ? parseInt(vars[key]) : new Date(vars[key]).getTime();
					if (key == 'from')
						vz.options.plot.xaxis.min = ts;
					else
						vz.options.plot.xaxis.max = ts;
					break;

				case 'style': // explicitly set display style
				case 'fillstyle': // explicitly set fill style
				case 'linewidth': // explicitly set line width
				case 'group': // explicitly set data grouping
				case 'options': // data load options
					vz.options[key] = vars[key];
					break;
			}
		}
	}

	entities.each(function(index, identifier) {
		identifier = identifier.split('@');
		var uuid = identifier[0];
		var middleware = (identifier.length > 1) ? identifier[1] : vz.options.middleware[0].url;

		var entity = new Entity({
			uuid: uuid,
			middleware: middleware,
			cookie: save
		});

		// avoid double entries
		var knownEntity = false;
		vz.entities.each(function(entity) {
			if (entity.uuid == uuid) {
				knownEntity = true;
			}
		});

		if (!knownEntity) {
			vz.entities.push(entity);
		}
	});

	if (save) {
		vz.entities.saveCookie();
	}
};

/**
 * Get middleware by URL param
 */
vz.getMiddleware = function(url) {
	var mw = $.grep(vz.middleware, function(middleware) {
		if (url == middleware.url) {
			return true;
		}
	});

	if (mw.length) {
		return mw[0];
	}

	return null;
};

/**
 * Load capabilities from middleware
 */
vz.capabilities.load = function() {
	// execute query asynchronously to refresh from middleware
	var deferred = vz.load({
		controller: 'capabilities'
	}).done(function(json) {
		$.extend(true, vz.capabilities, json.capabilities);
		try {
			localStorage.setItem('vz.capabilities', JSON.stringify(json)); // cache it
		}
		catch (e) { }
	});

	// get cached value to avoid blocking frontend startup
	try {
		var json = localStorage.getItem('vz.capabilities');
		if (json !== false) {
			// use cached value and return immediately
			$.extend(true, vz.capabilities, JSON.parse(json).capabilities);
			return $.Deferred().resolve();
		}
	}
	catch (e) {	}

	return deferred;
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

/**
 * jQuery extensions
 */
(function($) {

	/**
	 * Deferred script loading
	 */
	$.cachedScript = function(url, options) {
		// Allow user to set any option except for dataType, cache, and url
		options = $.extend(options || {}, {
			dataType: "script",
			cache: true,
			url: url
		});
		// Use $.ajax() since it is more flexible than $.getScript
		// Return the jqXHR object so we can chain callbacks
		return $.ajax(options);
	};

	/**
	 * Serialize form including unchecked checkboxes
	 * http://stackoverflow.com/questions/3029870/jquery-serialize-does-not-register-checkboxes
	 *
	 * @todo make off value and default selection configurable
	 */
	$.fn.serializeArrayWithCheckBoxes = function() {
		// serialize form the non-checkbox fields
		return $(this).serializeArray()
		// add values for unchecked checkbox fields
		.concat(
			$(this).find("input[type=checkbox]:not(:checked)").map(function() {
				return { "name": this.name, "value": "0" };
			}).get()
		);
	};

})(jQuery);
