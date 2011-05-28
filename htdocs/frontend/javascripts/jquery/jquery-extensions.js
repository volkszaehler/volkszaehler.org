/**
 * jQuery extensions
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
 * Get URL parameters
 */
$.extend( {
	getUrlParams : function() {
		var vars = {}, hash;
		var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
		
		for (var i = 0; i < hashes.length; i++) {
			hash = hashes[i].split('=');
			switch (typeof vars[hash[0]]) {
				case 'undefined':
					vars[hash[0]] = hash[1];
					break;
					
				case 'string':
					vars[hash[0]] = Array(vars[hash[0]]);
					
				case 'object':
					vars[hash[0]].push(hash[1]);
			}
		}
		return vars;
	},
	getUrlParam : function(name) {
		return $.getUrlParams()[name];
	}
});

/**
 * Cookie plugin
 *
 * @author Klaus Hartl  <klaus.hartl@stilbuero.de>
 * @author Steffen Vogel <info@steffenvogel.de>
 * 
 * Copyright (c) 2006 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 */
$.extend({
	setCookie: function(name, value, options) {
		options = options || { };
		
		if (value === null) {
			value = '';
			options.expires = -1;
		}
		
		var expires = '';
		if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
			var date;
			if (typeof options.expires == 'number') { // expires x seconds in the future
				date = new Date();
				date.setTime(date.getTime()
						+ (options.expires * 24 * 60 * 60 * 1000));
			} else {
				date = options.expires;
			}
			expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
		}
		
		// CAUTION: Needed to parenthesize options.path and options.domain
		// in the following expressions, otherwise they evaluate to undefined
		// in the packed version for some reason...
		var path = options.path ? '; path=' + (options.path) : '';
		var domain = options.domain ? '; domain=' + (options.domain) : '';
		var secure = options.secure ? '; secure' : '';
		
		document.cookie = name + '=' + encodeURIComponent(value) + expires + path + domain + secure;
	},
	getCookie: function(name) {
		if (document.cookie && document.cookie != '') {
			var cookies = document.cookie.split(';');
			for (var i = 0; i < cookies.length; i++) {
				var cookie = $.trim(cookies[i]);
				// Does this cookie string begin with the name we want?
				if (cookie.substring(0, name.length + 1) == (name + '=')) {
					return decodeURIComponent(cookie.substring(name.length + 1));
				}
			}
		}
	}
});
