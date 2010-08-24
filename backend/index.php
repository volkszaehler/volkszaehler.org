<?php
/**
 * Backend bootstrapping entrypoint
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package default
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

namespace Volkszaehler;

use Volkszaehler\Util;
use Volkszaehler\Controller;

// TODO replace by state class
define('VZ_VERSION', 0.2);
define('VZ_DIR', '/home/steffen/workspace/volkszaehler.org');	// TODO realpath(__DIR__)
define('BACKEND_DIR', VZ_DIR . '/backend');
define('DEV_ENV', TRUE);

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
