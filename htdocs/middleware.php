<?php
/**
 * middleware bootstrap entrypoint
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @copyright Copyright (c) 2011, The volkszaehler.org project
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

define('VZ_DIR', realpath(__DIR__ . '/..'));
define('VZ_VERSION', '0.3');

require_once VZ_DIR . '/lib/Util/Configuration.php';

// load configuration
Util\Configuration::load(VZ_DIR . '/etc/volkszaehler.conf');

// set timezone
$tz = (Util\Configuration::read('timezone')) ? Util\Configuration::read('timezone') : @date_default_timezone_get();
date_default_timezone_set($tz);

// set locale
setlocale(LC_ALL, Util\Configuration::read('locale'));

// define include dirs for vendor libs
define('DOCTRINE_DIR', Util\Configuration::read('lib.doctrine') ? Util\Configuration::read('lib.doctrine') : 'Doctrine');
define('JPGRAPH_DIR', Util\Configuration::read('lib.jpgraph') ? Util\Configuration::read('lib.jpgraph') : 'JpGraph');

/* @var $loader \Composer\Autoload\ClassLoader */
require VZ_DIR . '/vendor/autoload.php';

$r = new Router();
$r->run();
$r->view->send();

