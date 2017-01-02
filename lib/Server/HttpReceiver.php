<?php
/**
 * @copyright Copyright (c) 2016, The volkszaehler.org project
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @package util
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

use React\Socket\Server as ReactSocketServer;
use React\Http\Server as ReactHttpServer;
use React\Http\Request;
use React\Http\Response;

/**
 * HttpReceiver implements the server side interface for receiving push messages
 */
class HttpReceiver extends ReactHttpServer {

	/**
	 * @var MiddlewareAdapter
	 */
	protected $hub;

	function __construct(ReactSocketServer $socket, MiddlewareAdapter $hub) {
		parent::__construct($socket);
		$this->hub = $hub;
		$this->on('request', array($this, 'onRequest'));
	}

	/**
	 * Main push request/ websocket response loop
	 */
	function onRequest(Request $request, Response $response) {
		$headers = array('Content-Type' => 'application/json');
		try {
			$content = $request->getBody();
			$data = $this->hub->handlePushMessage($content);
			$headers['Content-Length'] = strlen($data);
			if (null === $data) {
				$response->writeHead(400, $headers);
				$response->end();
			}
			else {
				$response->writeHead(200, $headers);
				$response->end($data);
			}
		}
		catch (\Exception $exception) {
			$data = $this->getExceptionAsJson($exception, true);
			$headers['Content-Length'] = strlen($data);
			$response->writeHead(500, $headers); // internal server error
			$response->end($data);
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
