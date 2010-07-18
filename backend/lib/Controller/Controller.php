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

namespace Volkszaehler\Controller;

abstract class Controller {
	protected $view;
	protected $em;
	
	/*
	 * constructor
	 */
	public function __construct(\Volkszaehler\View\View $view, \Doctrine\ORM\EntityManager $em) {
		$this->view = $view;
		$this->em = $em;
	}
	
	/*
	 * creates new view instance depending on the requested format
	 */
	public static function factory(\Volkszaehler\View\View $view, \Doctrine\ORM\EntityManager $em) {
		$controller = ucfirst(strtolower($view->request->getParameter('controller')));
		
		$controllerClassName = 'Volkszaehler\Controller\\' . $controller;
		if (!(\Volkszaehler\Util\ClassLoader::classExists($controllerClassName)) || !is_subclass_of($controllerClassName, '\Volkszaehler\Controller\Controller')) {
			throw new \InvalidArgumentException('\'' . $controllerClassName . '\' is not a valid controller');
		}
		return new $controllerClassName($view, $em);
	}
	
	/**
	 * run controller actions
	 * 
	 * @param string $action runs the action if class method is available
	 */
	public function run($action) {
		if (!method_exists($this, $action)) {
			throw new \InvalidArgumentException('\'' . $action . '\' is not a valid controller action');
		}
		
		$this->$action();
	}
}

?>