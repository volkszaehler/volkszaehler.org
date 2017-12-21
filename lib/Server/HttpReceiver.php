<?php
/**
 * @copyright Copyright (c) 2011-2017, The volkszaehler.org project
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
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

use React\Socket\Server as SocketServer;
use React\Http\Server as HttpServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RingCentral\Psr7;

/**
 * HttpReceiver implements the server side interface for receiving push messages
 */
class HttpReceiver {

	/**
	 * @var MiddlewareAdapter
	 */
	protected $hub;

	/**
	 * @var HttpServer
	 */
	protected $http;

	function __construct(SocketServer $socket, MiddlewareAdapter $hub) {
		$this->hub = $hub;

		$this->http = new HttpServer([$this, 'handleRequest']);
		$this->http->listen($socket);
	}

	/**
	 * Main push request/ websocket response loop
	 */
	function handleRequest(ServerRequestInterface $request): ResponseInterface {
		$headers = array('Content-Type' => 'application/json');

		try {
			$content = (string)$request->getBody();

			$data = $this->hub->handlePushMessage($content);
			$headers['Content-Length'] = strlen($data);

			if (null === $data) {
				return new Psr7\Response(400, $headers, '1');
			}
			else {
				return new Psr7\Response(200, $headers, $data);
			}
		}
		catch (\Exception $exception) {
			$data = $this->getExceptionAsJson($exception, true);
			$headers['Content-Length'] = strlen($data);
			return new Psr7\Response(500, $headers, $data);
		}
	}

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
}
