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
 * 		# VOLKSZAEHLER
 *   	vzmw:235:respawn:/usr/bin/php /home/pi/volkszaehler.org/bin/httpd.php
 *
 *  Use `init q` to activate
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

define('VZ_DIR', realpath(__DIR__ . '/..'));

require VZ_DIR . '/lib/bootstrap.php';

$router = new Volkszaehler\Router();

// handler
$app = function ($request, $response) use ($router) {
	$content = '';
	$headers = $request->getHeaders();
	$contentLength = isset($headers['Content-Length']) ? (int) $headers['Content-Length'] : 0;
	$acceptEncoding = isset($headers['Accept-Encoding']) ? $headers['Accept-Encoding'] : null;

	$request->on('data', function($data)
		use ($request, $response, $router, &$content, $contentLength, $acceptEncoding)
	{
		// read data (may be empty for GET request)
		$content .= $data;

		// handle request after receive
		if (strlen($content) >= $contentLength) {
			// convert React\Http\Request to Symfony\Component\HttpFoundation\Request
			$syRequest = new Symfony\Component\HttpFoundation\Request(
				// $query, $request, $attributes, $cookies, $files, $server, $content
				$request->getQuery(), array(), array(), array(), array(), array(), $content
			);

			$syRequest->setMethod($request->getMethod());
			$syRequest->server->set('REQUEST_URI', $request->getPath());
			$syRequest->server->set('SERVER_NAME', explode(':', $request->getHeaders()['Host'])[0]);
			$syRequest->headers->replace($headers = $request->getHeaders());

			// handle request by middleware
			$syResponse = $router->handle($syRequest);

			// convert React\Http\Response to Symfony\Component\HttpFoundation\Response
			$content = $syResponse->getContent();
			$headers = array_map('current', $syResponse->headers->all());
			$contentType = isset($headers['content-type']) ? $headers['content-type'] : '';

			// compression
			if (in_array($contentType, array('application/javascript', 'application/json'))) {
				$encodings = preg_split('/,\s*/', $acceptEncoding);

				if (in_array('gzip', $encodings) /*|| in_array('deflate', $encodings)*/) {
					$content = gzencode($content);
					$headers['Content-Encoding'] = 'gzip';
					$headers['Content-Length'] = strlen($content);
				}
			}

			$response->writeHead($syResponse->getStatusCode(), $headers);
			$response->end($content);
		}
	});
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
