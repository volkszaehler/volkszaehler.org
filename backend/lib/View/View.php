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

namespace Volkszaehler\View;

abstract class View {
	public $request;
	protected $response;
	
	private $created;	// holds timestamp of creation, used later to return time of execution
	
	public function __construct(Http\Request $request, Http\Response $response) {
		$this->request = $request;
		$this->response = $response;
		
		// TODO move to Debug or State class
		$this->created = microtime(true);
		
		// error & exception handling by view
		set_exception_handler(array($this, 'exceptionHandler'));
		set_error_handler(array($this, 'errorHandler'));
	}
	
	/*
	 * error & exception handling
	 */
	final public function errorHandler($errno, $errstr, $errfile, $errline) {
		$this->exceptionHandler(new \ErrorException($errstr, 0, $errno, $errfile, $errline));
	}
	
	final public function exceptionHandler(\Exception $exception) {
		$this->addException($exception);
		
		//$this->status = STATUS_EXCEPTION;	// TODO add status reporting to API
		
		$code = ($exception->getCode() == 0 && Http\Response::getCodeDescription($exception->getCode())) ? 400 : $exception->getCode();
		$this->response->setCode($code);
		
		$this->render();
		die();
	}
	
	// TODO move this into Debug or State Class
	protected function getTime() {
		return round(microtime(true) - $this->created, 4);
	}
	
	public function render() {
		if (!is_null($this->request->getParameter('debug')) && $this->request->getParameter('debug') > 0) {
			$this->addDebug();
		}
		
		$this->response->send();
	}
	
	public function addException(\Exception $e) {
		echo $e;
	}
	
	public function addDebug() {
		
	}
}