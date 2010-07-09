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
		
		// create entitymanager
		require '/path/to/lib/Doctrine/Common/ClassLoader.php';
		$classLoader = new \Doctrine\Common\ClassLoader('Doctrine', 'lib/doctrine/lib');
		$classLoader->register(); // register on SPL autoload stack
		
		// Doctrine
		$doctConfig = new Configuration;
		
		//$cache = new \Doctrine\Common\Cache\ApcCache;
		//$config->setMetadataCacheImpl($cache);
		
		$driverImpl = $doctConfig->newDefaultAnnotationDriver('/path/to/lib/MyProject/Entities');
		$doctConfig->setMetadataDriverImpl($driverImpl);
		
		//$config->setQueryCacheImpl($cache);
		
		$doctConfig->setProxyDir('/path/to/myproject/lib/MyProject/Proxies');
		$doctConfig->setProxyNamespace('MyProject\Proxies');
		
		$em = EntityManager::create($config['db'], $doctConfig);
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
	
	public static function initialize() {
		define('VERSION', '0.2');
		
		// enable strict error reporting
		error_reporting(E_ALL);
		
		// load configuration into registry
		if (!file_exists(__DIR__ . '/volkszaehler.conf.php')) {
			throw new Exception('No configuration available! Use volkszaehler.conf.default.php as an template');
		}
		
		include __DIR__ . '/volkszaehler.conf.php';
		
		spl_autoload_register(array($this, 'loadClass'));
		
		$this->initializeDoctrine();
	}
	
	/*
	 * class autoloading
	 */
	private function loadClass($className) {
		$libs = __DIR__ . '/lib/';
	
		// preg_replace pattern class name => inclulde path
		$mapping = array(
		// util classes
			'/^Registry$/'								=> 'util/registry',
			'/^Uuid$/'									=> 'util/uuid',
	
		// model classes
			'/^(Channel|User|Group|(Nested)?Database(Object)?)$/'=> 'model/$1',
			'/^(.+(Meter|Sensor))$/'					=> 'model/channel/$2/$1',
			'/^(Meter|Sensor)$/'						=> 'model/channel/$1',
	
		// view classes
			'/^(Http.*)$/'								=> 'view/http/$1',
			'/^(.*View)$/'								=> 'view/$1',
	
		// controller classes
			'/^(.*Controller)$/'						=> 'controller/$1'
			);
	
		$include = $libs . strtolower(preg_replace(array_keys($mapping), array_values($mapping), $className)) . '.php';
	
		if (!file_exists($include)) {
			return false;
		}
	
		require_once $include;
		return true;
	}
}

?>