<?php
/**
 * Backend bootstrap entrypoint
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

// enable strict error reporting
error_reporting(E_ALL);

// TODO replace by state class
define('VZ_VERSION', 0.2);
define('VZ_DIR', realpath(__DIR__ . '/..'));
define('VZ_BACKEND_DIR', VZ_DIR . '/backend');
define('DEV_ENV', TRUE);

// class autoloading
require VZ_BACKEND_DIR . '/lib/Util/ClassLoader.php';

$classLoaders = array();
$classLoaders[] = new Util\ClassLoader('Volkszaehler', VZ_BACKEND_DIR . '/lib');
$classLoaders[] = new Util\ClassLoader('Doctrine', VZ_BACKEND_DIR . '/lib/vendor/Doctrine');
//$classLoaders[] = new Util\ClassLoader('Symfony', VZ_BACKEND_DIR . '/lib/vendor/Symfony'); // only required for the cli

foreach ($classLoaders as $loader) {
	$loader->register(); // register on SPL autoload stack
}

Util\Configuration::load(VZ_BACKEND_DIR . '/volkszaehler.conf');

$fc = new Dispatcher;	// spawn frontcontroller / dispatcher
$fc->run();				// execute controller and sends output

?>
