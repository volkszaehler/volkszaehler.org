/**
 * Some general functions we need for the frontend
 *
 * @author Florian Ziegler <fz@f10-home.de>
 * @author Justin Otherguy <justin@justinotherguy.org>
 * @author Steffen Vogel <info@steffenvogel.de>
 * @copyright Copyright (c) 2011-2020, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
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
		if (entity.isChannel() && entity.active) {
			if (entities.length === 0) {
				middleware = entity.middleware;
			}
			if (entity.middleware.indexOf(middleware) >= 0) {
				entities.push(entity);
			}
			else {
				// TODO add warning for entities from secondary MW
			}
		}
	}, true); // recursive!

	var params = $.extend($.getUrlParams(), {
		from: Math.floor(vz.options.plot.xaxis.min),
		to: Math.ceil(vz.options.plot.xaxis.max),
		uuid: entities.map(function(entity) {
			return entity.uuid;
		})
	});

	return entities[0].middleware + '/data.' + format + '?' + $.param(params);
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
			var uuid = entity.uuid + '@' + 	entity.middleware;
			if (uuids.indexOf(uuid) < 0) {
				uuids.push(uuid);
			}
		}
	});

	var params = $.extend($.getUrlParams(), {
		from: Math.floor(vz.options.plot.xaxis.min),
		to: Math.ceil(vz.options.plot.xaxis.max),
		uuid: uuids,
		mode: vz.options.mode
	});

	return window.location.protocol + '//' + window.location.host + window.location.pathname + '?' + $.param(params);
};

/**
 * Universal helper for middleware ajax requests with error handling
 *
 * @param skipDefaultErrorHandling according to http://stackoverflow.com/questions/19101670/provide-a-default-fail-method-for-a-jquery-deferred-object
 */
vz.load = function(args, skipDefaultErrorHandling) {
	if (vz.wui.requests.issued++ === 0) {
		NProgress.start();
	}

	$.extend(args, {
		accepts: {
			'json': 'application/json'
		},
		beforeSend: function (xhr, settings) {
			// remember URL for potential error messages
			xhr.requestUrl = settings.url;
		}
	});

	if (args.url === undefined) { // local middleware by default
		args.url = vz.options.middleware[0].url;
	}

	if (args.controller !== undefined) {
		args.url += '/' + args.controller;
	}

	if (args.identifier !== undefined) {
		args.url += '/' + args.identifier;
	}

	args.url += '.json';

	// workaround Safari 11 cache bug
	if (args.method === undefined || args.method == 'GET') {
		args.url += '?unique=' + Date.now();
	}

	if (args.data === undefined) {
		args.data = { };
	}

	return vz.load.loadHandler(args, skipDefaultErrorHandling);
};

/**
 * Reusable ajax request sender with error handling
 */
vz.load.loadHandler = function (args, skipDefaultErrorHandling) {
	return $.ajax(args).always(function (res) {
		NProgress.set(++vz.wui.requests.completed / vz.wui.requests.issued);
		if (vz.wui.requests.completed == vz.wui.requests.issued) {
			vz.wui.requests.issued = vz.wui.requests.completed = 0;
		}
	}).then(
		// success
		function (json, error, xhr) {
			// ensure json response - might still be server error
			if (!xhr.responseJSON) {
				vz.load.errorHandler(xhr);
				$.Deferred().rejectWith(this, [xhr]);
			}
			return $.Deferred().resolveWith(this, [json]);
		},
		// error
		function (xhr) {
			return vz.load.errorHandler(xhr, skipDefaultErrorHandling);
		}
	);
};

/**
 * Reusable authorization-aware error handler
 */
vz.load.errorHandler = function(xhr, skipDefaultErrorHandling) {
	if (!skipDefaultErrorHandling) {
		vz.wui.dialogs.middlewareException(xhr);
	}
	return xhr;
};

/**
 * Parse URL GET parameters
 */
vz.parseUrlParams = function() {
	var vars = $.getUrlParams();
	var entities = [];
	var save = false;
	var active = null;

	for (var key in vars) {
		if (vars.hasOwnProperty(key)) {
			switch (key) {
				case 'uuid': // add optional uuid from url
					entities = (typeof vars[key] == 'string') ? [vars[key]] : vars[key]; // handle multiple uuids
					break;

				case 'save': // save new uuids in cookie
					save = vars[key];
					break;

				case 'active': // define active/deactive state for uuid from url. Set to deactive when set to FALSE, F, NO or 0 (case insensitive)
					active = !/^(?:f(?:alse)?|no?|0+)$/i.test(vars[key]);
					break;

				case 'from':
				case 'to':
					if (key == 'to') {
						// disable automatic refresh
						vz.options.refresh = false;
					}
					// ms or speaking (relative) timestamp
					var ts_param = vars[key];
					var ts = null;
					if (/^-?[0-9]+$/.test(ts_param)) { 
						// string contains only numbers => it's a timestamp
						ts = parseInt(ts_param);
					} else {
						// string contains something else, parse it with strtotime
						parsedDate = strtotime(ts_param);
						// return time in ms if strtotime was successful
						ts = parsedDate === false ? null : parsedDate * 1000;
					}
					if (ts != null && !isNaN(ts)) {
						if (key == 'from')
							vz.options.plot.xaxis.min = ts;
						else
							vz.options.plot.xaxis.max = ts;
					}
					break;

				case 'mode': // explicitly set display mode
				case 'style': // explicitly set display style
				case 'fillstyle': // explicitly set fill style
				case 'linewidth': // explicitly set line width
				case 'group': // explicitly set data grouping
				case 'options': // data load options
					vz.options[key] = vars[key];
					break;

				case 'hide':
					$(vars[key]).hide();
					break;
			}
		}
	}

	entities.forEach(function(identifier) {
		identifier = identifier.split('@');
		var uuid = identifier[0];
		var middleware = (identifier.length > 1) ? identifier[1] : vz.options.middleware[0].url;

		var entity = new Entity({
			uuid: uuid,
			middleware: middleware,
			cookie: save,
			active: active
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
 * Load capabilities from middleware
 */
vz.capabilities.load = function() {
	return vz.load({
		controller: 'capabilities/definitions'
	}).done(function(json) {
		$.extend(vz.capabilities.definitions, json.capabilities.definitions);
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

  var slice = [].slice;

  // https://gist.github.com/fearphage/4341799
  $.whenAll = function(array) {
    var
			/* jshint laxbreak: true */
      resolveValues = arguments.length == 1 && Array.isArray(array)
        ? array
        : slice.call(arguments),
      length = resolveValues.length,
      remaining = length,
      deferred = $.Deferred(),
      i = 0,
      failed = 0,
      rejectContexts = Array(length),
      rejectValues = Array(length),
      resolveContexts = Array(length),
      value
    ;

    function updateFunc (index, contexts, values) {
      return function() {
        if (values !== resolveValues) {
          failed++;
        }
        deferred.notifyWith(
         contexts[index] = this,
         values[index] = slice.call(arguments)
        );
        if (!(--remaining)) {
          deferred[(!failed ? 'resolve' : 'reject') + 'With'](contexts, values);
        }
      };
    }

    for (; i < length; i++) {
      if ((value = resolveValues[i]) && (typeof value.promise === "function")) {
        value.promise()
          .done(updateFunc(i, resolveContexts, resolveValues))
          .fail(updateFunc(i, rejectContexts, rejectValues))
        ;
      }
      else {
        deferred.notifyWith(this, value);
        --remaining;
      }
    }

    if (!remaining) {
      deferred.resolveWith(resolveContexts, resolveValues);
    }

    return deferred.promise();
  };

})(jQuery);
