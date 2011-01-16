/**
 * Some functions and prototypes which make our life easier
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
function waitAsync(callback, finished, identifier) {
	if (!waitAsync.counter) { waitAsync.counter = new Array(); }
	if (!waitAsync.counter[identifier]) { waitAsync.counter[identifier] = 0; }
	
	waitAsync.counter[identifier]++;
	
	return function (data, textStatus) {
		callback(data, textStatus);
		
		if (!--waitAsync.counter[identifier]) {
			finished();
		}
	};
}

var Exception = function(type, message, code) {
	return {
		type: type,
		message: message,
		code: code
	};
}

/*
 * Array extensions
 * according to js language specification ECMA 1.6
 */
Array.prototype.indexOf = function(n) {
	for (var i = 0, l = this.length; i < l; i++) {
		if (n == this[i]) return i;
	}
};

Array.prototype.remove = function(n) {
	this.splice(this.indexOf(n), 1);
};

Array.prototype.each = function(cb) {
	for (var i = 0, l = this.length; i < l; i++) {
		cb(i, this[i]);
	}
};

Array.prototype.contains = function(n) {
	return this.indexOf(n) !== undefined;
};

Array.prototype.clear = function() {
	this.length = 0;
}

Array.prototype.unique = function () {
	var r = new Array();
	this.each(function(key, value) {
		if (!r.contains(value)) {
			r.push(value);
		}
	});
	return r;
}
