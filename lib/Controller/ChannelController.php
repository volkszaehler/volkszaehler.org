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

use Volkszaehler\Definition;
use Volkszaehler\Model;
use Volkszaehler\Util;

/**
 * Channel controller
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class ChannelController extends EntityController {
	/**
	 * Get channel
	 */
	public function get($identifier = NULL) {
		$channel = parent::get($identifier);
	
		if (is_array($channel)) { // filter public entities
			return array('channels' => array_values(array_filter($channel['entities'], function($ch) {
				return ($ch instanceof Model\Channel);
			})));
		}
		else if ($channel instanceof Model\Channel) {
			return $channel;
		}
		else {
			throw new \Exception('Entity is not a channel: \'' . $identifier . '\'');
		}
	}

	/**
	 * Add channel
	 */
	public function add() {
		$type = $this->view->request->getParameter('type');

		if (!isset($type)) {
			throw new \Exception('Missing entity type');
		}
	
		$channel = new Model\Channel($type);
		$parameters = array_merge(
			$this->view->request->getParameters('post'),
			$this->view->request->getParameters('get')
		);
		
		foreach ($parameters as $key => $value)
			if ($value == '')
				unset($parameters[$key]);
		
		$this->setProperties($channel, $parameters);
		$this->em->persist($channel);
		$this->em->flush();

		return $channel;
	}
}

?>
