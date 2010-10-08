/**
 * UUID handling
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
 * Read UUIDs from cookie and add them to the array
 */
vz.uuids.parseCookie = function() {
	if ($.getCookie('vz_uuids')) {
		$.each(JSON.parse($.getCookie('vz_uuids')), function(index, uuid) {
			vz.uuids.push(uuid);
		});
	}
};
	
/**
 * Add given UUID and update cookie
 */
vz.uuids.add = function(uuid) {
	if (vz.uuids.validate(uuid)) {
		if (!vz.uuids.contains(uuid)) {
			vz.uuids.push(uuid);
			$.setCookie('vz_uuids', JSON.stringify(vz.uuids));
		}
		else {
			throw 'UUID already added';
		}
	}
	else {
		throw 'Invalid UUID';
	}
};
	
/**
 * Remove UUID and update cookie
 */
vz.uuids.remove = function(uuid) {
	console.log(vz.uuids.contains(uuid));
	if (vz.uuids.contains(uuid)) {
		vz.uuids.splice(this.indexOf(uuid), 1);	// remove uuid from array
		$.setCookie('vz_uuids', JSON.stringify(vz.uuids));
	}
	else {
		throw 'UUID unkown: ' + uuid;
	}
};
	
/**
 * Validate UUID
 */
vz.uuids.validate = function(uuid) {
	return uuid.match(/^[0-9a-zA-Z]{8}-[0-9a-zA-Z]{4}-[0-9a-zA-Z]{4}-[0-9a-zA-Z]{4}-[0-9a-zA-Z]{12}$/);
};