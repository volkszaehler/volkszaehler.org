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
 * Add given UUID and update cookie
 */
vz.uuids.add = function(uuid) {
	if (this.validate(uuid)) {
		if (!this.contains(uuid)) {
			this.push(uuid);
			this.save();
		}
		else {
			throw new Exception('UUIDException', 'UUID already added: ' + uuid);
		}
	}
	else {
		throw new Exception('UUIDException', 'Invalid UUID');
	}
};
	
/**
 * Remove UUID and update cookie
 */
vz.uuids.remove = function(uuid) {
	if (this.contains(uuid)) {
		this.splice(this.indexOf(uuid), 1);	// remove uuid from array
		this.save();
	}
	else {
		throw new Exception('UUIDException', 'UUID unkown: ' + uuid);
	}
};
	
/**
 * Validate UUID
 */
vz.uuids.validate = function(uuid) {
	return uuid.match(/^[0-9a-zA-Z]{8}-[0-9a-zA-Z]{4}-[0-9a-zA-Z]{4}-[0-9a-zA-Z]{4}-[0-9a-zA-Z]{12}$/);
};

/**
 * Save uuids as cookie
 */
vz.uuids.save = function() {
	$.setCookie('vz_uuids', this.join(';'));
};

/**
 * Load uuids from cookie
 */
vz.uuids.load = function() {
	var cookie = $.getCookie('vz_uuids');
	if (cookie) {
		cookie.split(';').each(function(index, uuid) {
			vz.uuids.add(uuid);
		});
	}
};
