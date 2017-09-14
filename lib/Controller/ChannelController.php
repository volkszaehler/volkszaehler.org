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

namespace Volkszaehler\Controller;

use Volkszaehler\Model;

/**
 * Channel controller
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class ChannelController extends EntityController {

	/**
	 * Get one or more channels.
	 * If uuid is empty, list of public channels is returned.
	 *
	 * @param $identifier
	 * @return array
	 * @throws \Exception
	 */
	public function get($uuid = NULL) {
		$channel = parent::get($uuid);

		if (is_array($channel)) { // filter channels
			return array('channels' => array_values(
				array_filter($channel['entities'], function($ch) {
					return ($ch instanceof Model\Channel);
				})
			));
		}
		else if ($channel instanceof Model\Channel) {
			return $channel;
		}
		else {
			throw new \Exception('Entity is not a channel: \'' . $uuid . '\'');
		}
	}

	/**
	 * Add channel
	 */
	public function add() {
		$type = $this->getParameters()->get('type');

		if (!isset($type)) {
			throw new \Exception('Missing entity type');
		}

		$channel = new Model\Channel($type);
		$this->setProperties($channel, $this->getParameters()->all());

		$this->em->persist($channel);
		$this->em->flush();

		return $channel;
	}
}

?>
