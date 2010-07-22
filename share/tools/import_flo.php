<?php
/**
 * simple script to import demo pulses
 *
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package tools
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
 *
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