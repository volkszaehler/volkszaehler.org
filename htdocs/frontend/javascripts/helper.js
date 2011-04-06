/**
 * Some functions and prototypes which make our life easier
 * 
 * not volkszaehler.org related
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

Array.prototype.each = function(cb, ctx) {
	for (var i = 0, l = this.length; i < l; i++) {
		if (cb.call((ctx === undefined) ? this[i] : ctx, i, this[i]) === false) {
			break;
		}
	}
};

Array.prototype.contains = function(n) {
	return this.indexOf(n) !== undefined;
};

Array.prototype.clear = function() {
	this.length = 0;
}

Array.prototype.unique = function() {
	var r = new Array;
	this.each(function(key, value) {
		if (!r.contains(value)) {
			r.push(value);
		}
	});
	return r;
}

Array.prototype.last = function() {
	if (this.length > 0) {
		return this[this.length-1];
	}
}
