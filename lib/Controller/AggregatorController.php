<?php
/**
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

namespace Volkszaehler\Controller;

use Volkszaehler\Model;

/**
 * Aggregator controller
 *
 * @author Steffen Vogel (info@steffenvogel.de)
 */
class AggregatorController extends EntityController {

	/**
	 * Get aggregator
	 * @param string|array|null $uuid
	 * @return array
	 * @throws \Exception
	 */
	public function get($uuid) {
		$aggregator = parent::get($uuid);

		if (is_array($aggregator)) { // filter public entities
			return array('channels' => array_values(
				array_filter($aggregator['entities'], function($agg) {
					return ($agg instanceof Model\Aggregator);
				})
			));
		}
		else if ($aggregator instanceof Model\Aggregator) {
			return $aggregator;
		}
		else {
			throw new \Exception('Entity is not a group: \'' . $uuid . '\'');
		}
	}

	/**
	 * Create new aggregator or add entity to aggregator
	 * @param string|null $uuid
	 * @return array|Model\Aggregator
	 * @throws \Exception
	 */
	public function add($uuid) {
		if (isset($uuid)) {	// add entity to aggregator
			if ($uuids = (array) $this->getParameters()->get('uuid')) {
				/** @var Model\Aggregator */
				$aggregator = $this->ef->get($uuid);
				foreach ($uuids as $uuid) {
					$aggregator->addChild($this->ef->get($uuid));
				}
			}
			else {
				throw new \Exception('Missing child UUID(s) to add');
			}
		}
		else {	// create new aggregator
			$type = $this->getParameters()->get('type', 'group');
			$aggregator = new Model\Aggregator($type);
			$this->setProperties($aggregator, $this->getParameters()->all());
			$this->em->persist($aggregator);
		}

		$this->em->flush();

		return $aggregator;
	}

	/**
	 * Delete Aggregator or remove entity from aggregator
	 * @param string|array|null $uuid
	 * @return Model\Aggregator|null
	 */
	public function delete($uuid) {
		if (!($entity = $this->ef->getByUuid($uuid)) instanceof Model\Entity) {
			throw new \Exception('Invalid operation - missing entity.');
		}

		$aggregator = null;
		if ($uuids = (array) $this->getParameters()->get('uuid')) { // remove entity from aggregator
			/** @var Model\Aggregator */
			$aggregator = $this->ef->getByUuid($uuid);
			foreach ($uuids as $uuid) {
				$aggregator->removeChild($this->ef->getByUuid($uuid));
			}

			$this->em->flush();
			$this->ef->remove($uuid);
		}
		else {	// remove aggregator
			parent::delete($uuid);
		}
		return $aggregator;
	}
}

?>
