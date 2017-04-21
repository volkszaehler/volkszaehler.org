#!/usr/bin/env php
<?php
/**
 * WebSockets push server for realtime updates from middleware
 *
 * @copyright Copyright (c) 2015, The volkszaehler.org project
 * @author Andreas Goetz <cpuidle@gmx.de>
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

namespace Volkszaehler\Server;

use Volkszaehler\Util;

use React\Socket\Server as ReactSocketServer;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\Http\HttpServerInterface;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;
use Ratchet\Http\Router;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;

use Symfony\Component\Console\Output\ConsoleOutput;


define('VZ_DIR', realpath(__DIR__ . '/../..'));

require_once VZ_DIR . '/lib/bootstrap.php';


const REMOTE_IP_PORT = '0.0.0.0:';

function addRoute($path, HttpServerInterface $controller) {
	global $routes;
	$routes->add('r' . $routes->count(), new Route($path, array('_controller' => $controller)));
}

/*
 * Main
 */

$output = new ConsoleOutput();
$output->writeln("<info>Volkszaehler Push Server</info>");

if (!Util\Configuration::read('push.enabled')) {
	throw new \Exception("Push server is disabled in configuration", 1);
}

// read config
$localPort = Util\Configuration::read('push.server');
$remotePort = Util\Configuration::read('push.broadcast');

$output->writeln(sprintf("<info>Listening for updates on %d. Clients may connect at %d.</info>", $localPort, $remotePort));

$loop = \React\EventLoop\Factory::create();
$middleware = new MiddlewareAdapter();

// configure local httpd interface
$localSocket = new ReactSocketServer(REMOTE_IP_PORT . $localPort, $loop); // remote loggers can push updates
$localServer = new HttpReceiver($localSocket, $middleware);

// configure routes
$routes = new RouteCollection;
$router = new Router(new UrlMatcher($routes, new RequestContext));

// WAMP adapter
$wampRoutes = Util\Configuration::read('push.routes.wamp');
if (is_array($wampRoutes) && count($wampRoutes)) {
	$wampAdapter = new WampClientAdapter();
	$wampServer = new WsServer(new WampServer($wampAdapter));

	foreach ($wampRoutes as $path) {
		addRoute($path, $wampServer);
	}

	$middleware->addAdapter($wampAdapter);
}
else {
	$output->writeln("<info>No routes configured for WAMP protocol. Disabling WAMP.</info>");
}

// WebSocket adapter
$wsRoutes = Util\Configuration::read('push.routes.websocket');
if (is_array($wsRoutes) && count($wsRoutes)) {
	$wsAdapter = new WsClientAdapter();
	$wsServer = new WsServer($wsAdapter);

	foreach ($wsRoutes as $path) {
		addRoute($path, $wsServer);
	}

	$middleware->addAdapter($wsAdapter);
}
else {
	$output->writeln("<info>No routes configured for WebSocket protocol. Disabling websockets.</info>");
}

// configure remote interface
$remoteSocket = new ReactSocketServer(REMOTE_IP_PORT . $remotePort, $loop); // remote clients can connect
$remoteServer = new IoServer(
	new HttpServer(
		$router
	),
	$remoteSocket
);

$loop->run();
