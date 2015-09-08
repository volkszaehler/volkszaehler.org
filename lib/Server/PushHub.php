<?php
/**
 * @copyright Copyright (c) 2015, The volkszaehler.org project
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

use Symfony\Component\HttpFoundation\Request;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

use Volkszaehler\Router;
use Volkszaehler\Interpreter\Interpreter;
use Volkszaehler\Controller\EntityController;

class PushHub implements WampServerInterface {
	/**
	 * @var lookup of all the topics clients have subscribed to
	 */
	protected $subscribedTopics = array();

	/**
	 * @var interpreters per subscribed topic
	 */
	protected $interpreters = array();

	protected $em;
	protected $controller;

	public function onSubscribe(ConnectionInterface $conn, $topic) {
		if (null === $this->getTopic($uuid = $topic->getId())) {
			$this->addTopic($uuid, $topic);
		}

		if (null === $this->getInterpreter($uuid)) {
			$this->addInterpreter($uuid, $this->connectToMiddleware($uuid));
		}
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
	 * @param string JSON'ified string we'll receive from publisher
	 */
	public function handleRequest($json) {
		$msg = json_decode($json, true);
		$response = array(
			'version' => VZ_VERSION,
			'data' => array()
		);

		// validate input message
		if (null === ($data = @$msg['data']) || !is_array($data)) {
			return;
		}

		// loop through channels
		foreach ($data as $channel) {
			if (null === ($uuid = @$channel['uuid'])) {
				return;
			}
			if (null === ($tuples = @$channel['tuples']) || !is_array($tuples) || !count($tuples)) {
				return;
			}

			// get interpreter if no client has connected yet
			if (null === ($interpreter = $this->getInterpreter($uuid))) {
				$this->addInterpreter($uuid, $interpreter = $this->connectToMiddleware($uuid));
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
			$response['data'][] = $channelData;

			// broadcast if transformed tuple valid and topic subscribed
			if (count($transformed) && ($topic = $this->getTopic($uuid))) {
				$topic->broadcast(json_encode(
					array(
						'version' => VZ_VERSION,
						'data' => $channelData
					)
				));
			}
		}

		return json_encode($response);
	}

	public function onUnSubscribe(ConnectionInterface $conn, $topic) {
		if ($topic->count() == 0) {
			$this->removeTopic($topic->getId());
		}
	}

	public function onOpen(ConnectionInterface $conn) {
	}

	public function onClose(ConnectionInterface $conn) {
	}

	public function onCall(ConnectionInterface $conn, $id, $topic, array $params) {
		// In this application if clients send data it's because the user hacked around in console
		$conn->callError($id, $topic, 'Calls not supported')->close();
	}

	public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible) {
		// In this application if clients send data it's because the user hacked around in console
		$conn->close();
	}

	public function onError(ConnectionInterface $conn, \Exception $e) {
		$conn->close();
	}


	/*
	 * Get/store subscription data
	 */

	protected function getTopic($uuid) {
		if (!isset($this->subscribedTopics[$uuid])) {
			return null;
		}
		return $this->subscribedTopics[$uuid];
	}

	protected function addTopic($uuid, $topic) {
		$this->subscribedTopics[$uuid] = $topic;
	}

	protected function removeTopic($uuid) {
		if (isset($this->subscribedTopics[$uuid])) {
			unset($this->subscribedTopics[$uuid]);
		}
	}

	protected function getInterpreter($uuid) {
		if (!isset($this->interpreters[$uuid])) {
			return null;
		}
		return $this->interpreters[$uuid];
	}

	protected function addInterpreter($uuid, $interpreter) {
		$this->interpreters[$uuid] = $interpreter;
	}
}
