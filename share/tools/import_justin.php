<?php
/*
* Copyright (c) 2010 by Justin Otherguy <justin@justinotherguy.org>
* 
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License (either version 2 or
* version 3) as published by the Free Software Foundation.
*     
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*     
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*     
* For more information on the GPL, please go to:
* http://www.gnu.org/copyleft/gpl.html
*/

/*
 * simple script to import demo pulses
 */

// TODO adapt to doctrine dal or use native mysql

$sql = '';

$fd = fopen('../docs/developer/pulses.dummy.copy', 'r');
if ($fd) {
	while (!feof($fd)) {
		$buffer = explode("\t", fgets($fd));
		
		$ts = parsePgSqlTimestamp($buffer[0]);

		if ($ts > 0)
			$pulses[] = '(' . (int) ($buffer[2] + 1) . ', ' . $ts . ', 1)';
	};
	
	fclose($fd);
	
	$sql = 'INSERT INTO data (channel_id, timestamp, value) VALUES ' . implode(', ', $pulses);
	$dbh->execute($sql);
	
	echo 'Imported rows: ' . $dbh->affectedRows();
}
else {
	throw new Exception('cant open dump');
}

function parsePgSqlTimestamp($timestamp) {
	$unix = strtotime($timestamp);
	$ms = substr($timestamp, strrpos($timestamp, '.') + 1);
	
	return $unix + $ms/pow(10, strlen($ms));
}

?>