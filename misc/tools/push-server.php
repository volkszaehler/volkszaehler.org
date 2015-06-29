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

define('VZ_DIR', realpath(__DIR__ . '/../..'));

require_once VZ_DIR . '/lib/bootstrap.php';

/**
 * Convert Exception to json
 *
 * Cloned from Volkszaehler\Debug\addException
 */
function getExceptionAsJson(\Exception $exception, $debug = false) {
	$exceptionClass = explode('\\', get_class($exception));
	$json = array(
		'version' => VZ_VERSION,
		'exception' => array(
			'message' => $exception->getMessage(),
			'type' => end($exceptionClass),
			'code' => $exception->getCode()
		)
	);

	if ($debug) {
		$json['exception'] = array_merge($json['exception'], array(
			'file' => $exception->getFile(),
			'line' => $exception->getLine(),
			'backtrace' => $exception->getTrace()
		));
	}

	return json_encode($json);
}

//
// Main
//

echo "Volkszaehler push server\n";

if (!Volkszaehler\Util\Configuration::read('push.enabled')) {
	throw new \Exception("Push server is disabled in configuration", 1);
}

// read config
$localPort = Volkszaehler\Util\Configuration::read('push.server');
$remotePort = Volkszaehler\Util\Configuration::read('push.broadcast');

echo sprintf("Listening for updates on %d. Clients may connect at %d.\n", $localPort, $remotePort);

$loop = React\EventLoop\Factory::create();
$hub = new Volkszaehler\Server\PushHub();

// configure local httpd interface
$localSocket = new React\Socket\Server($loop);
$localServer = new React\Http\Server($localSocket);
$localSocket->listen($localPort, '0.0.0.0'); // remote loggers can push updates

// main push request/ websocket response loop
$localServer->on('request', function(React\Http\Request $request, React\Http\Response $response) use ($hub) {
	$content = '';
	$headers = $request->getHeaders();
	$contentLength = isset($headers['Content-Length']) ? (int) $headers['Content-Length'] : 0;

	$request->on('data', function($data) use ($request, $response, $hub, &$content, $contentLength) {
		// read data (may be empty for GET request)
		$content .= $data;

		// handle request after receive
		if (strlen($content) >= $contentLength) {
			$headers = array('Content-Type' => 'application/json');
			try {
				$data = $hub->handleRequest($content);
				$response->writeHead(200, $headers);
				$response->end($data);
			}
			catch (\Exception $exception) {
				$response->writeHead(500, $headers); // internal server error
				$data = getExceptionAsJson($exception, true);
				$response->end($data);
			}
		}
	});
});

// configure remote wamp interface
$remoteSocket = new React\Socket\Server($loop);
$remoteServer = new Ratchet\Server\IoServer(
	new Ratchet\Http\HttpServer(
		new Ratchet\WebSocket\WsServer(
			new Ratchet\Wamp\WampServer(
				$hub
			)
		)
	),
	$remoteSocket
);
$remoteSocket->listen($remotePort, '0.0.0.0'); // remote clients can connect

$loop->run();
