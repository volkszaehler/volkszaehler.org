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
use Volkszaehler\Definition;

/**
 * Aggregator controller
 *
 * @author Steffen Vogel (info@steffenvogel.de)
 * @package default
 */
class AggregatorController extends EntityController {
	/**
	 * Get aggregator
	 */
	public function get($identifier = NULL) {
		$aggregator = parent::get($identifier);

		if (is_array($aggregator)) { // filter public entities
			return array('channels' => array_values(array_filter($aggregator['entities'], function($agg) {
				return ($agg instanceof Model\Aggregator);
			})));
		}
		else if ($aggregator instanceof Model\Aggregator) {
			return $aggregator;
		}
		else {
			throw new \Exception('Entity is not a group: \'' . $identifier . '\'');
		}
	}

	/**
	 * Create new aggregator or add entity to aggregator
	 */
	public function add($identifier = NULL) {
		if (isset($identifier)) {	// add entity to aggregator
			$aggregator = $this->get($identifier);

			if ($uuids = self::makeArray($this->request->parameters->get('uuid'))) {
				$ec = new EntityController($this->request, $this->em);

				foreach ($uuids as $uuid) {
					$aggregator->addChild($ec->get($uuid));
				}
			}
			else {
				throw new \Exception('You have to specifiy a UUID to add');
			}
		}
		else {	// create new aggregator
			$type = $this->request->parameters->get('type');

			if (!isset($type)) {
				$type = 'group';
			}

			$aggregator = new Model\Aggregator($type);

			$this->setProperties($aggregator, $this->request->parameters->all());
			$this->em->persist($aggregator);
		}

		$this->em->flush();

		return $aggregator;
	}

	/**
	 * Delete Aggregator or remove entity from aggregator
	 */
	public function delete($identifier) {
		if (!isset($identifier))
			return;

		$aggregator = NULL;
		if ($uuids = self::makeArray($this->request->parameters->get('uuid'))) { // remove entity from aggregator
			$aggregator = $this->get($identifier);
			$ec = new EntityController($this->request, $this->em);

			foreach ($uuids as $uuid) {
				$aggregator->removeChild($ec->get($uuid));
			}

			$this->em->flush();
		}
		else {	// remove aggregator
			parent::delete($identifier);
		}
		return $aggregator;
	}
}

?>
