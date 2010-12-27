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
error_reporting(E_ALL | E_STRICT);

// TODO replace by state class
define('VZ_VERSION', 0.2);
define('VZ_DIR', realpath(__DIR__ . '/..'));

// class autoloading
require VZ_DIR . '/lib/Util/ClassLoader.php';

$classLoaders = array(
	new Util\ClassLoader('Volkszaehler', VZ_DIR . '/lib'),
	new Util\ClassLoader('Doctrine', VZ_DIR . '/lib/vendor/Doctrine')
);

foreach ($classLoaders as $loader) {
	$loader->register(); // register on SPL autoload stack
}

Util\Configuration::load(VZ_DIR . '/etc/volkszaehler.conf');

$r = new Router();
$r->run();
$r->view->send();

?>
