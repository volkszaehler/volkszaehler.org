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

define('VZ_VERSION', '0.1');

/*
 * class autoloading
 */
function __autoload($className) {
	$libs = __DIR__ . '/lib/';

	// preg_replace pattern class name => inclulde path
	$mapping = array(
	// util classes
		'/^Registry$/'								=> 'util/registry',
		'/^Uuid$/'									=> 'util/uuid',

	// model classes
		'/^(Channel|User|Group|Database(Object)?)$/'=> 'model/$1',
		'/^(MySql|PgSql|SqLite)$/i'					=> 'model/db/$1',
		'/^(.+(Meter|Sensor))$/'					=> 'model/channel/$2/$1',
		'/^(Meter|Sensor)$/'						=> 'model/channel/$1',

	// view classes
		'/^(Http.*)$/'								=> 'view/http/$1',
		'/^(.*View)$/'								=> 'view/$1',

	// controller classes
		'/^(.*Controller)$/'						=> 'controller/$1'
		);

	$include = $libs . strtolower(preg_replace(array_keys($mapping), array_values($mapping), $className)) . '.php';

	if (empty($include)) {
		throw new Exception('Cannot load class ' . $className . '! Name not mapped.');
	}

	if (!file_exists($include)) {
		throw new Exception('Cannot load class ' . $className . '! File does not exist: ' . $include);
	}

	require_once $include;
}

// enable strict error reporting
error_reporting(E_ALL);

// load configuration into registry
if (!file_exists(__DIR__ . '/volkszaehler.conf.php')) {
	throw new Exception('No configuration available! Use volkszaehler.conf.default.php as an template');
}

include __DIR__ . '/volkszaehler.conf.php';

?>