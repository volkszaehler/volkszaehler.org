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

// class autoloading
require 'lib/vendor/doctrine/Common/ClassLoader.php';

$doctrineLoader = new \Doctrine\Common\ClassLoader('Doctrine', 'lib/vendor/doctrine');
$doctrineLoader->register(); // register on SPL autoload stack

$vzLoader = new \Doctrine\Common\ClassLoader('Volkszaehler', 'lib');
$vzLoader->register(); // register on SPL autoload stack

// API version
define('VERSION', '0.2');

// enable strict error reporting
error_reporting(E_ALL);

// load configuration into registry
if (!file_exists(__DIR__ . '/volkszaehler.conf.php')) {
	throw new Exception('No configuration available! Use volkszaehler.conf.default.php as an template');
}

include __DIR__ . '/volkszaehler.conf.php';

$fc = new FrontController();	// spawn frontcontroller
$fc->run();						// execute controller and sends output

?>
