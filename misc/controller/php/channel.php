<?php
/**
 * PHP implementation of the controller API
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package controller
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
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

header('Content-type: application/json');

define('FILE', 'channels.csv');

$methodMapping = array(
	'POST'		=> 'add',
	'DELETE'	=> 'delete',
	'GET'		=> 'get'
);

$channels = array();

$operation = (isset($_GET['operation'])) ? $_GET['operation'] : $methodMapping[$_SERVER['REQUEST_METHOD']];

// create file if it doesnt exist
touch(FILE);

// read channels
$fd = fopen(FILE, 'r') or die('cant open file');
while (($data = fgetcsv($fd, 100, ';')) !== FALSE) {
	$channels[] = array_combine(array('uuid', 'type', 'port', 'last_value', 'last_timestamp'), $data);
}
fclose($fd);

if ($operation == 'add') {
	// TODO check for duplicate uuid
	// TODO validate input
		$channels[] = array(
			'uuid'		=> $_GET['uuid'],
			'type'		=> $_GET['type'],
			'port'		=> $_GET['port'],
			'value'		=> '',
			'timestamp'	=> ''
		);
}
elseif ($operation == 'delete') {
	$channels = array_filter($channels, function($channel) {
		return $channel['uuid'] != $_GET['uuid'];
	});
}

// save channels
if (in_array($operation, array('delete', 'add'))) {
	$fd = fopen(FILE, 'w') or die('cant open file');
	foreach ($channels as $channel) {
		fputcsv($fd, $channel, ';');
	}
	fclose($fd);
}

// return all channels as JSON
echo json_encode($channels);

?>