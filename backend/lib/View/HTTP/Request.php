<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package http
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
 *
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
 * also used for data
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 */
class Request {
	protected $headers;
	protected $parameters;

	/**
	 * HTTP request methods
	 *
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html
	 */
	public $method;

	/**
	 * constructor
	 */
	public function __construct() {
		$this->headers = apache_response_headers();	// NOTICE only works for Apache Webservers

		$this->method = $_SERVER['REQUEST_METHOD'];

		$this->parameters= array(
								'get' => $_GET,
								'post' => $_POST,
								'cookies' => $_COOKIE,
								'files' => $_FILES
							);

		unset($_GET, $_POST, $_COOKIE, $_FILES);
	}

	/**
	 * setter & getter
	 */
	public function getHeader($header) { return $this->headers[$header]; }

	public function getParameter($name, $method = 'get') {
		return (isset($this->parameters[$method][$name])) ? $this->parameters[$method][$name] : NULL;
	}
}
