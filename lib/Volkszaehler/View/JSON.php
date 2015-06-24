<?php
/**
 * @copyright Copyright (c) 2011, The volkszaehler.org project
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

use Volkszaehler\Interpreter;
use Volkszaehler\Util;
use Volkszaehler\Model;

/**
 * JSON view
 *
 * @package default
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Goetz <cpuidle@gmx.de>
 */
class JSON extends View {
	/**
	 * @var array holds the JSON data in an array
	 */
	protected $json;

	/**
	 * @var string padding function name or NULL if disabled
	 */
	protected $padding = FALSE;

	/**
	 * Constructor
	 */
	public function __construct(Request $request) {
		parent::__construct($request);

		$this->json = array();
		$this->json['version'] = VZ_VERSION;

		// JSONP
		if ($this->padding = $request->parameters->get('padding')) {
			$this->response->headers->set('Content-Type', 'application/javascript');
		}
		else {
			$this->response->headers->set('Content-Type', 'application/json');
			// enable CORS if not JSONP
			$this->response->headers->set('Access-Control-Allow-Origin', '*');
		}
	}

	/**
	 * Creates exception response
	 *
	 * @param \Exception $exception
	 */
	public function getExceptionResponse(\Exception $exception) {
		$this->add($exception);
		$this->response->setStatusCode(($this->padding) ? 200 : 400);

		return $this->send();
	}

	/**
	 * Process, encode and print output to stdout
	 */
	protected function render() {
		$json = Util\Json::encode($this->json, (Util\Debug::isActivated()) ? JSON_PRETTY : 0);

		if ($this->padding) {
			$json = $this->padding . '(' . $json . ');';
		}

		return $json;
	}

	/**
	 * Add object to output
	 *
	 * @param mixed $data
	 */
	public function add($data) {
		if ($data instanceof Interpreter\Interpreter) {
			$this->json['data'] = self::convertInterpreter($data);
		}
		elseif ($data instanceof Model\Entity) {
			$this->json['entity'] = self::convertEntity($data);
		}
		elseif ($data instanceof \Exception) {
			$this->addException($data);
		}
		elseif ($data instanceof Util\Debug) {
			$this->addDebug($data);
		}
		elseif (is_array($data)) {
			$this->addArray($data, $this->json);
		}
		elseif (isset($data)) { // ignores NULL data
			throw new \Exception('Can\'t show: \'' . get_class($data) . '\'');
		}
	}

	/**
	 * Convert interpreter to json-serializable object
	 *
	 * @param Interpreter\Interpreter $interpreter
	 * @return JsonInterpreterWrapper
	 */
	protected static function convertInterpreter(Interpreter\Interpreter $interpreter) {
		return new JsonInterpreterWrapper($interpreter);
	}

	/**
	 * Converts entity to array for json_encode()
	 *
	 * @param Model\Entity $entity
	 * @return array
	 */
	protected static function convertEntity(Model\Entity $entity, $chain = array()) {
		$jsonEntity = array();
		$jsonEntity['uuid'] = (string) $entity->getUuid();
		$jsonEntity['type'] = $entity->getType();

		foreach ($entity->getProperties() as $key => $value) {
			$jsonEntity[$key] = $value;
		}

		if ($entity instanceof Model\Aggregator) {
			$chain[$entity->getUuid()] = 1;
			foreach ($entity->getChildren() as $child) {
				if (array_key_exists($child->getUuid(), $chain))
					continue; # don't ever loop back
				$jsonEntity['children'][] = self::convertEntity($child, $chain);
			}
		}

		return $jsonEntity;
	}

	/**
	 * Add an array of objects to the output
	 */
	protected function addArray($data, &$refNode) {
		if (is_null($refNode)) {
			$refNode = array();
		}

		foreach ($data as $index => $value) {
			if (is_array($value)) {
				$this->addArray($value, $refNode[$index]);
			}
			elseif ($value instanceof Model\Entity) {
				$refNode[$index] = self::convertEntity($value);
			}
			elseif ($value instanceof Interpreter\Interpreter) {
				// special case: interpreters are always added to the root node
				if (!isset($this->json['data'])) {
					$this->json['data'] = array();
				}
				$this->json['data'][] = self::convertInterpreter($value);
			}
			elseif (is_numeric($value)) {
				$refNode[$index] = View::formatNumber($value);
			}
			else {
				$refNode[$index] = $value;
			}
		}
	}

	/**
	 * Add debugging information include queries and messages to output queue
	 *
	 * @param Util\Debug $debug
	 */
	protected function addDebug(Util\Debug $debug) {
		$jsonDebug['level'] = $debug->getLevel();
		if ($dbDriver = Util\Configuration::read('db.driver')) $jsonDebug['database'] = $dbDriver;
		$jsonDebug['time'] = $debug->getExecutionTime();

		if ($uptime = Util\Debug::getUptime()) $jsonDebug['uptime'] = $uptime*1000;
		if ($load = Util\Debug::getLoadAvg()) $jsonDebug['load'] = $load;
		if ($commit = Util\Debug::getCurrentCommit()) $jsonDebug['commit-hash'] = $commit;
		if ($version = phpversion()) $jsonDebug['php-version'] = $version;

		$jsonDebug['messages'] = $debug->getMessages();

		// SQL statements
		$this->getSQLTimes($debug->getQueries());
		$jsonDebug['sql'] = array(
			'totalTime' => $this->sqlTotalTime,
			'worstTime' => $this->sqlWorstTime,
			'queries' => array_values($debug->getQueries())
		);

		$this->json['debug'] = $jsonDebug;
	}

	/**
	 * Add exception to output queue
	 *
	 * @param \Exception $exception
	 * @param boolean $debug
	 */
	protected function addException(\Exception $exception) {
		$exceptionType = explode('\\', get_class($exception));
		$exceptionInfo = array(
			'message' => $exception->getMessage(),
			'type' => end($exceptionType),
			'code' => $exception->getCode()
		);

		if (Util\Debug::isActivated()) {
			$debugInfo = array(
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
				'backtrace' => $exception->getTrace()
			);

			$this->json['exception'] = array_merge($exceptionInfo, $debugInfo);
		}
		else {
			$this->json['exception'] = $exceptionInfo;
		}
	}
}

/**
 * Interpreter to JSON converter with low memory footprint
 */
class JsonInterpreterWrapper {

	protected $interpreter;

	public function __construct($interpreter) {
		$this->interpreter = $interpreter;
	}

	/**
	 * Called by Zend\Json\Encode::encode to convert object to string
	 */
	public function toJson() {
		$interpreter = $this->interpreter;

		// iterate through PDO resultset to populate interpreter
		$tuples = '';
		foreach ($interpreter as $tuple) {
			$tuples .= json_encode(
				array(
					$tuple[0],
					View::formatNumber($tuple[1]),
					$tuple[2]
				)
			) . ',';
		}

		$from = 0 + $interpreter->getFrom();
		$to = 0 + $interpreter->getTo();
		$min = $interpreter->getMin();
		$max = $interpreter->getMax();
		$average = $interpreter->getAverage();
		$consumption = $interpreter->getConsumption();

		$header = array();
		$header['uuid'] = $interpreter->getEntity()->getUuid();
		if (isset($from)) $header['from'] = $from;
		if (isset($to)) $header['to'] = $to;
		if (isset($min)) $header['min'] = $min;
		if (isset($max)) $header['max'] = $max;
		if (isset($average)) $header['average'] = View::formatNumber($average);
		if (isset($consumption)) $header['consumption'] = View::formatNumber($consumption);
		$header['rows'] = $interpreter->getRowCount();
		$json = json_encode($header);

		// for historic reasons, add tuples after header data
		if (strlen($tuples) > 0) {
			// insert before closing } bracket
			$json = substr_replace($json, ',"tuples":[' . substr($tuples, 0, -1) . ']', -1, 0);
		}

		return $json;
	}
}

?>
