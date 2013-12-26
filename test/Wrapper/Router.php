<?php
/**
 * @package test
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

namespace Wrapper;

use Volkszaehler\View;
use Volkszaehler\Util;
use Volkszaehler\View\HTTP;
use Doctrine\ORM;

/**
 * Router
 *
 * This class acts as test-only wrapper for the Router class
 *
 * @package test
 * @author Andreas Goetz <cpuidle@gmx.de>
 */
class Router extends \Volkszaehler\Router {

	/**
	 * Constructor
	 */
	public function __construct($request, $response = null) {
		// initialize HTTP request & response (required to initialize view & controllers)
		$response = ($response) ?: new HTTP\Response();

		// initialize entity manager
		$this->em = self::createEntityManager();

		// initialize debugging
		if (($debugLevel = $request->getParameter('debug')) != NULL || $debugLevel = Util\Configuration::read('debug')) {
			if ($debugLevel > 0) {
				$this->debug = new Util\Debug($debugLevel, $this->em);
			}
		}

		// check for JpGraph
		foreach (array('png', 'jpeg', 'jpg', 'gif') as $format) {
			self::$viewMapping[$format] = 'Volkszaehler\View\JpGraph';
		}

		// initialize view
		$this->pathInfo = self::getPathInfoWrapper($request);
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
	 * Simulate CGI environmental var PATH_INFO from webserver
	 *
	 * @return string
	 */
	protected static function getPathInfoWrapper(View\HTTP\Request $request) {
		$pathInfo = parse_url($request->getUrl(), PHP_URL_PATH);
		preg_match('/.*middleware(.php)?(.*)/', $pathInfo, $matches);
		return (isset($matches[2]) ? $matches[2] : $pathInfo);
	}
}

?>
