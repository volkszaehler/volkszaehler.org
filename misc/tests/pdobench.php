<?php
/**
 * Some testing for PDOs performence
 *
 * @package tests
 * @copyright Copyright (c) 2011, The volkszaehler.org project
 * @license http://www.gnu.org/licenses/gpl.txt GNU Public License
 * @author Steffen Vogel <info@steffenvogel.de>
 */
/*
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

$start = microtime(true);

$dbh = new PDO('mysql:host=localhost;dbname=volkszaehler', 'vz', 'demo');

$attr = array();
$attr[PDO::ATTR_CURSOR] = PDO::CURSOR_SCROLL;

$stmt = $dbh->prepare('SELECT timestamp, value FROM data LEFT JOIN channels ON data.channel_id = channels.id WHERE channels.indicator = \'temperature\'', $attr);

for ($i = 0; $i < 1000; $i++) {

	$stmt->execute();
	$stmt->setFetchMode(PDO::FETCH_ASSOC);

	$c = 0;
	foreach($stmt as $row) {
	}

}

echo $stmt->rowCount() . '<br />';

echo 'time: ' . round(microtime(true) - $start, 6);

?>