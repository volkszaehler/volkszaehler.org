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

namespace Volkszaehler;

use Volkszaehler\View\HTTP;
use Volkszaehler\View;
use Volkszaehler\Controller;
use Volkszaehler\Util;

/**
 * Backend dispatcher
 *
 * This class acts as a frontcontroller to route incomming requests
 *
 * @package default
 * @author Steffen Vogel <info@steffenvogel.de>
 */
class Dispatcher {
	/**
	 * @var \Doctrine\ORM\EntityManager Doctrine EntityManager
	 */
	protected $em;

	/**
	 * @var View\View
	 */
	protected $view;

	/**
	 * @var Controller\Controller
	 */
	protected $controller;

	/**
	 * @var Router
	 */
	protected $router;

	/**
	 * @var Util\Debug optional debugging instance
	 */
	protected $debug = NULL;

	/**
	 * @var array HTTP method => action mapping
	 */
	protected static $actionMapping = array(
		'post' => 'add',
		'delete' => 'delete',
		'get' => 'get',
		'pull' => 'edit'
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		// create HTTP request & response (needed to initialize view & controller)
		$request = new HTTP\Request();
		$response = new HTTP\Response();

		// initialize entity manager
		$this->em = Dispatcher::createEntityManager();

		// initialize debugging
		if (($debugLevel = $request->getParameter('debug')) != NULL || $debugLevel = Util\Configuration::read('debug')) {
			if ($debugLevel > 0) {
				$this->debug = new Util\Debug($debugLevel, $this->em);
			}
		}

		// initialize router
		$this->router = new Router($this->em);

		// initialize view
		if ($this->router->getFormat()) {
			$format = $this->router->getFormat();
		}
		else {
			$format = 'json';
		}

		switch ($format) {
			case 'png':
			case 'jpeg':
			case 'gif':
				$this->view = new View\JpGraph($request, $response, $format);
				break;

			case 'xml':
			case 'csv':
				$padding = FALSE;
			case 'json':
				$padding = $request->getParameter('padding');

				$viewClassName = 'Volkszaehler\View\\' . strtoupper($format);
				$this->view = new $viewClassName($request, $response, $padding);
				break;

			case 'txt':
				$this->view = new View\PlainText($request, $response);
				break;

			default:
				throw new \Exception('unknown format: ' . $format);
				break;
		}

		// initialize controller
		if (!($controllerClassName = $this->router->getController())) {
			throw new \Exception('invalid controller specified');
		}
		$this->controller = new $controllerClassName($this->view, $this->em, $this->router->getIdentifier());
	}

	/**
	 * Execute application
	 */
	public function run() {
		if ($this->router->getAction()) {
			$action = $this->router->getAction();
		}
		elseif (self::$actionMapping[strtolower($this->view->request->getMethod())]) {
			$action = self::$actionMapping[strtolower($this->view->request->getMethod())];
		}
		else {
			throw new \Exception('can\'t determine action');
		}

		$this->controller->run($action);	// run controllers actions (usually CRUD: http://de.wikipedia.org/wiki/CRUD)
		$this->view->sendResponse();		// render view & send http response
	}

	/**
	 * Factory for doctrines entitymanager
	 *
	 * @todo add other caching drivers (memcache, xcache)
	 * @todo put into static class? singleton?
	 */
	public static function createEntityManager() {
		$config = new \Doctrine\ORM\Configuration;

		if (extension_loaded('apc') && Util\Configuration::read('devmode') == FALSE) {
			$cache = new \Doctrine\Common\Cache\ApcCache;
			$config->setMetadataCacheImpl($cache);
			$config->setQueryCacheImpl($cache);
		}

		$driverImpl = $config->newDefaultAnnotationDriver(VZ_BACKEND_DIR . '/lib/Model');
		$config->setMetadataDriverImpl($driverImpl);

		$config->setProxyDir(VZ_BACKEND_DIR . '/lib/Model/Proxy');
		$config->setProxyNamespace('Volkszaehler\Model\Proxy');
		$config->setAutoGenerateProxyClasses(Util\Configuration::read('devmode'));

		return \Doctrine\ORM\EntityManager::create(Util\Configuration::read('db'), $config);
	}
}

?>
