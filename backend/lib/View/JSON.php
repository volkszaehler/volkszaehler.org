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
	 * constructor
	 */
	public function __construct(HTTP\Request $request, HTTP\Response $response) {
		parent::__construct($request, $response);

		$this->json = new Util\JSON();
		$this->json['source'] = 'volkszaehler.org';
		$this->json['version'] = VZ_VERSION;
		$this->json['component'] = 'backend';

		$this->setPadding($request->getParameter('padding'));
	}

	/**
	 * Process, encode and print output
	 */
	protected function render() {
		$json = $this->json->encode((Util\Debug::isActivated()) ? JSON_PRETTY : 0);

		if ($this->padding) {
			$json = 'if (self.' . $this->padding . ') { ' . $this->padding  . '(' . $json . '); }';
		}

		$this->response->setHeader('Content-type', 'application/json');
		echo $json;
	}

	/**
	 * Add channel to output queue
	 *
	 * @param Model\Channel $channel
	 */
	protected function addChannel(Model\Channel $channel) {
		$this->json['channel'] = self::convertEntity($channel);
	}

	/**
	 * Add aggregator to output queue
	 *
	 * @param Model\Aggregator $aggregator
	 * @param boolean $recursive
	 */
	protected function addAggregator(Model\Aggregator $aggregator, $recursive = FALSE) {
		$this->json['group'] = self::convertAggregator($aggregator, $recursive);
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
	protected function addException(\Exception $exception, $debug = FALSE) {
		$exceptionInfo = array(
			'type' => get_class($exception),
			'message' => $exception->getMessage(),
			'code' => $exception->getCode()
		);

		if ($debug) {
			$debugInfo = array('file' => $exception->getFile(),
				'line' => $exception->getLine(),
				'trace' => $exception->getTrace()
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
		$this->json['data'][$interpreter->getUuid()] = $interpreter->getValues($this->request->getParameter('resolution'));
	}

	protected function addArray($data) {
		foreach ($data as $index => $value) {
			$this->json[$index] = $value;
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
	 * @param boolean $recursive
	 * @return array
	 */
	protected static function convertAggregator(Model\Aggregator $aggregator) {
		$jsonAggregator = self::convertEntity($aggregator);

		foreach ($aggregator->getChildren() as $entity) {

			if ($entity instanceof Model\Channel) {
				$jsonAggregator['channels'][] = self::convertEntity($entity);
			}
			elseif ($entity instanceof Model\Aggregator) {
				$jsonAggregator['groups'][] = self::convertAggregator($entity);
			}
		}

		return $jsonAggregator;
	}

	public function add($data) {
		if ($data instanceof Util\JSON || is_array($data)) {
			$this->addArray($data);
		}
		else {
			parent::add($data);
		}
	}

	/*
	 * Setter & getter
	 */
	public function setPadding($padding) { $this->padding = $padding; }
}

?>
