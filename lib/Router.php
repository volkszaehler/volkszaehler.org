<?php
/**
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
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
use Symfony\Component\HttpFoundation\RequestMatcher;

use Symfony\Component\HttpKernel\HttpKernelInterface;

use Symfony\Component\Debug\ErrorHandler;

use Doctrine\ORM;
use Doctrine\Common\Cache;

use Volkszaehler\View;
use Volkszaehler\Util;
use Volkszaehler\Controller;

/**
 * Router
 *
 * This class routes incoming requests to controllers
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Götz <cpuidle@gmx.de>
 */
class Router implements HttpKernelInterface {

	// firewall actions
	const ACTION_ALLOW     = 'allow';
	const ACTION_DENY      = 'deny';
	const ACTION_AUTHORIZE = 'auth';

	/**
	 * @var ORM\EntityManager Doctrine EntityManager
	 */
	public $em;

	/**
	 * @var View\View output view
	 */
	public $view;

	/**
	 * @var float timestamp
	 */
	public $requestReceived;

	/**
	 * @var array HTTP-method => operation mapping
	 */
	public static $operationMapping = array(
		'POST'			=> 'add',
		'DELETE'		=> 'delete',
		'GET'			=> 'get',
		'PATCH'			=> 'edit'
	);

	/**
	 * @var array context => controller mapping
	 */
	public static $controllerMapping = array(
		'auth'			=> 'Volkszaehler\Controller\AuthorizationController',
		'channel'		=> 'Volkszaehler\Controller\ChannelController',
		'group'			=> 'Volkszaehler\Controller\AggregatorController',
		'aggregator'	=> 'Volkszaehler\Controller\AggregatorController',
		'entity'		=> 'Volkszaehler\Controller\EntityController',
		'data'			=> 'Volkszaehler\Controller\DataController',
		'query'			=> 'Volkszaehler\Controller\QueryController',
		'prognosis'		=> 'Volkszaehler\Controller\PrognosisController',
		'capabilities'	=> 'Volkszaehler\Controller\CapabilitiesController',
		'iot'			=> 'Volkszaehler\Controller\IotController'
	);

	/**
	 * @var array format => view mapping
	 */
	public static $viewMapping = array(
		'csv'			=> 'Volkszaehler\View\CSV',
		'json'			=> 'Volkszaehler\View\JSON',
		'txt'			=> 'Volkszaehler\View\Text'
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		// handle errors as exceptions
		ErrorHandler::register();

		// views
		if (class_exists('\JpGraph\JpGraph')) {
			foreach (array('png', 'jpeg', 'jpg', 'gif') as $format) {
				self::$viewMapping[$format] = 'Volkszaehler\View\JpGraph';
			}
		}
	}

	/**
	 * Handle the request
	 * Source: Symfony\Component\HttpKernel\HttpKernel
	 *
	 * @param Request $request A Request instance
	 * @param int $type The type of the request (for Symfony compatibility, not implemented)
	 * @param bool $catch Whether to catch exceptions or not
	 * @return Response A Response instance
	 * @throws \Exception
	 */
	public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true) {
		try {
			// initialize view to ensure StreamedResponse->streamed is false
			$this->view = null;
			$this->requestReceived = microtime(true);

			// initialize entity manager
			if (null == $this->em || !$this->em->isOpen() || $this->em->getConnection()->ping() === false) {
				$this->em = self::createEntityManager();
			}
			else {
				// clear to make sure it doesn't use its cache
				$this->em->clear();
			}

			return $this->handleRaw($request, $type);
		}
		catch (\Throwable $e) {
			if (false === $catch) {
				throw $e;
			}

			return $this->handleException($e, $request);
		}
	}

	/**
	 * Determine context, format and uuid of the raw request
	 *
	 * @param Request $request A Request instance
	 * @param int $type The type of the request (for Symfony compatibility, not implemented)
	 * @return Response A Response instance
	 * @throws \Exception
	 */
	public function handleRaw(Request $request, $type = HttpKernelInterface::MASTER_REQUEST) {
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

		// initialize debugging
		if ($request->query->get('debug') || Util\Configuration::read('debug')) {
			Util\Debug::activate($this->em)->startTimer($this->requestReceived);
		}
		else {
			// make sure static debug instance is removed
			Util\Debug::deactivate();
		}

		$class = self::$viewMapping[$format];
		$this->view = new $class($request, $format);

		$path = explode('/', substr($pathInfo, 1, strrpos($pathInfo, '.')-1));
		list($context, $uuid) = array_merge($path, array(null));

		// verify route
		if (!array_key_exists($context, self::$controllerMapping)) {
			throw new \Exception((empty($context)) ? 'Missing context' : 'Unknown context: \'' . $context . '\'');
		}

		// make sure proxy ip in trusted local network isn't mistaken for client ip
		$proxies = Util\Configuration::read('proxies', []);
		if (count($proxies)) {
			$proxy_headers = Util\Configuration::read('proxy_headers', Request::HEADER_FORWARDED | Request::HEADER_X_FORWARDED_ALL);
			$request->setTrustedProxies($proxies, $proxy_headers);
		}

		// map GET operation to HTTP method for use in firewall rules
		if ($operation = $request->query->get('operation')) {
			$request->query->remove('operation');
			$method = array_flip(self::$operationMapping)[strtolower($operation)];
			$request->setMethod($method);
		}

		// firewall
		$firewallAction = $this->firewall($request);

		if ($firewallAction == self::ACTION_DENY) {
			$this->view->getResponse()->setStatusCode(Response::HTTP_UNAUTHORIZED);
			throw new \Exception('Access denied');
		}

		// authorize requests
		if ($firewallAction == self::ACTION_AUTHORIZE) {
			if (null == Util\Configuration::read('authorization')) {
				$this->view->getResponse()->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
				throw new \Exception('No authorization ruleset');
			}

			Controller\AuthorizationController::authorize($request, $context, $this->view);
		}

		return $this->handler($request, $context, $uuid);
	}

	/**
	 * Processes the request
	 *
	 * Example: http://sub.domain.local/middleware.php/channel/550e8400-e29b-11d4-a716-446655440000/data.json?operation=edit&title=New Title
	 * @param Request $request
	 * @param $context
	 * @param $uuid
	 * @return Response
	 */
	function handler(Request $request, $context, $uuid) {
		$operation = self::$operationMapping[$request->getMethod()];
		$class = self::$controllerMapping[$context];
		$controller = new $class($request, $this->em, $this->view);

		$result = $controller->run($operation, $uuid);
		$this->view->add($result);

		return $this->view->send();
	}

	/**
	 * Handles an exception by trying to convert it to a Response
	 * Source: Symfony\Component\HttpKernel\HttpKernel
	 *
	 * @param \Exception $e An \Exception instance
	 * @param Request $request A Request instance
	 * @return Response A Response instance
	 */
	private function handleException(\Throwable $e, Request $request) {
		if (null === $this->view) {
			$this->view = new View\JSON($request); // fallback view instantiates error handler
		}

		return $this->view->getExceptionResponse($e);
	}

	public function firewall(Request $request) {
		$firewallAction = Util\Configuration::read('firewall');
		if (!(is_array($firewallAction) && count($firewallAction))) {
			return self::ACTION_DENY;
		}

		foreach ($firewallAction as $ruleset) {
			$path = (isset($ruleset['path'])) ? $ruleset['path'] : null;
			$host = (isset($ruleset['host'])) ? $ruleset['host'] : null;
			$methods = (isset($ruleset['methods'])) ? $ruleset['methods'] : null;
			$ips = (isset($ruleset['ips'])) ? $ruleset['ips'] : null;

			if (!isset($ruleset['action'])) {
				throw new \Exception('Missing firewall rule action');
			}

			$matcher = new RequestMatcher($path, $host, $methods, $ips);
			if ($matcher->matches($request)) {
				return $ruleset['action'];
			}
		}

		throw new \Exception('No matching firewall ruleset');
	}

	/**
	 * Factory method for Doctrine EntityManager
	 *
	 * @param bool $admin
	 * @return ORM\EntityManager
	 */
	public static function createEntityManager($admin = false) {
		$config = new ORM\Configuration;

		if (Util\Configuration::read('devmode') == false) {
			$cache = null;
			if (extension_loaded('apcu'))
				$cache = new Cache\ApcuCache();
			if ($cache) {
				$config->setMetadataCacheImpl($cache);
				$config->setQueryCacheImpl($cache);
			}
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

		// reset singleton to use new entity manager
		Util\EntityFactory::reset();

		return ORM\EntityManager::create($dbConfig, $config);
	}
}

?>
