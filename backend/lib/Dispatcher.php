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

namespace Volkszaehler;

use Volkszaehler\View;
use Volkszaehler\Controller;
use Volkszaehler\Util;

/*
 * frontcontroller / dispatcher
 */
final class Dispatcher {
	// MVC
	protected $em;			// Model (Doctrine EntityManager)
	protected $view;		// View
	protected $controller;	// Controller
	
	/*
	 * constructor
	 */
	public function __construct() {
		// create HTTP request & response (needed to initialize view & controller)
		$request = new View\Http\Request();
		$response = new View\Http\Response();
		
		$format = ($request->getParameter('format')) ? $request->getParameter('format') : 'json';	// default action
		$controller = $request->getParameter('controller');
		
		// initialize entity manager
		$this->em = Dispatcher::createEntityManager();
		
		// initialize view
		if (in_array($format, array('png', 'jpeg', 'gif'))) {
			$this->view = new View\JpGraph($request, $response, $format);
		}
		else {
			if ($controller == 'data' && ($format == 'json' || $format == 'xml')) {
				$controller = 'channel';
			}
			
			$viewClassName = 'Volkszaehler\View\\' . ucfirst($format) . '\\' . ucfirst($controller);
			if (!(\Volkszaehler\Util\ClassLoader::classExists($viewClassName)) || !is_subclass_of($viewClassName, '\Volkszaehler\View\View')) {
				throw new \InvalidArgumentException('\'' . $viewClassName . '\' is not a valid View');
			}
		
			$this->view = new $viewClassName($request, $response);
		}
		
		// initialize controller
		$controllerClassName = 'Volkszaehler\Controller\\' . ucfirst(strtolower($request->getParameter('controller')));
		if (!(\Volkszaehler\Util\ClassLoader::classExists($controllerClassName)) || !is_subclass_of($controllerClassName, '\Volkszaehler\Controller\Controller')) {
			throw new \InvalidArgumentException('\'' . $controllerClassName . '\' is not a valid controller');
		}
		$this->controller = new $controllerClassName($this->view, $this->em);
	}
	
	/**
	 * execute application
	 */
	public function run() {
		$action = ($this->view->request->getParameter('action')) ? 'get' : $this->view->request->getParameter('action');	// default action
		
		$this->controller->run($action);	// run controllers actions (usually CRUD: http://de.wikipedia.org/wiki/CRUD)
		$this->view->render();				// render view & send http response
	}
	
	/**
	 * factory for doctrines entitymanager
	 * 
	 * @todo create extra singleton class?
	 */
	public static function createEntityManager() {
		$config = new \Doctrine\ORM\Configuration;
		
		if (extension_loaded('apc')) {
			$cache = new \Doctrine\Common\Cache\ApcCache;
			$config->setMetadataCacheImpl($cache);
			$config->setQueryCacheImpl($cache);
		}
		
		$driverImpl = $config->newDefaultAnnotationDriver(BACKEND_DIR . '/lib/Model');
		$config->setMetadataDriverImpl($driverImpl);
		
		$config->setProxyDir(BACKEND_DIR . '/lib/Model/Proxies');
		$config->setProxyNamespace('Volkszaehler\Model\Proxies');
		$config->setAutoGenerateProxyClasses(DEV_ENV == true);
		
		$config->setSQLLogger(Util\Debug::getSQLLogger());
		
		$em = \Doctrine\ORM\EntityManager::create(Util\Configuration::read('db'), $config);
		
		return $em;
	}
}

?>
