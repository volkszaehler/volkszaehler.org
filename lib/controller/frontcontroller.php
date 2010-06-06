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

class FrontController {
	private $request;
	private $response;
	
	public function __construct() {
		$this->request = new HttpRequest();
		$this->response = new HttpResponse();
		
		$controller = $this->request->get['controller'] . 'Controller';
		
		$rc = new ReflectionClass($controller);
		if (!$rc->isSubclassOf('Controller')) {
			throw new InvalidArgumentException('\'' . $controller . '\' is not a valid Controller');
		}
		
		$this->controller = $rc->newInstanceArgs(array($this->request, $this->response));
	}
	
	public function handleRequest() {
		$this->controller->process();
	}
	
	public function sendResponse() {
		$this->response->send();
	}
}

?>