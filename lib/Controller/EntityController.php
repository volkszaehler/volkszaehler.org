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

use Doctrine\ORM\EntityManager;

use Volkszaehler\Model;
use Volkszaehler\Util;
use Volkszaehler\Definition;

/**
 * Entity controller
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Götz <cpuidle@gmx.de>
 */
class EntityController extends Controller {

	/**
	 * Get one or more entities.
	 * If uuid is empty, list of public entities is returned.
	 *
	 * @param string|array|null $uuid
	 * @return array|Model\Entity
	 * @throws \Exception
	 */
	public function get($uuid) {
		if (is_string($uuid)) { // single entity
			return $this->ef->get($uuid, true);
		}

		if (is_array($uuid)) { // multiple entities
			$entities = array();
			$strict = !$this->getParameters()->get('nostrict');

			foreach ($uuid as $_uuid) {
				try {
					$entities[] = $this->ef->get($_uuid, true);
				}
				catch (\Exception $e) {
					if ($strict) {
						throw $e;
					}
				}
			}
		}
		else { // public entities
			$entities = $this->ef->getByProperties(array('public' => true));
		}

		return array('entities' => $entities);
	}

	/**
	 * Delete entity by uuid
	 * @param string $uuid
	 * @throws \Exception
	 */
	public function delete($uuid) {
		$entity = $this->ef->get($uuid);

		if ($entity instanceof Model\Channel) {
			/** @var Model\Channel */
			$entity->clearData($this->em->getConnection());
		}

		$this->em->remove($entity);
		$this->em->flush();
		$this->ef->remove($uuid);
	}

	/**
	 * Edit entity properties
	 * @param string|null $uuid
	 * @return Model\Entity
	 * @throws \Exception
	 */
	public function edit($uuid) {
		$entity = $this->ef->get($uuid);

		$this->setProperties($entity, $this->getParameters()->all());
		$this->em->flush();
		$this->ef->remove($uuid);

		// HACK - see https://github.com/doctrine/doctrine2/pull/382
		$entity->castProperties();

		return $entity;
	}

	/**
	 * Update/set/delete properties of entities
	 * @param Model\Entity $entity
	 * @param array $parameters
	 * @throws \Exception
	 */
	protected function setProperties(Model\Entity $entity, $parameters) {
		foreach ($parameters as $key => $value) {
			if (in_array($key, array('operation', 'type', 'debug'))) {
				continue; // skip generic parameters
			}
			else if (!Definition\PropertyDefinition::exists($key)) {
				throw new \Exception('Unknown property: \'' . $key . '\'');
			}

			if ($value == '') { // dont use empty() because it also matches 0
				$entity->deleteProperty($key);
			}
			else {
				$entity->setProperty($key, $value);
			}
		}

		$entity->checkProperties();
	}
}

?>
