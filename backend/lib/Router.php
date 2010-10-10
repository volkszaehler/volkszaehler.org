<?php
/**
 * @package default
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @license http://www.gnu.org/licenses/gpl.txt GNU Public License
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

use Volkszaehler\View;
use Volkszaehler\Util;
use Volkszaehler\View\HTTP;
use Doctrine\ORM;

/**
 *  Router
 *
 * This class acts as a frontcontroller to route incomming requests
 *
 * @package default
 * @author Steffen Vogel <info@steffenvogel.de>
 */
class Router {
	/**
	 * @var \Doctrine\ORM\EntityManager Doctrine EntityManager
	 */
	public $em;

	/**
	 * @var View\View
	 */
	public $view;

	/**
	 * @var Util\Debug optional debugging instance
	 */
	public $debug;

	/**
	 * PATH_INFO envvar
	 * @var string
	 */
	protected $pathInfo;

	/**
	 * @var array HTTP-method => operation mapping
	 */
	protected static $operationMapping = array(
		'post'			=> 'add',
		'delete'		=> 'delete',
		'get'			=> 'get',
		'pull'			=> 'edit'
	);

	/**
	 * @var array context => controller mapping
	 */
	protected static $controllerMapping = array(
		'channel'		=> 'Volkszaehler\Controller\ChannelController',
		'group'			=> 'Volkszaehler\Controller\AggregatorController',
		'group'			=> 'Volkszaehler\Controller\AggregatorController',
		'entity'		=> 'Volkszaehler\Controller\EntityController',
		'data'			=> 'Volkszaehler\Controller\DataController',
		'capabilities'	=> 'Volkszaehler\Controller\CapabilitiesController'
	);

	/**
	 * @var array format => view mapping
	 */
	protected static $viewMapping = array(
		'png'			=> 'Volkszaehler\View\JpGraph',
		'jpeg'			=> 'Volkszaehler\View\JpGraph',
		'jpg'			=> 'Volkszaehler\View\JpGraph',
		'gif'			=> 'Volkszaehler\View\JpGraph',
		'xml'			=> 'Volkszaehler\View\XML',
		'csv'			=> 'Volkszaehler\View\CSV',
		'json'			=> 'Volkszaehler\View\JSON',
		'txt'			=> 'Volkszaehler\View\PlainText'
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		// initialize HTTP request & response (required to initialize view & controllers)
		$request = new HTTP\Request();
		$response = new HTTP\Response();

		// early default format
		$this->view = new View\JSON($request, $response);

		// initialize entity manager
		$this->em = self::createEntityManager();

		// initialize debugging
		if (($debugLevel = $request->getParameter('debug')) != NULL || $debugLevel = Util\Configuration::read('debug')) {
			if ($debugLevel > 0) {
				$this->debug = new Util\Debug($debugLevel, $this->em);
			}
		}

		// initialize view
		$this->pathInfo = self::getPathInfo();
		$this->format = self::getFormat($this->pathInfo);
		$this->operation = self::getOperation($request);

		if (empty(self::$viewMapping[$this->format])) {
			if (empty($this->format)) {
				throw new \Exception('Missing format');
			}
			else {
				throw new \Exception('Unknown format: ' . $this->format);
			}
		}

		$class = self::$viewMapping[$this->format];
		$this->view = new $class($request, $response, $this->format);

		// some general debugging information
		//Util\Debug::log('_SERVER', $_SERVER);
		//Util\Debug::log('PATH_INFO', $this->pathInfo);
	}

	/**
	 * Processes the request
	 *
	 * Request Example: http://sub.domain.local/vz/backend/channel/550e8400-e29b-11d4-a716-446655440000/data.json?operation=edit&title=New Title
	 */
	public function run() {
		$pathInfo = substr($this->pathInfo, 1, strrpos($this->pathInfo, '.') -1);	// remove leading slash and format
		$pathInfo = explode('/', $pathInfo);						// split into path segments

		if (array_key_exists($pathInfo[0], self::$controllerMapping)) {
			$class = self::$controllerMapping[$pathInfo[0]];
			$controller = new $class($this->view, $this->em);

			if (isset($pathInfo[1])) {
				$result = $controller->run($this->operation, array_slice($pathInfo, 1));
			}
			else {
				$result = $controller->run($this->operation);
			}
		}
		else {
			throw new \Exception('Unknown context: ' . $pathInfo[0]);
		}

		$this->view->add($result);
	}

	protected static function getOperation(HTTP\Request $request) {
		if ($operation = $request->getParameter('operation')) {
			return $operation;
		}
		elseif (isset(self::$operationMapping[strtolower($request->getMethod())])) {
			return self::$operationMapping[strtolower($request->getMethod())];
		}
		else {
			throw new \Exception('Can\'t determine operation');
		}
	}

	protected static function getFormat($pathInfo) {
		return pathinfo($pathInfo, PATHINFO_EXTENSION);
	}

	/**
	 * Get CGI environmental var PATH_INFO from webserver
	 *
	 * @return string
	 */
	protected static function getPathInfo() {
		if (isset($_SERVER['PATH_INFO'])) {
			return $_SERVER['PATH_INFO'];
		}
		elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
			return $_SERVER['ORIG_PATH_INFO'];
		}
		elseif (strlen($_SERVER['PHP_SELF']) > strlen($_SERVER['SCRIPT_NAME'])) {
			return substr($_SERVER['PHP_SELF'], strlen($_SERVER['SCRIPT_NAME']));
		}
		else {
			throw new \Exception('Can\'t get PATH_INFO');
		}
	}

	/**
	 * Factory for doctrines entitymanager
	 *
	 * @todo add other caching drivers (memcache, xcache)
	 * @todo put into static class? singleton? function or state class?
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
