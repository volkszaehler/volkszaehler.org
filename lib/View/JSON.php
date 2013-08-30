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

use Volkszaehler\Interpreter;
use Volkszaehler\View\HTTP;
use Volkszaehler\Util;
use Volkszaehler\Model;

/**
 * JSON view
 *
 * @package default
 * @author Steffen Vogel <info@steffenvogel.de>
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
	 *
	 * @param HTTP\Request $request
	 * @param HTTP\Response $response
	 */
	public function __construct(HTTP\Request $request, HTTP\Response $response) {
		parent::__construct($request, $response);

		$this->json = new Util\JSON();
		$this->json['version'] = VZ_VERSION;

		$this->response->setHeader('Content-type', 'application/json');
		$this->setPadding($request->getParameter('padding'));
	}

	/**
	 * Add object to output
	 *
	 * @param mixed $data
	 */
	public function add($data) {
		if ($data instanceof Interpreter\AggregatorInterpreter) {
			$this->addAggregateData($data);
		}
		elseif ($data instanceof Interpreter\Interpreter) {
			$this->addData($data);
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
		elseif ($data instanceof Util\JSON || is_array($data)) {
			$this->addArray($data, $this->json);
		}
		elseif (isset($data)) { // ignores NULL data
			throw new \Exception('Can\'t show: \'' . get_class($data) . '\'');
		}
	}

	/**
	 * Process, encode and print output to stdout
	 */
	protected function render() {
		$json = $this->json->encode((Util\Debug::isActivated()) ? JSON_PRETTY : 0);

		if ($this->padding) {
			$json = $this->padding  . '(' . $json . ');';
		}
		echo $json;
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
		if ($version = Util\Debug::getPhpVersion()) $jsonDebug['php-version'] = $version;
		
		$jsonDebug['messages'] = $debug->getMessages();
		$jsonDebug['queries'] = array_values($debug->getQueries());

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

	/**
	 * Add multiple data objects to output queue
	 *
	 * @param $interpreter
	 */
	protected function addAggregateData($interpreter) {
		// child entities first to ensure min/max etc are populated
		foreach ($interpreter->getChildrenInterpreter() as $childInterpreter) {
			$this->addData($childInterpreter, true);
		}
		$this->addData($interpreter);
	}

	/**
	 * Add data to output queue
	 *
	 * @param $interpreter
	 * @param boolean $children
	 */
	protected function addData($interpreter, $children = false) {
		$data = $interpreter->processData( // iterate through PDO resultset
			function($tuple) {
				return array(
					$tuple[0],
					View::formatNumber($tuple[1]),
					$tuple[2]
				);
			}
		);
		
		$from = 0 + $interpreter->getFrom();
		$to = 0 + $interpreter->getTo();
		$min = $interpreter->getMin();
		$max = $interpreter->getMax();
		$average = $interpreter->getAverage();
		$consumption = $interpreter->getConsumption();

		$wrapper = array();
		$wrapper['uuid'] = $interpreter->getEntity()->getUuid();
		if (isset($from)) $wrapper['from'] = $from;
		if (isset($to)) $wrapper['to'] = $to;
		if (isset($min)) $wrapper['min'] = $min;
		if (isset($max)) $wrapper['max'] = $max;
		if (isset($average)) $wrapper['average'] = View::formatNumber($average);
		if (isset($consumption)) $wrapper['consumption'] = View::formatNumber($consumption);
			
		$wrapper['rows'] = $interpreter->getRowCount();
		
		if (($interpreter->getTupleCount() > 0 || is_null($interpreter->getTupleCount())) && count($data) > 0)
			$wrapper['tuples'] = $data;

		if (!isset($this->json['data'])) {
			// make sure json['data'] is initialized when child data is added
			$this->json['data'] = array();
		}
		if ($children == false) {
			// preserve child data if existing
			$this->json['data'] = array_merge($wrapper, $this->json['data']);
		}
		else {
			$this->json['data']['children'][] = $wrapper;
		}
	}

	protected function addArray($data, &$refNode) {
		if (is_null($refNode)) {
			$refNode = array();
		}
		
		foreach ($data as $index => $value) {
			if ($value instanceof Util\JSON || is_array($value)) {
				$this->addArray($value, $refNode[$index]);
			}
			elseif ($value instanceof Model\Entity) {
				$refNode[$index] = self::convertEntity($value);
			}
			elseif (is_numeric($value)) {
				$refNode[$index] = View::formatNumber($value);
			}
			else {
				$refNode[$index] = $value;
			}
		}
	}

	/*
	 * Setter & getter
	 */

	public function setPadding($padding) { $this->padding = $padding; }
}

?>
