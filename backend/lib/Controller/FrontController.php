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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;

final class FrontController {
	// MVC
	private $em = NULL;			// Model (Doctrine Entitymanager)
	private $view = NULL;		// View
	private $controller = NULL;	// Controller
	
	public function __construct() {
		// create view instance
		$view = $request->get['format'] . 'View';
		if (!class_exists($view) || !is_subclass_of($view, 'View')) {
			throw new InvalidArgumentException('\'' . $view . '\' is not a valid View');
		}
		$this->view = new $view;
		
		$this->em = self::createEntityManager();
	}
	
	public static function createEntityManager() {
		$config = Registry::get('config');
		
		// Doctrine
		$doctConfig = new Configuration;
		
		//$cache = new \Doctrine\Common\Cache\ApcCache;
		//$config->setMetadataCacheImpl($cache);
		
		$driverImpl = $doctConfig->newDefaultAnnotationDriver('lib/Model');
		$doctConfig->setMetadataDriverImpl($driverImpl);
		
		//$config->setQueryCacheImpl($cache);
		
		$doctConfig->setProxyDir('lib/Model/Proxies');
		$doctConfig->setProxyNamespace('Volkszaehler\Model\Proxies');
		
		return EntityManager::create($config['db'], $doctConfig);
	}
	
	public function run() {
		// create controller instance
		$controller = $request->get['controller'] . 'Controller';
		if (!class_exists($controller) || !is_subclass_of($controller, 'Controller')) {
			throw new ControllerException('\'' . $controller . '\' is not a valid controller');
		}
		$controller = new $controller($this->view);
		
		$action = $this->view->request->get['action'];

		$controller->$action();	// run controllers actions (usually CRUD: http://de.wikipedia.org/wiki/CRUD)
	}

	public function __destruct() {
		$this->view->render();			// render view & send http response
	}
}

?>