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
 * XML view
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class XML extends View {
	/**
	 * @var DOMDocument contains the XML tree
	 */
	protected $xmlDoc;

	/**
	 * @var DOMNode reference to the XML root node
	 */
	protected $xmlRoot;
	
	/**
	 * Constructor
	 *
	 * @param HTTP\Request $request
	 * @param HTTP\Response $response
	 */
	public function __construct(HTTP\Request $request, HTTP\Response $response) {
		parent::__construct($request, $response);

		$this->xmlDoc = new \DOMDocument('1.0', 'UTF-8');

		$this->xmlRoot = $this->xmlDoc->createElement('volkszaehler');
		$this->xmlRoot->setAttribute('version', VZ_VERSION);

		$this->xmlDoc->appendChild($this->xmlRoot);
	}

	/**
	 * Process, encode and print output
	 *
	 * @return string the output
	 */
	protected function render() {
		$this->response->setHeader('Content-type', 'application/xml; charset=UTF-8');		
	
		echo $this->xmlDoc->saveXML();
	}

	/**
	 * Add Entity to output queue
	 *
	 * @param Model\Entity $entity
	 */
	protected function addEntity(Model\Entity $entity) {
		if ($entity instanceof Model\Aggregator) {
			$this->xmlRoot->appendChild($this->convertAggregator($entity));
		}
		else {
			$this->xmlRoot->appendChild($this->convertEntity($entity));
		}
	}

	/**
	 * Converts entity to DOMElement
	 *
	 * @param Model\Entity $entity
	 * @return DOMElement
	 */
	protected function convertEntity(Model\Entity $entity) {
		$xmlEntity = $this->xmlDoc->createElement('entity');		

		$xmlEntity->appendChild($this->xmlDoc->createElement('uuid', $entity->getUuid()));
		$xmlEntity->appendChild($this->xmlDoc->createElement('type', $entity->getType()));

		foreach ($entity->getProperties() as $key => $value) {
			$xmlEntity->appendChild($this->xmlDoc->createElement($key, $value));
		}

		return $xmlEntity;
	}

	/**
	 * Converts aggregator to DOMElement
	 *
	 * @param Model\Aggregator $aggregator
	 * @return DOMElement
	 */
	protected function convertAggregator(Model\Aggregator $aggregator, $recursive = FALSE) {
		$xmlAggregator = $this->convertEntity($aggregator);
		$xmlChildren = $this->xmlDoc->createElement('children');

		foreach ($aggregator->getChildren() as $entity) {
			if ($entity instanceof Model\Channel) {
				$xmlChildren->appendChild($this->convertEntity($entity));
			}
			elseif ($entity instanceof Model\Aggregator) {
				$xmlChildren->appendChild($this->convertAggregator($entity));
			}
		}
		$xmlAggregator->appendChild($xmlChildren);

		return $xmlAggregator;
	}

	/**
	 * Add debugging information include queries and messages to output queue
	 *
	 * @param Util\Debug $debug
	 */
	protected function addDebug(Util\Debug $debug) {
		$xmlDebug = $this->xmlDoc->createElement('debug');
		$xmlDebug->appendChild($this->xmlDoc->createElement('time', $debug->getExecutionTime()));
		$xmlDebug->appendChild($this->convertArray($debug->getMessages(), 'messages'));
		
		$xmlDatabase = $this->xmlDoc->createElement('database');
		$xmlDatabase->setAttribute('driver', Util\Configuration::read('db.driver'));
		$xmlDatabase->appendChild($this->convertArray($debug->getQueries(), 'queries'));

		$xmlDebug->appendChild($xmlDatabase);
		$this->xmlRoot->appendChild($xmlDebug);
	}

	/**
	 * Add exception to output queue
	 *
	 * @param \Exception $exception
	 * @param boolean $debug
	 */
	protected function addException(\Exception $exception) {
		$xmlException = $this->xmlDoc->createElement('exception');

		$xmlException->setAttribute('code', $exception->getCode());
		$xmlException->appendChild($this->xmlDoc->createElement('message', $exception->getMessage()));
		$xmlException->appendChild($this->xmlDoc->createElement('line', $exception->getLine()));
		$xmlException->appendChild($this->xmlDoc->createElement('file', $exception->getFile()));
		$xmlException->appendChild($this->convertTrace($exception->getTrace()));

		$this->xmlRoot->appendChild($xmlException);
	}

	/**
	 * Add data to output queue
	 *
	 * @param Interpreter\InterpreterInterface $interpreter
	 */
	protected function addData(Interpreter\InterpreterInterface $interpreter) {
		$data = $interpreter->getValues($this->request->getParameter('tuples'), $this->request->getParameter('group'));
		$xmlData = $this->xmlDoc->createElement('data');
		$xmlTuples = $this->xmlDoc->createElement('tuples');
		foreach ($data as $tuple) {
			$xmlTuple = $this->xmlDoc->createElement('tuple');
			$xmlTuple->setAttribute('timestamp', $tuple[0]);	// hardcoded data fields for performance optimization
			$xmlTuple->setAttribute('value', $tuple[1]);
			$xmlTuple->setAttribute('count', $tuple[2]);
			$xmlTuples->appendChild($xmlTuple);
		}

		$xmlData->appendChild($this->xmlDoc->createElement('uuid', $interpreter->getUuid()));
		$xmlData->appendChild($this->xmlDoc->createElement('count', count($data)));
		$xmlData->appendChild($this->xmlDoc->createElement('first', (isset($data[0][0])) ? $data[0][0] : NULL));
		$xmlData->appendChild($this->xmlDoc->createElement('last', (isset($data[count($data)-1][0])) ? $data[count($data)-1][0] : NULL));
		$xmlData->appendChild($this->xmlDoc->createElement('min', $interpreter->getMin()));
		$xmlData->appendChild($this->xmlDoc->createElement('max', $interpreter->getMax()));
		$xmlData->appendChild($this->xmlDoc->createElement('average', $interpreter->getAverage()));
		$xmlData->appendChild($xmlTuples);
	
		$this->xmlRoot->appendChild($xmlData);
	}

	/**
	 * Converts array to DOMElement
	 *
	 * @param array the input array
	 * @return DOMElement
	 */
	protected function convertArray(array $array, $identifier = 'array') {
		$xmlArray = $this->xmlDoc->createElement($identifier);

		foreach ($array as $key => $value) {
			// determine tagname
			if (is_numeric($key)) {
				if (substr($identifier, -3) == 'ies') {
					$key = substr($identifier, 0, -3) . 'y';
				}
				elseif (substr($identifier, -1) == 's') {
					$key = substr($identifier, 0, -1);
				}
				else {
					$key = 'index' . $key;
				}
			}

			if (is_array($value)) {
				$xmlArray->appendChild($this->convertArray($value, $key));
			}
			else {
				$xmlArray->appendChild($this->xmlDoc->createElement($key, (is_scalar($value)) ? $value : 'object'));
			}
		}
		
		return $xmlArray;
	}

	/**
	 * Converts excpetion backtrace to DOMElement
	 *
	 * @param array backtrace
	 * @return DOMElement
	 */
	private function convertTrace(array $traces) {
		$xmlTraces = $this->xmlDoc->createElement('backtrace');

		foreach ($traces as $step => $trace) {
			$xmlTrace = $this->xmlDoc->createElement('trace');
			$xmlTraces->appendChild($xmlTrace);
			$xmlTrace->setAttribute('step', $step);

			foreach ($trace as $key => $value) {
				switch ($key) {	
					case 'args':
						$xmlArgs = $this->xmlDoc->createElement($key);
						$xmlTrace->appendChild($xmlArgs);
						foreach ($value as $arg) {
							$xmlArgs->appendChild($this->xmlDoc->createElement('arg', (is_scalar($value)) ? $value : 'object'));
						}
						break;

					case 'type':
					case 'function':
					case 'line':
					case 'file':
					case 'class':
					default:
						$xmlTrace->appendChild($this->xmlDoc->createElement($key, $value));
				}
			}
		}

		return $xmlTraces;
	}

	/**
	 * Overloaded to support arrays
	 */
	public function add($data) {
		if (is_array($data)) {
			$this->xmlRoot->appendChild($this->convertArray($data));
		}
		else {
			parent::add($data);
		}
	}
}

?>
