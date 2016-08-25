<?php
/**
 * @copyright Copyright (c) 2016, The volkszaehler.org project
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @package util
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

namespace Volkszaehler\Server;

use Volkszaehler\Router;
use Volkszaehler\Interpreter;
use Volkszaehler\Controller\EntityController;

use Symfony\Component\HttpFoundation\Request;

/**
 * Convert raw meter readings to frontend-compatible current values
 */
class MiddlewareAdapter {

	/**
	 * @var interpreters per subscribed topic
	 */
	protected $interpreters = array();

	/**
	 * @var Doctrine\ORM\EntityManager
	 */
	protected $em;

	/**
	 * @var array
	 */
	protected $adapters;

	public function __construct() {
		$this->adapters = new \SplObjectStorage;
	}

	public function addAdapter(PushTransportInterface $adapter) {
		$this->adapters->attach($adapter);
	}

	public function removeAdapter(PushTransportInterface $adapter) {
		$this->adapters->detach($adapter);
	}

	protected function openController($force = false) {
		if ($force || $this->em == null || !$this->em->isOpen()) {
			$this->em = Router::createEntityManager(false);
		}
	}

	protected function connectToMiddleware($uuid) {
		try {
			$this->openController();

			$entity = EntityController::factory($this->em, $uuid);
			$class = $entity->getDefinition()->getInterpreter();
			$interpreter = new $class($entity, $this->em, null, null);

			return $interpreter;
		}
		catch (\Exception $e) {
			echo("Trying to subcribe to invalid topic " . $uuid . "\n");
			throw $e;
		}
	}

	protected function convertRawTuple(Interpreter\Interpreter $interpreter, $tuple) {
		try {
			$this->openController();
			$result = false;

			// convert raw reading to converted value
			if (!isset($interpreter->push_ts)) {
				// skip first conversion result
				$interpreter->convertRawTuple($tuple);
			}
			// prevent div by zero
			elseif ($tuple[0] > $interpreter->push_ts) {
				// AccumulatorInterpreter special handling- suppress duplicate counter values
				if ($interpreter instanceof Interpreter\AccumulatorInterpreter) {
					if (isset($interpreter->push_raw_value) && $interpreter->push_raw_value == $tuple[1]) {
						return false;
					}
				}

				$result = $interpreter->convertRawTuple($tuple);
			}

			// indicate that tuple conversion has already happened once
			$interpreter->push_ts = $tuple[0];
			$interpreter->push_raw_value = $tuple[1];
		}
		catch (\Exception $e) {
			// make sure EntityManager is re-initialized on error
			$this->openController(true);
			return false;
		}

		return $result;
	}

	/**
	 * Handle vzlogger push request
	 *
	 * @param string JSON'ified string we'll receive from publisher
	 * @return null|string Returns null on invalid request
	 */
	public function handlePushMessage($json) {
		$response = array(
			'version' => VZ_VERSION,
			'data' => array()
		);

		// validate input message
		if (null === ($msg = json_decode($json, true))) {
			return null;
		}
		if (null === ($data = @$msg['data']) || !is_array($data)) {
			return null;
		}

		// loop through channels
		foreach ($data as $channel) {
			// validate channel structure
			if (null === ($uuid = @$channel['uuid'])) {
				return null;
			}
			if (null === ($tuples = @$channel['tuples']) || !is_array($tuples) || !count($tuples)) {
				return null;
			}

			// get interpreter if no client has connected yet
			if (null === ($interpreter = $this->getInterpreter($uuid))) {
				$interpreter = $this->connectToMiddleware($uuid);
				$this->addInterpreter($uuid, $interpreter);
			}

			// convert raw tuples using interpreter rules
			$transformed = array();
			foreach ($tuples as $tuple) {
				if (!is_array($tuple) || count($tuple) < 2) {
					return null;
				}

				if (count($tuple) < 3) {
					$tuple[] = 1;
				}

				// first ever tuple may be swallowed, skip if payload == false
				if ($payload = $this->convertRawTuple($interpreter, $tuple)) {
					$transformed[] = $payload;
				}
			}

			$channelData = array(
				'uuid' => $uuid,
				'tuples' => $transformed
			);

			if (count($transformed)) {
				$this->broadcast($uuid, array(
					'version' => VZ_VERSION,
					'data' => $channelData
				));
			}

			$response['data'][] = $channelData;
		}

		return json_encode($response);
	}

	protected function broadcast($uuid, $data) {
		foreach ($this->adapters as $adapter) {
			$adapter->onUpdate($uuid, $data);
		}
	}

	protected function getInterpreter($uuid) {
		if (isset($this->interpreters[$uuid])) {
			return $this->interpreters[$uuid];
		}
		return null;
	}

	protected function addInterpreter($uuid, $interpreter) {
		$this->interpreters[$uuid] = $interpreter;
	}
}
