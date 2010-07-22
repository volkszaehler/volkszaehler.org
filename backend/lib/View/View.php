<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package default
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

namespace Volkszaehler\View;

use Volkszaehler\View\HTTP;
use Volkszaehler\Util;

/**
 * superclass for all view classes
 *
 * @package default
 * @author Steffen Vogel <info@steffenvogel.de>
 *
 */
abstract class View {
	public $request;
	protected $response;

	public function __construct(HTTP\Request $request, HTTP\Response $response) {
		$this->request = $request;
		$this->response = $response;

		// error & exception handling by view
		set_exception_handler(array($this, 'exceptionHandler'));
		set_error_handler(array($this, 'errorHandler'));
	}

	/**
	 * error & exception handling
	 */
	final public function errorHandler($errno, $errstr, $errfile, $errline) {
		$this->exceptionHandler(new \ErrorException($errstr, 0, $errno, $errfile, $errline));
	}

	final public function exceptionHandler(\Exception $exception) {
		$this->addException($exception);

		//$this->status = STATUS_EXCEPTION;	// TODO add status reporting to API

		$code = ($exception->getCode() == 0 && HTTP\Response::getCodeDescription($exception->getCode())) ? 400 : $exception->getCode();
		$this->response->setCode($code);

		$this->render();
		die();
	}

	public function render() {
		$this->response->send();
	}

	public function addException(\Exception $e) {
		echo $e;
	}

	public function addDebug(Util\Debug $debug) {

	}
}

?>
