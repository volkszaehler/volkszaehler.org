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

use Symfony\Component\HttpFoundation\Request;
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
	protected $cache;

	public function __construct(Request $request, EntityManager $em) {
		parent::__construct($request, $em);
		$this->cache = $this->em->getConfiguration()->getQueryCacheImpl();
	}

	/**
	 * Get entity
	 *
	 * @param string $identifier
	 */
	public function get($uuids) {
		if (is_string($uuids)) { // single entity
			return $this->getSingleEntity($uuids);
		}
		elseif (is_array($uuids)) { // multiple entities
			$entities = array();
			$allowInvalidUuid = $this->request->query->get('nostrict');

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

	public function getSingleEntity($uuid, $allowCache = false) {
		if (!Util\UUID::validate($uuid)) {
			throw new \Exception('Invalid UUID: \'' . $uuid . '\'');
		}

		if ($allowCache && $this->cache && $this->cache->contains($uuid)) {
			// used hydrated cache result
			return $this->cache->fetch($uuid);
		}

		$dql = 'SELECT a, p
			FROM Volkszaehler\Model\Entity a
			LEFT JOIN a.properties p
			WHERE a.uuid = :uuid';

		$q = $this->em->createQuery($dql)
			->setParameter('uuid', $uuid);

		try {
			$entity = $q->getSingleResult();

			if ($allowCache && $this->cache) {
				$this->cache->save($uuid, $entity, Util\Configuration::read('cache.ttl'));
			}

			return $entity;
		} catch (\Doctrine\ORM\NoResultException $e) {
			throw new \Exception('No entity found with UUID: \'' . $uuid . '\'', 404);
		}
	}

	/**
	 * Delete entity by uuid
	 */
	public function delete($identifier) {
		$entity = $this->get($identifier);

		if ($entity instanceof Model\Channel) {
			$entity->clearData($this->em->getConnection());
		}

		$this->em->remove($entity);
		$this->em->flush();

		if ($this->cache) {
			$this->cache->delete($identifier);
		}
	}

	/**
	 * Edit entity properties
	 */
	public function edit($identifier) {
		$entity = $this->get($identifier);

		$this->setProperties($entity, $this->request->query->all());
		$this->em->flush();

		if ($this->cache) {
			$this->cache->delete($identifier);
		}

		// HACK - see https://github.com/doctrine/doctrine2/pull/382
		$entity->castProperties();

		return $entity;
	}

	/**
	 * Update/set/delete properties of entities
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
	 * Filter entites by properties
	 *
	 * @todo improve performance
	 * @param array of property => value filters
	 * @return array of entities
	 */
	public function filter(array $properties) {
		$dql = 'SELECT a, p
			FROM Volkszaehler\Model\Entity a
			LEFT JOIN a.properties p';

		$i = 0;
		$sqlWhere = array();
		$sqlParams = array();
		foreach ($properties as $key => $value) {
			switch (Definition\PropertyDefinition::get($key)->getType()) {
				case 'string':
				case 'text':
				case 'multiple':
					$value = "'" . $value . "'";
					break;

				case 'boolean':
					$value = (int) $value;
			}
			$sqlWhere[] = 'EXISTS (SELECT p' . $i . ' FROM \Volkszaehler\Model\Property p' . $i . ' WHERE p' . $i . '.key = :key' . $i . ' AND p' . $i . '.value = :value' . $i . ' AND p' . $i . '.entity = a)';
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
