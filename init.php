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
 * class autoloading
 */
function __autoload($className) {
	$libs = __DIR__ . '/lib/';
	
	require_once $libs . 'util/exceptions.php';
	
	// preg_replace pattern => replace mapping
	$mapping = array(
					'/.*Exception$/'			=> 'util/exceptions',
					'/^Registry$/'			=> 'util/registry',
					'/^Database$/'			=> 'db/database',
					'/^Channel$/'			=> 'channel/channel',
					'/(.*(Meter|Sensor))/i'	=> 'channel/$2/$1',
					'/(Http.*)/'			=> 'http/$1',
					'/(.*sql.*)/i'			=> 'db/$1',
					'/(.*Controller)/'		=> 'controller/$1');
	
	foreach ($mapping as $pattern => $replacement) {
		$className = preg_replace($pattern, $replacement, $className);
	}
	
	$className = strtolower($className);
	
	if (file_exists($libs . $className . '.php')) {
		require_once $libs . $className . '.php';
	}
	else {
		throw new CustomException('Cannot load class! Name not mapped: ' . $className);
	}
}

// enable strict error reporting
error_reporting (E_ALL);

// lets handle all php errors as exceptions
set_error_handler(array('CustomErrorException', 'errorHandler'));

// load configuration into registry
if (file_exists('volkszaehler.conf.php')) {
	throw new CustomException('No configuration available! Use volkszaehler.conf.php as an template');
}

include 'volkszaehler.conf.php';

?>