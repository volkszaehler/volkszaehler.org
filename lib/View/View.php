<?php
/**
 * @copyright Copyright (c) 2011-2017, The volkszaehler.org project
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

namespace Volkszaehler\View;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Volkszaehler\Interpreter;
use Volkszaehler\Model;
use Volkszaehler\Util;

/**
 * Base class for all view formats
 *
 * @package default
 * @author Steffen Vogel <info@steffenvogel.de>
 */
abstract class View {

	/**
	 * Round all values to save bandwidth
	 *
	 * @var integer round all values to x decimals
	 */
	const PRECISION = 3;

	/**
	 * @var HTTP\Request
	 */
	protected $request;

	/**
	 * @var HTTP\Response
	 */
	protected $response;

	/**
	 * SQL queries analysis result (debug mode)
	 * @var float
	 */
	protected $sqlTotalTime;
	protected $sqlWorstTime;

	/**
	 * Constructor
	 */
	public function __construct(Request $request) {
		$this->request = $request;
		$this->response = new Response();
	}

	/**
	 * Get response instance
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Creates exception response
	 *
	 * @param \Exception $exception
	 */
	public function getExceptionResponse(\Throwable $exception) {
		$this->add($exception);

		// only set status code if default - allows controllers to overwrite
		if ($this->response->getStatusCode() == Response::HTTP_OK) {
			$this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
		}

		return $this->send();
	}

	/**
	 * Render response and send it to the client
	 */
	public function send() {
		if (Util\Debug::isActivated()) {
			$this->add(Util\Debug::getInstance());
		}

		$this->response->setContent($this->render());
		return $this->response;
	}

	/**
	 * Sets caching mode for the browser
	 *
	 * @todo implement remaining caching modes
	 * @param $mode
	 * @param integer $value timestamp in seconds or offset in seconds
	 */
	public function setCaching($mode, $value) {
		switch ($mode) {
			case 'modified':	// Last-modified
				$this->response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', $value) . ' GMT');

			case 'expires': 	// Expire
				$this->response->headers->set('Expires', gmdate('D, d M Y H:i:s', $value) . ' GMT');
				break;

			case 'age':		// Cache-control: max-age=
				$this->response->headers->set('Cache-control', 'max-age=' . $value);
				break;

			case 'off':
			case FALSE:
				$this->response->headers->set('Cache-control', 'no-cache');
				$this->response->headers->set('Pragma', 'no-cache');

			default:
				throw new \Exception('Unknown caching mode: \'' . $mode . '\'');
		}
	}

	/**
	 * Round decimal numbers to given precision
	 *
	 * @param $number float the number
	 * @return (float|string) the formatted number
	 */
	public static function formatNumber($number) {
		return is_null($number) ? null : round($number, self::PRECISION);
	}

	/**
	 * Format timestamp according to request
	 */
	public function formatTimestamp($ts) {
		switch ($this->request->query->get('tsfmt')) {
			case 'sql':
				return strftime('%Y-%m-%d %H:%M:%S', intval($ts/1000));
			case 'unix':
			case 'ms':
			case '':
			case NULL:
				return 0 + $ts;
			default:
				throw new \Exception('Unknown tsfmt');
		}
	}

	/**
	 * Analyze SQL statements
	 * @param  array $queries sql queries from logger
	 */
	protected function getSQLTimes($queries) {
		foreach ($queries as $query) {
			$this->sqlTotalTime += $query['executionMS'];
			if ($query['executionMS'] > $this->sqlWorstTime) {
				$this->sqlWorstTime = $query['executionMS'];
			}
		}
	}

	public abstract function add($object);
	protected abstract function render();

	/**
	 * Get primitive type or class
	 */
	public static function getClassOrType($var) {
		if ('object' === ($cot = gettype($var))) {
			$cot = get_class($var);
		}
		return $cot;
	}
}

?>
