<?php
/**
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
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

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

/**
 * Distribute push messages to WAMP subscribers
 */
class WampClientAdapter implements WampServerInterface, PushTransportInterface {

	/**
	 * @var lookup of all the topics clients have subscribed to
	 */
	protected $subscribedTopics = array();

	/**
	 * @var count of open connections
	 */
	protected $connections = 0;

	/*
	 * PushTransportInterface
	 */
	public function onUpdate($uuid, $content) {
		// broadcast if topic subscribed
		if ($topic = $this->getTopic($uuid)) {
			$topic->broadcast(json_encode($content));
		}
	}

	/*
	 * WampServerInterface
	 */
	public function onSubscribe(ConnectionInterface $conn, $topic) {
		if (null === $this->getTopic($uuid = $topic->getId())) {
			$this->addTopic($uuid, $topic);
		}
	}

	public function onUnSubscribe(ConnectionInterface $conn, $topic) {
		$this->cleanTopic($conn, $topic);
	}

	public function onCall(ConnectionInterface $conn, $id, $topic, array $params) {
		// In this application if clients send data it's because the user hacked around in console
		$conn->callError($id, $topic, 'Calls not supported')->close();
	}

	public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible) {
		// In this application if clients send data it's because the user hacked around in console
		$conn->close();
	}

	/*
	 * ComponentInterface
	 */

	public function onOpen(ConnectionInterface $conn) {
		$this->connections++;
	}

	public function onClose(ConnectionInterface $conn) {
		$this->connections--;

		foreach ($this->subscribedTopics as $topic) {
			$this->cleanTopic($conn, $topic);
		}
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

	protected function cleanTopic(ConnectionInterface $conn, $topic) {
		$topic->remove($conn);

		$uuid = $topic->getId();
		if ($topic->count() === 0 && isset($this->subscribedTopics[$uuid])) {
			unset($this->subscribedTopics[$uuid]);
		}
	}
}
