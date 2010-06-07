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

final class FrontController {
	private $controller;
	private $view;
	
	public function __construct() {
		$request = new HttpRequest();
		$response = new HttpResponse();
		
		// create view instance
		$viewClass = new ReflectionClass($request->get['format'] . 'View');
		if (!$viewClass->isSubclassOf('View')) {
			throw new InvalidArgumentException('\'' . $viewClass->getName() . '\' is not a valid View');
		}
		$this->view = $viewClass->newInstanceArgs(array($request, $response));
		
		// create controller instance
		$controllerClass = new ReflectionClass($request->get['controller'] . 'Controller');
		if (!$controllerClass->isSubclassOf('Controller')) {
			throw new InvalidArgumentException('\'' . $controllerClass->getName() . '\' is not a valid Controller');
		}
		$this->controller = $controllerClass->newInstanceArgs(array($this->view));
	}
	
	public function run() {
		$this->controller->execute();	// run controller
		$this->view->render();			// send response
	}
}

?>