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

interface ViewInterface {
	public function __construct(HttpRequest $request, HttpResponse $response);
	public function render();
	public function exceptionHandler(Exception $exception);
	public function errorHandler($errno, $errstr, $errfile, $errline);
	
	public function add($obj);
	public function addChannel(Channel $obj);
	public function addUser(User $obj);
	public function addGroup(Group $obj);
}

abstract class View implements ViewInterface {
	public $request;
	protected $response;
	protected $created;	// holds timestamp of creation, used later to return time of execution
	
	public function __construct(HttpRequest $request, HttpResponse $response) {
		$this->request = $request;
		$this->response = $response;
		$this->created = microtime(true);
		
		// error & exception handling by view
		set_exception_handler(array($this, 'exceptionHandler'));
		set_error_handler(array($this, 'errorHandler'));
	}
	
	final public function errorHandler($errno, $errstr, $errfile, $errline) {
		$this->exceptionHandler(new ErrorException($errstr, 0, $errno, $errfile, $errline));
	}
	
	public function __destruct() {
		$this->response->send();	// send response
	}
}