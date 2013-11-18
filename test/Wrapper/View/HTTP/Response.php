<?php
/**
 * @copyright Copyright (c) 2011, The volkszaehler.org project
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

namespace Wrapper\View\HTTP;

/**
 * HTTP request wrapper
 *
 * @package test
 * @author Andreas Goetz <cpuidle@gmx.de>
*/
class Response extends \Volkszaehler\View\HTTP\Response {

	protected $contents;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->headers = self::getHeaders();

		ob_start();
	}

	public function send() {
		$this->contents = ob_get_contents();
		ob_end_clean();
	}

	public function getResponseContents() { return $this->contents; }
	public function getResponseHeaders() { return $this->headers; }
}

?>
