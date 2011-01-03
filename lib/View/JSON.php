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

		$this->setPadding($request->getParameter('padding'));
	}

	/**
	 * Add object to output
	 *
	 * @param mixed $data
	 */
	public function add($data) {
		if ($data instanceof Interpreter\InterpreterInterface) {
			$this->addData($data);
		}
		elseif ($data instanceof Model\Entity) {
			$this->addEntity($data);
		}
		elseif ($data instanceof \Exception) {
			$this->addException($data);
		}
		elseif ($data instanceof Util\Debug) {
			$this->addDebug($data);
		}
		elseif ($data instanceof Util\JSON || is_array($data)) {
			$this->addArray($data);
		}
		elseif (isset($data)) { // ignores NULL data
			throw new \Exception('Can\'t show ' . get_class($data));
		}
	}

	/**
	 * Process, encode and print output
	 *
	 * @return string the output
	 */
	protected function render() {
		$json = $this->json->encode((Util\Debug::isActivated()) ? JSON_PRETTY : 0);

		if ($this->padding) {
			$json = 'if (' . $this->padding . ') { ' . $this->padding  . '(' . $json . '); }';
		}

		$this->response->setHeader('Content-type', 'application/json');
		echo $json;
	}

	/**
	 * Add Entity to output queue
	 *
	 * @param Model\Entity $entity
	 */
	protected function addEntity(Model\Entity $entity) {
		if ($entity instanceof Model\Aggregator) {
			$this->json['entity'] = self::convertAggregator($entity);
		}
		else {
			$this->json['entity'] = self::convertEntity($entity);
		}
	}

	/**
	 * Converts entity to array for json_encode()
	 *
	 * @param Model\Entity $entity
	 * @return array
	 */
	protected static function convertEntity(Model\Entity $entity) {
		$jsonEntity = array();
		$jsonEntity['uuid'] = (string) $entity->getUuid();
		$jsonEntity['type'] = $entity->getType();


		foreach ($entity->getProperties() as $key => $value) {
			$jsonEntity[$key] = $value;
		}

		return $jsonEntity;
	}

	/**
	 * Converts aggregator to array for json_encode
	 *
	 * @param Model\Aggregator $aggregator
	 * @return array
	 */
	protected static function convertAggregator(Model\Aggregator $aggregator) {
		$jsonAggregator = self::convertEntity($aggregator);

		foreach ($aggregator->getChildren() as $entity) {
			if ($entity instanceof Model\Channel) {
				$jsonAggregator['children'][] = self::convertEntity($entity);
			}
			elseif ($entity instanceof Model\Aggregator) {
				$jsonAggregator['children'][] = self::convertAggregator($entity);
			}
		}

		return $jsonAggregator;
	}

	/**
	 * Add debugging information include queries and messages to output queue
	 *
	 * @param Util\Debug $debug
	 */
	protected function addDebug(Util\Debug $debug) {
		$queries = $debug->getQueries();
		$messages = $debug->getMessages();

		$jsonDebug['time'] = $debug->getExecutionTime();

		if (count($messages) > 0) {
			$jsonDebug['messages'] = $messages;
		}

		if (count($queries) > 0) {
			$jsonDebug['database'] = array(
				'driver' => Util\Configuration::read('db.driver'),
				'queries' => $queries
			);
		}

		$this->json['debug'] = $jsonDebug;
	}

	/**
	 * Add exception to output queue
	 *
	 * @param \Exception $exception
	 * @param boolean $debug
	 */
	protected function addException(\Exception $exception) {
		$exceptionInfo = array(
			'message' => $exception->getMessage(),
			'type' => get_class($exception),
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
	 * Add data to output queue
	 *
	 * @param Interpreter\InterpreterInterface $interpreter
	 */
	protected function addData(Interpreter\InterpreterInterface $interpreter) {
		$data = $interpreter->getValues($this->request->getParameter('tuples'), $this->request->getParameter('group'));

		$this->json['data'] = array(
			'uuid'		=> $interpreter->getEntity()->getUuid(),
			'count'		=> count($data),
			'first'		=> (isset($data[0][0])) ? $data[0][0] : NULL,
			'last'		=> (isset($data[count($data)-1][0])) ? $data[count($data)-1][0] : NULL,
			'min'		=> $interpreter->getMin(),
			'max'		=> $interpreter->getMax(),
			'average'	=> $interpreter->getAverage(),
			'tuples'	=> $data
		);
	}

	/**
	 * Insert array in output
	 *
	 * @todo fix workaround for public entities
	 */
	protected function addArray($data) {
		foreach ($data as $index => $value) {
			if ($value instanceof Model\Entity) {
				$this->json['entities'][] = self::convertEntity($value);
			}
			else {
				$this->json[$index] = $value;
			}
		}
	}

	/*
	 * Setter & getter
	 */

	public function setPadding($padding) { $this->padding = $padding; }
}

?>
