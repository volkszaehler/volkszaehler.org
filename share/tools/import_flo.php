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

$sql = '';
$pulses = array();

// initialize db connection
mysql_connect('localhost', 'vz', 'demo');
mysql_select_db('volkszaehler_doctrine');

// dump => db channel id mapping
$mapping[4] = 22;
$mapping[5] = 23;
$mapping[9] = 24;
$mapping[10] = 25;

$fd = fopen('/home/steffen/Desktop/testdaten_nicht_veroeffentlichen.sql', 'r');
if ($fd) {
	while (!feof($fd)) {
		$line = fgets($fd);
		
		// $matches index	1			2		3		4		5		6		7		8
		if (preg_match('/^\((\d), \'(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})\', (\d)/', $line, $matches)) {
			
			$ts = mktime($matches[5], $matches[6], $matches[7], $matches[3], $matches[4], $matches[2]) * 1000;
			$value = $matches[8];
			$channel = $mapping[$matches[1]];
	
			if ($ts > 0) {
				$pulses[] = '(' . $channel . ', ' . $ts . ', ' . $value . ')';
			}
			
			if (count($pulses) % 1000 == 0) {
				$sql = 'INSERT INTO data (channel_id, timestamp, value) VALUES ' . implode(', ', $pulses);
				if (!mysql_query($sql)){
					echo mysql_error();
				}
				
				echo 'Rows inserted: ' . mysql_affected_rows() . '<br />';
				
				flush();
				$pulses = array();
			}
		}
	};
	
	fclose($fd);
}

?>