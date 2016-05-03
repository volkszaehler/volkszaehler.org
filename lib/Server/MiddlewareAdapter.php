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
use Volkszaehler\Interpreter\Interpreter;
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
	 * @var EntityController
	 */
	protected $controller;

	/**
	 * @var array
	 */
	protected $adapters;

	public function __construct() {
		$this->adapters = new \SplObjectStorage;
	}

	public function addAdapter(PushClientMessageInterface $adapter) {
		$this->adapters->attach($adapter);
	}

	public function removeAdapter(PushClientMessageInterface $adapter) {
		$this->adapters->detach($adapter);
	}

	protected function openController($force = false) {
		if ($force || $this->em == null || $this->controller == null || !$this->em->isOpen()) {
			$this->em = Router::createEntityManager(false);
			$this->controller = new EntityController(new Request(), $this->em);
		}
	}

	protected function connectToMiddleware($uuid) {
		try {
			$this->openController();

			$entity = $this->controller->getSingleEntity($uuid);
			$class = $entity->getDefinition()->getInterpreter();
			$interpreter = new $class($entity, $this->em, null, null);

			return $interpreter;
		}
		catch (\Exception $e) {
			echo("Trying to subcribe to invalid topic " . $uuid . "\n");
			throw $e;
		}
	}

	protected function getPayload(Interpreter $interpreter, $tuple) {
		try {
			$this->openController();
			$result = false;

			// prevent div by zero
			if (!isset($interpreter->calculated_ts) || ($tuple[0] > $interpreter->calculated_ts)) {
				$result = $interpreter->convertRawTuple($tuple);
			}

			// 1st calculated value is invalid due to interpreter logic
			if (!isset($interpreter->calculated_ts)) {
				$result = false;
			}

			$interpreter->calculated_ts = $tuple[0];
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
		$msg = json_decode($json, true);
		$response = array(
			'version' => VZ_VERSION,
			'data' => array()
		);

		// validate input message
		if (null === ($data = @$msg['data']) || !is_array($data)) {
			return null;
		}

		// loop through channels
		foreach ($data as $channel) {
			if (null === ($uuid = @$channel['uuid'])) {
				break;
			}
			if (null === ($tuples = @$channel['tuples']) || !is_array($tuples) || !count($tuples)) {
				break;
			}

			// get interpreter if no client has connected yet
			if (null === ($interpreter = $this->getInterpreter($uuid))) {
				$interpreter = $this->connectToMiddleware($uuid);
				$this->addInterpreter($uuid, $interpreter);
			}

			// convert raw tuples using interpreter rules
			$transformed = array();
			foreach ($tuples as $tuple) {
				if (count($tuple < 3)) {
					$tuple[] = 1;
				}

				// first ever tuple may be swallowed
				if ($payload = $this->getPayload($interpreter, $tuple)) {
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
			$adapter->onMiddlewareUpdate($uuid, $data);
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
