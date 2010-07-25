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
 * backend dispatcher
 *
 * this class acts as a frontcontroller to route incomming requests
 *
 * @package default
 * @author Steffen Vogel <info@steffenvogel.de>
 */
class Dispatcher {
	/**
	 * @var \Doctrine\ORM\EntityManager Doctrine Model
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
	 * constructor
	 */
	public function __construct() {
		// create HTTP request & response (needed to initialize view & controller)
		$request = new HTTP\Request();
		$response = new HTTP\Response();

		if (!($format = $request->getParameter('format'))) {
			$format = 'json';	// default view
		}

		if (!($controller = $request->getParameter('controller'))) {
			throw new \Exception('no controller specified');
		}

		// initialize entity manager
		$this->em = Dispatcher::createEntityManager();

		// starting debugging
		if (($debug = $request->getParameter('debug')) !== FALSE || $debug = Util\Configuration::read('debug')) {
			if ($debug > 0) {
				$this->debug = new Util\Debug($debug);
				$this->em->getConnection()->getConfiguration()->setSQLLogger($this->debug);
			}
		}
		// TODO debug controll via configuration file

		// initialize view
		switch ($format) {
			case 'png':
			case 'jpeg':
			case 'gif':
				$this->view = new View\JpGraph($request, $response, $format);
				break;

			case 'json':
			case 'xml':
			case 'csv':
				$viewClassName = 'Volkszaehler\View\\' . strtoupper($format);
				if (!(Util\ClassLoader::classExists($viewClassName)) || !is_subclass_of($viewClassName, '\Volkszaehler\View\View')) {
					throw new \Exception('\'' . $viewClassName . '\' is not a valid View');
				}

				$this->view = new $viewClassName($request, $response);
				break;

			case 'txt':
				$this->view = new View\PlainText($request, $response);
				break;

			default:
				throw new \Exception('unknown format: ' . $format);
				break;
		}

		// initialize controller
		$controllerClassName = 'Volkszaehler\Controller\\' . ucfirst($request->getParameter('controller')) . 'Controller';
		if (!(Util\ClassLoader::classExists($controllerClassName)) || !is_subclass_of($controllerClassName, '\Volkszaehler\Controller\Controller')) {
			throw new \Exception('\'' . $controllerClassName . '\' is not a valid controller');
		}
		$this->controller = new $controllerClassName($this->view, $this->em);
	}

	/**
	 * execute application
	 */
	public function run() {
		if ($this->view->request->getParameter('action')) {
			$action = $this->view->request->getParameter('action');
		}
		elseif (self::$actionMapping[strtolower($this->view->request->getMethod())]) {
			$action = self::$actionMapping[strtolower($this->view->request->getMethod())];
		}
		else {
			throw new \Exception('can\'t determine action');
		}

		$this->controller->run($action);	// run controllers actions (usually CRUD: http://de.wikipedia.org/wiki/CRUD)

		if (Util\Debug::isActivated()) {
			$this->view->addDebug($this->debug);
		}

		$this->view->sendResponse();				// render view & send http response
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
		$config->setAutoGenerateProxyClasses(DEV_ENV == TRUE);

		return \Doctrine\ORM\EntityManager::create(Util\Configuration::read('db'), $config);
	}
}

?>
