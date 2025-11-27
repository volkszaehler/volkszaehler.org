<?php
/**
 * Common loader code
 *
 * @copyright Copyright (c) 2011-2020, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
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

function fail($msg) {
	// JSON request?
	if (preg_match('/\.json/', @$_SERVER['REQUEST_URI'])) {
		header('Content-type: application/json');
		echo json_encode([
			'version' => VZ_VERSION,
			'exception' => array(
				'message' => $msg
			)
		]);
		die();
	}

	// normal request or command line
	throw new \Exception($msg);
}

// api version
define('VZ_VERSION', '0.3');

// Note: users of bootstrap.php can set VZ_DIR before calling bootstrap
if (!defined('VZ_DIR')) {
	define('VZ_DIR', realpath(__DIR__ . '/..'));
}

if (!file_exists(VZ_DIR . '/vendor/autoload.php')) {
	fail('Could not find autoloader. Check that dependencies have been installed via `composer install`.');
}

if (!file_exists(VZ_DIR . '/etc/config.yaml')) {
	fail('Could not find config file. Check that etc/config.yaml exists.');
}

require_once VZ_DIR . '/vendor/autoload.php';

// load configuration
Util\Configuration::load(VZ_DIR . '/etc/config.yaml');

// set timezone
$tz = (Util\Configuration::read('timezone')) ? Util\Configuration::read('timezone') : @date_default_timezone_get();
date_default_timezone_set($tz);

// set locale
setlocale(LC_ALL, Util\Configuration::read('locale'));

// force numeric output to C convention (issue #121)
setlocale(LC_NUMERIC, 'C');

?>
