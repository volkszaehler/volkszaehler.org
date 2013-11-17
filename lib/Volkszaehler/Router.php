<?php
/**
 * @package default
 * @copyright Copyright (c) 2011, The volkszaehler.org project
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
	public static $operationMapping = array(
		'post'			=> 'add',
		'delete'		=> 'delete',
		'get'			=> 'get',
		'pull'			=> 'edit'
	);

	/**
	 * @var array context => controller mapping
	 */
	public static $controllerMapping = array(
		'channel'		=> 'Volkszaehler\Controller\ChannelController',
		'group'			=> 'Volkszaehler\Controller\AggregatorController',
		'aggregator'		=> 'Volkszaehler\Controller\AggregatorController',
		'entity'		=> 'Volkszaehler\Controller\EntityController',
		'data'			=> 'Volkszaehler\Controller\DataController',
		'capabilities'		=> 'Volkszaehler\Controller\CapabilitiesController'
	);

	/**
	 * @var array format => view mapping
	 */
	public static $viewMapping = array(
		'xml'			=> 'Volkszaehler\View\XML',
		'csv'			=> 'Volkszaehler\View\CSV',
		'json'			=> 'Volkszaehler\View\JSON',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		// initialize HTTP request & response (required to initialize view & controllers)
		$request = new HTTP\Request();
		$response = new HTTP\Response();

		// initialize entity manager
		$this->em = self::createEntityManager();

		// initialize debugging
		if (($debugLevel = $request->getParameter('debug')) != NULL || $debugLevel = Util\Configuration::read('debug')) {
			if ($debugLevel > 0) {
				$this->debug = new Util\Debug($debugLevel, $this->em);
			}
		}
		
		foreach (array('png', 'jpeg', 'jpg', 'gif') as $format) {
			self::$viewMapping[$format] = 'Volkszaehler\View\JpGraph';
		}

		// initialize view
		$this->pathInfo = self::getPathInfo();
		$this->format = pathinfo($this->pathInfo, PATHINFO_EXTENSION);
		
		if (!array_key_exists($this->format, self::$viewMapping)) {
			$this->view = new View\JSON($request, $response); // fallback view
			
			if (empty($this->pathInfo)) {
				throw new \Exception('Missing or invalid PATH_INFO');
			}
			elseif (empty($this->format)) {
				throw new \Exception('Missing format');
			}
			else {
				throw new \Exception('Unknown format: \'' . $this->format . '\'');
			}
		}

		$class = self::$viewMapping[$this->format];
		$this->view = new $class($request, $response, $this->format);
	}

	/**
	 * Processes the request
	 *
	 * Example: http://sub.domain.local/middleware.php/channel/550e8400-e29b-11d4-a716-446655440000/data.json?operation=edit&title=New Title
	 */
	public function run() {
		$operation = self::getOperation($this->view->request);
		$context = explode('/', substr($this->pathInfo, 1, strrpos($this->pathInfo, '.')-1)); // parse pathinfo
		
		if (!array_key_exists($context[0], self::$controllerMapping)) {
			if (empty($context[0])) {
				throw new \Exception('Missing context');
			}
			else {
				throw new \Exception('Unknown context: \'' . $context[0] . '\'');
			}
		}
		
		$class = self::$controllerMapping[$context[0]];
		$controller = new $class($this->view, $this->em);
		
		$result = $controller->run($operation, array_slice($context, 1));
		$this->view->add($result);
	}

	protected static function getOperation(HTTP\Request $request) {
		if ($operation = $request->getParameter('operation')) {
			return $operation;
		}
		else {
			return self::$operationMapping[strtolower($request->getMethod())];
		}
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
	}

	/**
	 * Factory for doctrines entitymanager
	 *
	 * @todo add other caching drivers (memcache, xcache)
	 * @todo put into static class? singleton? function or state class?
	 */
	public static function createEntityManager($admin = FALSE) {
		$config = new \Doctrine\ORM\Configuration;

		if (extension_loaded('apc') && Util\Configuration::read('devmode') == FALSE) {
			$cache = new \Doctrine\Common\Cache\ApcCache;
			$config->setMetadataCacheImpl($cache);
			$config->setQueryCacheImpl($cache);
		}

		$driverImpl = $config->newDefaultAnnotationDriver(VZ_DIR . '/lib/Volkszaehler/Model');
		$config->setMetadataDriverImpl($driverImpl);

		$config->setProxyDir(VZ_DIR . '/lib/Volkszaehler/Model/Proxy');
		$config->setProxyNamespace('Volkszaehler\Model\Proxy');
		$config->setAutoGenerateProxyClasses(Util\Configuration::read('devmode'));

		$dbConfig = Util\Configuration::read('db');
		if ($admin && isset($dbConfig['admin'])) {
			$dbConfig = array_merge($dbConfig, $dbConfig['admin']);
		}

		return \Doctrine\ORM\EntityManager::create($dbConfig, $config);
	}
}

?>
