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
	private $em = NULL;			// Model (Doctrine EntityManager)
	private $view = NULL;		// View
	private $controller = NULL;	// Controller
	
	/*
	 * constructor
	 */
	public function __construct() {
		$request = new View\Http\Request();
		$response = new View\Http\Response();
		
		$format = $request->getParameter('format');
		$controller = $request->getParameter('controller');
		
		$this->em = Dispatcher::createEntityManager();
		$this->view = View\View::factory($request, $response);
		$this->controller = Controller\Controller::factory($this->view, $this->em);
	}
	
	/**
	 * execute application
	 */
	public function run() {
		$action = (is_null($this->view->request->getParameter('action'))) ? 'get' : $this->view->request->getParameter('action');	// default action
		
		$this->controller->run($action);	// run controllers actions (usually CRUD: http://de.wikipedia.org/wiki/CRUD)
		$this->view->render();				// render view & send http response
	}
	
	/**
	 * factory for doctrines entitymanager
	 * 
	 * @todo create extra singleton class or registry?
	 */
	public static function createEntityManager() {
		$vzConfig = Util\Registry::get('config');
		
		// Doctrine
		$dcConfig = new \Doctrine\ORM\Configuration;
		
		if (extension_loaded('apc')) {
			$cache = new \Doctrine\Common\Cache\ApcCache;
			$dcConfig->setMetadataCacheImpl($cache);
			$dcConfig->setQueryCacheImpl($cache);
		}
		
		$driverImpl = $dcConfig->newDefaultAnnotationDriver(BACKEND_DIR . '/lib/Model');
		$dcConfig->setMetadataDriverImpl($driverImpl);
		
		$dcConfig->setProxyDir(BACKEND_DIR . '/lib/Model/Proxies');
		$dcConfig->setProxyNamespace('Volkszaehler\Model\Proxies');
		$dcConfig->setAutoGenerateProxyClasses(DEV_ENV == true);
		
		$dcConfig->setSQLLogger(Util\Debug::getSQLLogger());
		
		$em = \Doctrine\ORM\EntityManager::create($vzConfig['db'], $dcConfig);
		
		return $em;
	}
}

?>
