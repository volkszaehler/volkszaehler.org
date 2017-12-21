<?php
/**
 * Common loader code
 *
 * @copyright Copyright (c) 2011-2017, The volkszaehler.org project
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

use Volkszaehler\Util;

// enable strict error reporting
error_reporting(E_ALL | E_STRICT);

// api version
define('VZ_VERSION', '0.3');

// Note: users of bootstrap.php can set VZ_DIR before calling bootstrap
if (!defined('VZ_DIR')) {
	define('VZ_DIR', realpath(__DIR__ . '/..'));
}

if (!file_exists(VZ_DIR . '/vendor/autoload.php')) {
	die('Could not find autoloader. Check that dependencies have been installed via `composer install`.');
}

if (!file_exists(VZ_DIR . '/etc/volkszaehler.conf.php')) {
	die('Could not find config file. Check that etc/volkszaehler.conf.php exists.');
}

require_once VZ_DIR . '/vendor/autoload.php';

// load configuration
Util\Configuration::load(VZ_DIR . '/etc/volkszaehler.conf');

// set timezone
$tz = (Util\Configuration::read('timezone')) ? Util\Configuration::read('timezone') : @date_default_timezone_get();
date_default_timezone_set($tz);

// set locale
setlocale(LC_ALL, Util\Configuration::read('locale'));

// force numeric output to C convention (issue #121)
setlocale(LC_NUMERIC, 'C');

?>
