#!/usr/bin/php
<?php
/**
 * httpd is a high-performance standalone webserver providing
 * middleware capabilities
 *
 * This implementation is still single-threaded and suited for single users.
 * For better scalability run behind an nginx load balancer or use built-in
 * load balancer of PHP process manager (https://github.com/marcj/php-pm)
 *
 * To run on startup add this line to /etc/inittab
 *
 *  # VOLKSZAEHLER
 *  vzmw:235:respawn:/usr/bin/php /home/pi/volkszaehler.org/misc/tools/httpd.php
 *
 *  Use `init q` to activate
 *
 * The server will listen on port 8080, to change use the httpd.port config setting
 *
 * @package default
 * @copyright Copyright (c) 2015, The volkszaehler.org project
 * @license http://www.gnu.org/licenses/gpl.txt GNU Public License
 * @author Andreas Goetz <cpuidle@gmx.de>
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

define('VZ_DIR', realpath(__DIR__ . '/../..'));

require VZ_DIR . '/lib/bootstrap.php';

// http kernel bridge using interface to middleware
$bridge = new PHPPM\Bridges\HttpKernel();
$bridge->bootstrap('Volkszaehler\Util\ReactInterface', null);

// handler
$app = function ($request, $response) use ($bridge) {
	$bridge->onRequest($request, $response);
};

// get configuration
$host = Volkszaehler\Util\Configuration::read('httpd.host', '127.0.0.1');
$port = Volkszaehler\Util\Configuration::read('httpd.port', 8080);

echo "Running httpd at http://$host:$port\n";

$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($loop);
$http = new React\Http\Server($socket, $loop);

$http->on('request', $app);

$socket->listen($port, $host);
$loop->run();
