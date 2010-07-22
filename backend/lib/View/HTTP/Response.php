<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package http
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

namespace Volkszaehler\View\HTTP;

/**
 * HTTP request
 *
 * simple class to control the output buffering
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package http
 */
class Response {
	protected $headers = array();
	protected $code = 200;	// default code (OK)

	protected static $codes = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		200 => 'OK',						// success
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		300 => 'Multiple Choicesv',
		301 => 'Moved Permanently',			// redirection
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',				// client error
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		500 => 'Internal Server Error',		// server error
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported'
	);

	/**
	 * constructor
	 */
	public function __construct() {
		$this->headers = apache_response_headers();

		ob_start(array($this, 'obCallback'));
	}

	public function obCallback($output) {
		return $output;	// simple passthrough
	}

	public function send() {
		// change returncode
		header('HTTP/1.1 ' . $this->code . ' ' . self::getCodeDescription($this->code));

		// send headers
		foreach ($this->headers as $name => $value) {
			header($name . ': ' . $value);
		}
		ob_end_flush();
	}

	/**
	 * setter & getter
	 */
	public function setHeader($header, $value) { $this->headers[$header] = $value; }
	public function getHeader($header) { return $this->headers[$header]; }
	public function getCode() { return $this->code; }
	public function setCode($code) { $this->code = $code; }
	static public function getCodeDescription($code) {
		return (isset(self::$codes[$code])) ? self::$codes[$code] : FALSE;
	}
}

?>
