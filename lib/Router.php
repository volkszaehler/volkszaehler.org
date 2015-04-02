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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

use Symfony\Component\HttpKernel\HttpKernelInterface;

use Symfony\Component\Debug\ErrorHandler;

use Doctrine\ORM;
use Doctrine\Common\Cache;

use Volkszaehler\View;
use Volkszaehler\Util;

/**
 * Router
 *
 * This class povides routing to incoming requests to controllers
 *
 * @package default
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Götz <cpuidle@gmx.de>
 */
class Router implements HttpKernelInterface {

	/**
	 * @var ORM\EntityManager Doctrine EntityManager
	 */
	public $em;

	/**
	 * @var Util\Debug optional debugging instance
	 */
	public $debug;

	/**
	 * @var View\View output view
	 */
	public $view;

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
		'aggregator'	=> 'Volkszaehler\Controller\AggregatorController',
		'entity'		=> 'Volkszaehler\Controller\EntityController',
		'data'			=> 'Volkszaehler\Controller\DataController',
		'capabilities'	=> 'Volkszaehler\Controller\CapabilitiesController'
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
		// handle errors as exceptions
		ErrorHandler::register();

		// views
		foreach (array('png', 'jpeg', 'jpg', 'gif') as $format) {
			self::$viewMapping[$format] = 'Volkszaehler\View\JpGraph';
		}
	}

	/**
	 * Handle the request
	 * Source: Symfony\Component\HttpKernel\HttpKernel
	 */
	public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true) {
		try {
			// initialize entity manager
			if (null == $this->em || !$this->em->isOpen()) {
				$this->em = self::createEntityManager();
			}

			return $this->handleRaw($request, $type);
		}
		catch (\Exception $e) {
			if (false === $catch) {
				throw $e;
			}

			return $this->handleException($e, $request, $type);
		}
	}

	/**
	 * Determine context, format and uuid of the raw request
	 */
	public function handleRaw(Request $request, $type = HttpKernelInterface::MASTER_REQUEST) {
		// merge request parameters before first view is initialized
		$request->parameters = new ParameterBag(array_merge($request->request->all(), $request->query->all()));

		// workaround for https://github.com/symfony/symfony/issues/13617
		$pathInfo = ($request->server->has('PATH_INFO')) ? $request->server->get('PATH_INFO') : '';
		if (0 === strlen($pathInfo)) {
			$pathInfo = $request->getPathInfo();
		}
		$format = pathinfo($pathInfo, PATHINFO_EXTENSION);

		if (!array_key_exists($format, self::$viewMapping)) {
			if (empty($pathInfo)) {
				throw new \Exception('Missing or invalid PATH_INFO');
			}
			elseif (empty($this->format)) {
				throw new \Exception('Missing format');
			}
			else {
				throw new \Exception('Unknown format: \'' . $this->format . '\'');
			}
		}

		$class = self::$viewMapping[$format];
		$this->view = new $class($request, $format);

		$path = explode('/', substr($pathInfo, 1, strrpos($pathInfo, '.')-1));
		list($context, $uuid) = array_merge($path, array(null));

		// verify route
		if (!array_key_exists($context, self::$controllerMapping)) {
			throw new \Exception((empty($context)) ? 'Missing context' : 'Unknown context: \'' . $context . '\'');
		}

		return $this->handler($request, $context, $uuid);
	}

	/**
	 * Processes the request
	 *
	 * Example: http://sub.domain.local/middleware.php/channel/550e8400-e29b-11d4-a716-446655440000/data.json?operation=edit&title=New Title
	 */
	function handler(Request $request, $context, $uuid) {
		// initialize debugging
		if (($debugLevel = $request->parameters->get('debug')) || $debugLevel = Util\Configuration::read('debug')) {
			if ($debugLevel > 0) {
				$this->debug = new Util\Debug($debugLevel, $this->em);
			}
		}

		// get controller operation
		if (null === ($operation = $request->parameters->get('operation'))) {
			$operation = self::$operationMapping[strtolower($request->getMethod())];
		}

		$class = self::$controllerMapping[$context];
		$controller = new $class($request, $this->em);

		$result = $controller->run($operation, $uuid);
		$this->view->add($result);

		return $this->view->send();
	}

	/**
	 * Handles an exception by trying to convert it to a Response
	 * Source: Symfony\Component\HttpKernel\HttpKernel
	 *
	 * @param \Exception $e       An \Exception instance
	 * @param Request    $request A Request instance
	 * @param int        $type    The type of the request
	 *
	 * @return Response A Response instance
	 */
	private function handleException(\Exception $e, $request, $type) {
		if (null === $this->view) {
			$this->view = new View\JSON($request); // fallback view instantiates error handler
		}

		return $this->view->getExceptionResponse($e);
	}

	/**
	 * Factory method for Doctrine EntityManager
	 *
	 * @todo add other caching drivers (memcache, xcache)
	 * @todo put into static class? singleton? function or state class?
	 */
	public static function createEntityManager($admin = FALSE) {
		$config = new ORM\Configuration;

		if (Util\Configuration::read('devmode') == FALSE) {
			$cache = null;
			if (extension_loaded('apc'))
				$cache = new Cache\ApcCache;
			if ($cache) {
				$config->setMetadataCacheImpl($cache);
				$config->setQueryCacheImpl($cache);
			}
		}
		else if (extension_loaded('apc')) {
			// clear cache
			apc_clear_cache('user');
		}

		$driverImpl = $config->newDefaultAnnotationDriver(VZ_DIR . '/lib/Model');
		$config->setMetadataDriverImpl($driverImpl);

		$config->setProxyDir(VZ_DIR . '/lib/Model/Proxy');
		$config->setProxyNamespace('Volkszaehler\Model\Proxy');
		$config->setAutoGenerateProxyClasses(Util\Configuration::read('devmode'));

		$dbConfig = Util\Configuration::read('db');
		if ($admin && isset($dbConfig['admin'])) {
			$dbConfig = array_merge($dbConfig, $dbConfig['admin']);
		}

		return ORM\EntityManager::create($dbConfig, $config);
	}
}

?>
