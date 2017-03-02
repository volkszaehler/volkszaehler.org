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

use Doctrine\ORM\EntityManager;

use Volkszaehler\Model;
use Volkszaehler\Util;
use Volkszaehler\Definition;

/**
 * Entity controller
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Götz <cpuidle@gmx.de>
 * @package default
 */
class EntityController extends Controller {

	/**
	 * Memory cache instance, e.g. APC
	 */
	protected static $cache;

	/**
	 * Get entity
	 *
	 * @param $uuids
	 * @return array
	 * @throws \Exception
	 */
	public function get($uuids) {
		if (is_string($uuids)) { // single entity
			return $this->getSingleEntity($uuids);
		}
		elseif (is_array($uuids)) { // multiple entities
			$entities = array();
			$allowInvalidUuid = $this->getParameters()->get('nostrict');

			foreach ($uuids as $uuid) {
				try {
					$entities[] = $this->getSingleEntity($uuid);
				}
				catch (\Exception $e) {
					if ($allowInvalidUuid) {
						// return empty entity
						$entities[] = array('uuid' => $uuid);
					}
					else {
						throw $e;
					}
				}
			}

			return array('entities' => $entities);
		}
		else { // public entities
			return array('entities' => $this->filter(array('public' => TRUE)));
		}
	}

	/**
	 * Return a single entity, potentially from cache
	 * @param $uuid
	 * @param bool $allowCache
	 * @return mixed
	 */
	public function getSingleEntity($uuid, $allowCache = false) {
		return self::factory($this->em, $uuid, $allowCache);
	}

	/**
	 * Static entity factory- usable for scripting
	 *
	 * @param EntityManager $em
	 * @param $uuid
	 * @param bool $allowCache
	 * @return mixed
	 * @throws \Exception
	 */
	static function factory(EntityManager $em, $uuid, $allowCache = false) {
		if (!Util\UUID::validate($uuid)) {
			throw new \Exception('Invalid UUID: \'' . $uuid . '\'');
		}

		if (!self::$cache) {
			self::$cache = $em->getConfiguration()->getQueryCacheImpl();
		}

		if ($allowCache && self::$cache && self::$cache->contains($uuid)) {
			// used hydrated cache result
			return self::$cache->fetch($uuid);
		}

		$dql = 'SELECT a, p
			FROM Volkszaehler\Model\Entity a
			LEFT JOIN a.properties p
			WHERE a.uuid = :uuid';

		$q = $em->createQuery($dql)
			->setParameter('uuid', $uuid);

		try {
			$entity = $q->getSingleResult();

			if ($allowCache && self::$cache) {
				self::$cache->save($uuid, $entity, Util\Configuration::read('cache.ttl', 3600));
			}

			return $entity;
		} catch (\Doctrine\ORM\NoResultException $e) {
			throw new \Exception('No entity found with UUID: \'' . $uuid . '\'');
		}
	}

	/**
	 * Delete entity by uuid
	 * @param $identifier
	 * @throws \Exception
	 */
	public function delete($identifier) {
		if (!($entity = $this->get($identifier)) instanceof Model\Entity) {
			throw new \Exception('Invalid operation - missing entity.');
		}

		if ($entity instanceof Model\Channel) {
			$entity->clearData($this->em->getConnection());
		}

		$this->em->remove($entity);
		$this->em->flush();

		if (self::$cache) {
			self::$cache->delete($identifier);
		}
	}

	/**
	 * Edit entity properties
	 * @param $identifier
	 * @return array
	 * @throws \Exception
	 */
	public function edit($identifier) {
		if (!($entity = $this->get($identifier)) instanceof Model\Entity) {
			throw new \Exception('Invalid operation - missing entity.');
		}

		$this->setProperties($entity, $this->getParameters()->all());
		$this->em->flush();

		if (self::$cache) {
			self::$cache->delete($identifier);
		}

		// HACK - see https://github.com/doctrine/doctrine2/pull/382
		$entity->castProperties();

		return $entity;
	}

	/**
	 * Update/set/delete properties of entities
	 * @param Model\Entity $entity
	 * @param $parameters
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

	/**
	 * Filter entities by properties
	 *
	 * @param array of property => value filters
	 * @return array of entities
	 */
	public function filter(array $properties) {
		$dql = 'SELECT e, p
			FROM Volkszaehler\Model\Entity e
			LEFT JOIN e.properties p';

		$i = 0;
		$sqlWhere = array();
		$sqlParams = array();
		foreach ($properties as $key => $value) {
			if (Definition\PropertyDefinition::get($key)->getType() == 'boolean') {
				$value = (int) $value;
			}
			$sqlWhere[] = 'EXISTS (SELECT p' . $i . ' FROM \Volkszaehler\Model\Property p' . $i . ' WHERE p' . $i . '.key = :key' . $i . ' AND p' . $i . '.value = :value' . $i . ' AND p' . $i . '.entity = e)';
			$sqlParams += array(
				'key' . $i => $key,
				'value' . $i => $value
			);
			$i++;
		}

		if (count($sqlWhere) > 0) {
			$dql .= ' WHERE ' . implode(' AND ', $sqlWhere);
		}

		$q = $this->em->createQuery($dql);
		return $q->execute($sqlParams);
	}
}

?>
