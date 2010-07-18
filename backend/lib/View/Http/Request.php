<?php
/*
 * Copyright (c) 2010 by Justin Otherguy <justin@justinotherguy.org>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License (either version 2 or
 * version 3) as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * For more information on the GPL, please go to:
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace Volkszaehler\View\Http;

class Request {
	protected $headers;
	protected $parameters;
	
	/**
	 * HTTP request methods
	 * 
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html
	 */
	public $method;
	
	/*
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
	
	/*
	 * setter & getter
	 */
	public function getHeader($header) { return $this->headers[$header]; }
	
	public function getParameter($name, $method = 'get') {
		return (isset($this->parameters[$method][$name])) ? $this->parameters[$method][$name] : NULL;
	}
}
