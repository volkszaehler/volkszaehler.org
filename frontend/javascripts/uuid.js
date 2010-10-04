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

/*
 * Cookie & UUID related functions
 */
function getUUIDs() {
	if ($.getCookie('uuids')) {
		return JSON.parse($.getCookie('uuids'));
	}
	else {
		return new Array;
	}
}

function addUUID(uuid) {
	if (!vz.uuids.contains(uuid)) {
		vz.uuids.push(uuid);
		$.setCookie('uuids', JSON.stringify(vz.uuids));
	}
}

function removeUUID(uuid) {
	if (vz.uuids.contains(uuid)) {
		vz.uuids.remove(uuid);
		$.setCookie('uuids', JSON.stringify(vz.uuids));
	}
}

/**
 * Create and return a "version 4" RFC-4122 UUID string
 * 
 * @todo remove after got backend handling working
 */
function getRandomUUID() {
	var s = [], itoh = '0123456789ABCDEF';

	// make array of random hex digits. The UUID only has 32 digits in it, but we
	// allocate an extra items to make room for the '-'s we'll be inserting.
	for (var i = 0; i <36; i++) s[i] = Math.floor(Math.random()*0x10);

	// conform to RFC-4122, section 4.4
	s[14] = 4;  // Set 4 high bits of time_high field to version
	s[19] = (s[19] & 0x3) | 0x8;  // Specify 2 high bits of clock sequence

	// convert to hex chars
	for (var i = 0; i <36; i++) s[i] = itoh[s[i]];

	// insert '-'s
	s[8] = s[13] = s[18] = s[23] = '-';

	return s.join('');
}