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

namespace Volkszaehler;

use Volkszaehler\Util;
use Volkszaehler\Controller;

// TODO replace by state class
const VERSION = 1.1;
const BACKEND_DIR = '/home/steffen/workspace/volkszaehler.org/backend';	// TODO realpath(__DIR__)
const DEV_ENV = TRUE;

// class autoloading
require BACKEND_DIR . '/lib/Util/ClassLoader.php';

$classLoaders = array();
$classLoaders[] = new Util\ClassLoader('Doctrine', BACKEND_DIR . '/lib/vendor/Doctrine');
$classLoaders[] = new Util\ClassLoader('Symfony', BACKEND_DIR . '/lib/vendor/Symfony');
$classLoaders[] = new Util\ClassLoader('Volkszaehler', BACKEND_DIR . '/lib');

foreach ($classLoaders as $loader) {
	$loader->register(); // register on SPL autoload stack
}

// enable strict error reporting
error_reporting(E_ALL);

Util\Configuration::load(BACKEND_DIR . '/volkszaehler.conf');

$fc = new Dispatcher;	// spawn frontcontroller / dispatcher
$fc->run();				// execute controller and sends output

?>
