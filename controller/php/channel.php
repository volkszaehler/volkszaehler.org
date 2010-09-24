<?php
header('Content-type: application/json');

$filename = 'channels.csv';
$channels = array();
$methodMapping = array(
	'POST'		=> 'add',
	'DELETE'	=> 'delete',
	'GET'		=> 'get'
);

$operation = (isset($_GET['operation'])) ? $_GET['operation'] : $methodMapping[$_SERVER['REQUEST_METHOD']];

// read channels
$fd = fopen($filename, 'r') or die('cant open file');
while (($data = fgetcsv($fd, 100, ';')) !== FALSE) {
	$channels[] = array(
		'uuid'				=> $data[0],
		'type'				=> $data[1],
		'port'				=> $data[2],
		'last_value'		=> $data[3],
		'last_timestamp'	=> $data[4]
	);
}
fclose($fd);

if ($operation == 'add') {
		$channels[] = array(
			'uuid'				=> $_GET['uuid'],
			'type'				=> $_GET['type'],
			'port'				=> $_GET['port']
		);
}
elseif ($operation == 'delete') {
	$channels = array_filter($channels, function($channel) {
		return $channel['uuid'] != $_GET['uuid'];
	});
}

if (in_array($operation, array('delete', 'add'))) {
	$fd = fopen($filename, 'w') or die('cant open file');
	foreach ($channels as $channel) {
		fputcsv($fd, $channel, ';');
	}
	fclose($fd);
}

// return all channels as JSON
echo json_encode($channels);

?>