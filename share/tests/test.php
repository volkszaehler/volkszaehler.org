<?php

/*
 * That's the volkszaehler.org configuration file.
 * Please take care of the following rules:
 * - you are allowed to edit it by your own
 * - anything else than the $config declaration
 *   will maybe be removed during the reconfiguration
 *   by the configuration parser!
 * - only literals are allowed as parameters
 * - expressions will be evaluated by the parser
 *   and saved as literals
 */

$config['db'] = array (
	'driver' => 'pdo_mysql',
	'host' => 'localhost',
	'user' => 'volkszaehler',
	'password' => '',
	'dbname' => 'volkszaehler',
);

$config['debug'] = false;

?>