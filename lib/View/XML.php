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
	 * Add object to output
	 *
	 * @param mixed $data
	 */
	public function add($data) {
		if ($data instanceof Interpreter\Interpreter || $data instanceof Interpreter\AggregatorInterpreter) {
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
		elseif (is_array($data) || $data instanceof Util\JSON) {
			foreach($data as $key => $value) {
				$this->xmlRoot->appendChild($this->convertArray($value, $key));
			}
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
		$xmlDebug->setAttribute('level', $debug->getLevel());
		$xmlDebug->appendChild($this->xmlDoc->createElement('time', $debug->getExecutionTime()));

		if ($uptime = Util\Debug::getUptime()) $xmlDebug->appendChild($this->xmlDoc->createElement('uptime', $uptime*1000));		
		if ($load = Util\Debug::getLoadAvg()) $xmlDebug->appendChild($this->xmlDoc->createElement('load', implode(', ', $load)));
		if ($commit = Util\Debug::getCurrentCommit()) $xmlDebug->appendChild($this->xmlDoc->createElement('commit-hash', $commit));
		if ($version = Util\Debug::getPhpVersion()) $xmlDebug->appendChild($this->xmlDoc->createElement('php-version', $version));
		
		$xmlMessages = $this->xmlDoc->createElement('messages');
		foreach ($debug->getMessages() as $message) {
			$xmlMessages->appendChild($this->convertMessage($message));
		}
		
		$xmlDebug->appendChild($xmlMessages);
		$xmlDebug->appendChild($this->convertArray($debug->getQueries(), 'queries', 'query'));
		$this->xmlRoot->appendChild($xmlDebug);
	}

	/**
	 * Add exception to output queue
	 *
	 * @param \Exception $exception
	 * @param boolean $debug
	 */
	protected function addException(\Exception $exception) {
		$exceptionType = explode('\\', get_class($exception));

		$xmlException = $this->xmlDoc->createElement('exception');
		$xmlException->setAttribute('code', $exception->getCode());
		$xmlException->setAttribute('type', end($exceptionType));

		$xmlException->appendChild($this->xmlDoc->createElement('message', $exception->getMessage()));

		if (Util\Debug::isActivated()) {
			$xmlException->appendChild($this->xmlDoc->createElement('file', $exception->getFile()));
			$xmlException->appendChild($this->xmlDoc->createElement('line', $exception->getLine()));
			$xmlException->appendChild($this->convertTrace($exception->getTrace()));
		}

		$this->xmlRoot->appendChild($xmlException);
	}
	
	/**
	 * Converts message to DOMElement
	 *
	 * @param array $message
	 * @return DOMElement
	 */
	protected function convertMessage($message) {
		$xmlMessage = $this->xmlDoc->createElement('message');

		$xmlMessage->appendChild($this->xmlDoc->createElement('message', $message['message']));

		if (isset($message['file'])) $xmlMessage->appendChild($this->xmlDoc->createElement('file', $message['file']));
		if (isset($message['line'])) $xmlMessage->appendChild($this->xmlDoc->createElement('line', $message['line']));
		if (isset($message['args'])) $xmlMessage->appendChild($this->convertArray($message['args'], 'args', 'arg'));
		if (isset($message['trace'])) $xmlMessage->appendChild($this->convertTrace($message['trace']));
		
		return $xmlMessage;
	}

	/**
	 * Add data to output queue
	 *
	 * @param $interpreter
	 */
	protected function addData($interpreter) {
		$xmlDoc = $this->xmlDoc;
		$xmlData = $this->xmlDoc->createElement('data');
		$xmlTuples = $this->xmlDoc->createElement('tuples');
		
		$data = $interpreter->processData(
			function($tuple) use ($xmlDoc, $xmlTuples) {
				$xmlTuple = $xmlDoc->createElement('tuple');
				$xmlTuple->setAttribute('timestamp', $tuple[0]);
				$xmlTuple->setAttribute('value', View::formatNumber($tuple[1]));
				$xmlTuple->setAttribute('count', $tuple[2]);
				$xmlTuples->appendChild($xmlTuple);
				
				return $tuple;
			}
		);
		
		$from = $interpreter->getFrom();
		$to = $interpreter->getTo();
		$min = $interpreter->getMin();
		$max = $interpreter->getMax();
		$average = $interpreter->getAverage();
		$consumption = $interpreter->getConsumption();
		
		$xmlData->appendChild($this->xmlDoc->createElement('uuid', $interpreter->getEntity()->getUuid()));
		if (isset($from)) 
			$xmlData->appendChild($this->xmlDoc->createElement('from', $from));
			
		if (isset($to)) 
			$xmlData->appendChild($this->xmlDoc->createElement('to', $to));
			
		if (isset($min)) {
			$xmlMin = $this->xmlDoc->createElement('min');
			$xmlMin->setAttribute('timestamp', $min[0]);
			$xmlMin->setAttribute('value', $min[1]);
			$xmlData->appendChild($xmlMin);
		}
			
		if (isset($max)) {
			$xmlMax = $this->xmlDoc->createElement('max');
			$xmlMax->setAttribute('timestamp', $max[0]);
			$xmlMax->setAttribute('value', $max[1]);
			$xmlData->appendChild($xmlMax);
		}
			
		if (isset($average)) 
			$xmlData->appendChild($this->xmlDoc->createElement('average', View::formatNumber($average)));
			
		if (isset($consumption))
			$xmlData->appendChild($this->xmlDoc->createElement('consumption', View::formatNumber($consumption)));
			
		$xmlData->appendChild($this->xmlDoc->createElement('rows', $interpreter->getRowCount()));
		
		if (($interpreter->getTupleCount() > 0 || is_null($interpreter->getTupleCount())) && count($data) > 0)
			$xmlData->appendChild($xmlTuples);
	
		$this->xmlRoot->appendChild($xmlData);
	}

	/**
	 * Converts array to DOMElement
	 *
	 * @param array the input array
	 * @return DOMElement
	 */
	protected function convertArray($array, $identifierPlural = 'array', $identifierSingular = 'entry') {
		$xmlArray = $this->xmlDoc->createElement($identifierPlural);

		foreach ($array as $key => $value) {
			if (is_numeric($key)) {
				$key = $identifierSingular;
			}

			if (is_null($value)) {
				$value = 'null';
			}
			
			if (is_array($value) || $value instanceof Util\JSON || $value instanceof \stdClass) {
				$xmlArray->appendChild($this->convertArray($value, $key));
			}
			elseif (is_numeric($value)) {
				$xmlArray->appendChild($this->xmlDoc->createElement($key, self::formatNumber($value)));
			}
			elseif (is_scalar($value)) {
				$xmlArray->appendChild($this->xmlDoc->createElement($key, $value));
			}
			else { // TODO required?
				$xmlArray->appendChild($this->xmlDoc->createElement($key, 'object:' . get_class($value)));
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
						$xmlTrace->appendChild($this->convertArray($value, 'args', 'arg'));
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
}

?>
