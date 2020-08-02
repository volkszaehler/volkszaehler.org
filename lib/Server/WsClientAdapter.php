<?php
/**
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2020, The volkszaehler.org project
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
use Ratchet\MessageComponentInterface;

/**
 * Distribute push messages to plain web socket subscribers
 */
class WsClientAdapter implements MessageComponentInterface, PushTransportInterface {

	protected $subscribers;

	function __construct() {
        $this->subscribers = new \SplObjectStorage;
	}

	/*
	 * PushTransportInterface
	 */
	public function onUpdate($uuid, $content) {
		foreach($this->subscribers as $subscriber) {
			$subscriber->send(json_encode($content));
		}
	}

	/*
	 * MessageInterface
	 */

    function onMessage(ConnectionInterface $from, $msg) {
    	$from->close();
    }

	/*
	 * ComponentInterface
	 */

    function onOpen(ConnectionInterface $conn) {
    	$this->subscribers->attach($conn);
    }

    function onClose(ConnectionInterface $conn) {
    	$this->subscribers->detach($conn);
    }

    function onError(ConnectionInterface $conn, \Exception $e) {
    	$conn->close();
    }
}
