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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
	 * @var string StreamedResponse content buffer, see renderDeferred()
	 */
	protected $content;

	/**
	 * Constructor
	 */
	public function __construct(Request $request) {
		parent::__construct($request);

		$this->json = array('version' => VZ_VERSION);
		// use StreamedResponse unless pretty-printing is required
		if (Util\Debug::isActivated()) {
			$this->add(Util\Debug::getInstance());
		}
		else {
			$this->response = new StreamedResponse();
		}

		// add JSON headers
		$this->response->headers->set('Content-Type', 'application/json');
		$this->response->headers->set('Access-Control-Allow-Origin', '*');
		$this->response->headers->set('Access-Control-Allow-Headers', 'Authorization');
	}

	/**
	 * Render response and send it to the client
	 */
	public function send() {
		if ($this->response instanceof StreamedResponse) {
			$this->response->setCallback(function() {
				$this->renderDeferred();
				flush();
			});
		}
		else {
			ob_start();
			$this->renderDeferred();
			$json = ob_get_contents();
			ob_end_clean();

			if (Util\Debug::isActivated()) {
				$json = Util\JSON::format($json);
			}

			$this->response->setContent($json);
		}

		return $this->response;
	}

	/**
	 * Creates exception response
	 *
	 * @param \Exception $exception
	 */
	public function getExceptionResponse(\Exception $exception) {
		$this->add($exception);
		$this->response->setStatusCode(Response::HTTP_BAD_REQUEST);

		return $this->send();
	}

	protected function render() {
		throw new \LogicException('Cannot call render when using StreamedResponse');
	}

	/**
	 * Process, encode and print output to stdout
	 *
	 * For StreamedResponse the renderDeferred happens outside Router->handle() and therefore
	 * without surrounding try/catch. To enable exception handling via json responses, all output
	 * must be collected until Exception occured and is handled by View.
	 *
	 * Since most likely source of exceptions and source of mass-data is Interpreter->processData(),
	 * this case is implemented in renderInterpreter() below.
	 */
	protected function renderDeferred() {
		$this->content .= '{';
		$contentStarted = false;

		foreach ($this->json as $key => $data) {
			if ($contentStarted) {
				$this->content .= ",";
			}
			$contentStarted = true;

			$this->content .= '"' . $key . '":';

			if ($data instanceof Interpreter\Interpreter) {
				// single interpreter
				$this->renderInterpreter($data);
			}
			elseif (is_array($data) && isset($data[0]) && $data[0] instanceof Interpreter\Interpreter) {
				// array of interpreters
				$this->content .= '[';
				foreach ($data as $key => $interpreter) {
					if ($key) $this->content .= ',';
					$this->renderInterpreter($interpreter);
				}
				$this->content .= ']';
			}
			elseif ($data instanceof Util\Debug) {
				$this->content .= json_encode($this->convertDebug($data));
			}
			else {
				$this->content .= json_encode($data);
			}
		}

		$this->content .= '}';

		$this->renderContent();
	}

	/**
	 * Render collected buffer
	 */
	private function renderContent() {
		if ($this->content) {
			echo($this->content);
			$this->content = '';
		}
	}

	/**
	 * Render exception for StreamedResponse
	 */
	private function renderStreamedException(\Exception $exception) {
		// cleanup result structure
		$this->json = array('version' => VZ_VERSION);
		$this->content = '';

		// add exception to output
		$this->getExceptionResponse($exception);

		// render
		$this->response->sendHeaders();
		$this->renderDeferred();
	}

	/**
	 * Render Interpreter output
	 *
	 * See comments regarding StreamedResponse at renderDeferred()
	 */
	protected function renderInterpreter(Interpreter\Interpreter $interpreter) {
		$this->content .= '{"tuples":[';

		try {
			// start with iterating through PDO result set to populate interpreter header data
			foreach ($interpreter as $key => $tuple) {
				// render buffered content- likely no exception after loop iteration has started
				$this->renderContent();

				if ($key) echo(',');
				echo('[' . $tuple[0] . ',' . View::formatNumber($tuple[1]) . ',' . $tuple[2] . ']');
			}
		}
		catch (\Exception $e) {
			if ($this->response instanceof StreamedResponse) {
				$this->renderStreamedException($e);
				die;
			}
			throw ($e);
		}

		// render buffered content if not rendered inside foreach loop due to Interpreter empty
		$this->renderContent();

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

		echo('],' . substr(json_encode($header), 1, -1) . '}');
	}

	/**
	 * Add object to output
	 *
	 * @param mixed $data
	 */
	public function add($data) {
		if ($data instanceof Interpreter\Interpreter) {
			$this->json['data'] = $data;
		}
		elseif ($data instanceof Model\Entity) {
			$this->json['entity'] = self::convertEntity($data);
		}
		elseif ($data instanceof Util\Debug) {
			$this->json['debug'] = $data;
		}
		elseif ($data instanceof \Exception) {
			$this->addException($data);
		}
		elseif (is_array($data)) {
			$this->addArray($data, $this->json);
		}
		elseif (isset($data)) { // ignores NULL data
			throw new \Exception('Can\'t show: \'' . self::getClassOrType($data) . '\'');
		}
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
	protected function convertDebug(Util\Debug $debug) {
		$jsonDebug['level'] = $debug->getLevel();
		if ($dbDriver = Util\Configuration::read('db.driver')) $jsonDebug['database'] = $dbDriver;
		$jsonDebug['time'] = $debug->getExecutionTime();

		if ($uptime = Util\Debug::getUptime()) $jsonDebug['uptime'] = $uptime*1000;
		if ($load = Util\Debug::getLoadAvg()) $jsonDebug['load'] = $load;
		if ($commit = Util\Debug::getCurrentCommit()) $jsonDebug['commit-hash'] = $commit;
		if ($version = phpversion()) $jsonDebug['php-version'] = $version;

		$jsonDebug['messages'] = $debug->getMessages();

		// SQL statements
		if (count($statements = $debug->getQueries())) {
			$this->getSQLTimes($statements);
			$jsonDebug['sql'] = array(
				'totalTime' => $this->sqlTotalTime,
				'worstTime' => $this->sqlWorstTime,
				'queries' =>
					array_map(function($q) {
						return array(
							'sql' => Util\Debug::getParametrizedQuery($q['sql'], $q['params']),
							'execTime' => $q['executionMS']
						);
					}, array_values($debug->getQueries()))
			);
		}

		return $jsonDebug;
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
				$this->json['data'][] = $value;
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

?>
