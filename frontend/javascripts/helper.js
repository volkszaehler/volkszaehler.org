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

function ajaxWait(callback, finished, identifier) {
	if (!ajaxWait.counter) { ajaxWait.counter = new Array(); }
	if (!ajaxWait.counter[identifier]) { ajaxWait.counter[identifier] = 0; }
	
	ajaxWait.counter[identifier]++;
	
	return function (data, textStatus) {
		callback(data, textStatus);
		
		if (!--ajaxWait.counter[identifier]) {
			finished();
		}
	};
}

function eachRecursive(array, callback, parent) {
	$.each(array, function(index, value) {
		callback(value, parent);
		
		if (value.children) {	// has children?
			eachRecursive(value.children, callback, value);	// call recursive
		}
	});
}

/**
 * Checks if value of part of the array
 * 
 * @param needle the value to search for
 * @return boolean
 */
Array.prototype.contains = function(needle) {
	return this.key(needle) ? true : false;
};

/**
 * Calculates the diffrence between this and another Array
 * 
 * @param compare the Array to compare with
 * @return array
 */
Array.prototype.diff = function(compare) {
	return this.filter(function(elem) {
		return !compare.contains(elem);
	});
};

/**
 * Find the key to an given value
 * 
 * @param needle the value to search for
 * @return integer
 */
Array.prototype.key = function(needle) {
	for (var i=0; i<this.length; i++) {
		if (this[i] == needle) {
			return i;
		}
	}
};

/**
 * Remove a value from the array
 */
Array.prototype.remove = function(needle) {
	var key = this.key(needle);
	if (key) {
		this.splice(key, 1);
	}
};