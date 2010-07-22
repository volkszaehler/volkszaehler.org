<?php
/**
 * simple script to import demo pulses
 *
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package tools
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
 * @todo adapt to doctrine dal or use native mysql
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