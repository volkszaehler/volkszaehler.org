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

class HttpResponse extends HttpHandle {
	
	public $code = 200;	// default code (OK)
	
	public function __construct() {
		$this->headers = apache_response_headers();
		
		ob_start(array($this, 'obCallback'));
	}
	
	public function obCallback($output) {
		return $output;
	}
	
	public function send() {
		// change returncode
		header('HTTP/1.1 ' . $this->code . ' ' . HttpHandle::$codes[$this->code]);	// TODO untested
		
		// send headers
		foreach ($this->headers as $name => $value) {
			header($name . ': ' . $value);
		}
		ob_end_flush();
	}
	
	public function setHeader($header, $value) {
		$this->headers[$header] = $value;
	}
}

