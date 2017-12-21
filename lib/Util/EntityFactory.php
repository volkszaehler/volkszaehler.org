<?php
/**
 * @copyright Copyright (c) 2011-2017, The volkszaehler.org project
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

namespace Volkszaehler\Util;

use Doctrine\ORM;
use Doctrine\Common\Cache;

use Volkszaehler\Util;
use Volkszaehler\Definition\PropertyDefinition;
use Volkszaehler\Model\Aggregator;

/**
 * Entity factory
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 */
class EntityFactory {

	/**
	 * Factory instance
	 */
	protected static $instance;

	/**
	 * @var Doctrine\ORM\EntityManager
	 */
	protected $em;

	/**
	 * Memory cache instance, e.g. APC
	 */
	protected $cache;

	/**
	 * Cache ttl
	 */
	protected $ttl;

	/**
	 * Create singleton instance
	 */
	public static function getInstance(ORM\EntityManager $em) {
		if (!isset(self::$instance)) {
			self::$instance = new EntityFactory($em);
		}
		return self::$instance;
	}

	/**
	 * Reset singleton instance
	 */
	public static function reset() {
		self::$instance = null;
	}

	/**
	 * Instance
	 */
	protected function __construct(ORM\EntityManager $em) {
		$this->em = $em;
		$this->cache = $em->getConfiguration()->getQueryCacheImpl();

		if (!isset($this->cache)) {
			$this->cache = new Cache\ArrayCache();
		}
	}

	/**
	 * Return a single entity by identifier, either UUID or name
	 * @param $uuid
	 * @param bool $cache Use cache
	 * @throws Exception on empty result
	 * @return Entity
	 */
	public function get($uuid, $cache = false) {
		if (empty($uuid)) {
			throw new \Exception('Missing UUID');
		}
		if (UUID::validate($uuid)) {
			return $this->getByUuidUnvalidated($uuid, $cache);
		}
		return $this->getByName($uuid, $cache);
	}

	/**
	 * Return a single entity by UUID, potentially from cache
	 *
	 * @param $uuid
	 * @param bool $cache Use cache
	 * @throws Exception on empty result
	 * @return Entity
	 */
	public function getByUuid($uuid, $cache = false) {
		if (!UUID::validate($uuid)) {
			throw new \Exception('Invalid UUID: \'' . $uuid . '\'');
		}
		return $this->getByUuidUnvalidated($uuid, $cache);
	}

	/**
	 * Return a single entity by UUID, potentially from cache.
	 * Does not validate UUID.
	 *
	 * @param $uuid
	 * @param bool $cache Use cache
	 * @throws Exception on empty result
	 * @return Entity
	 */
	protected function getByUuidUnvalidated($uuid, $cache = false) {
		return $this->cached($uuid, $cache, function() use ($uuid) {
			$dql = 'SELECT e, p
				FROM Volkszaehler\Model\Entity e
				LEFT JOIN e.properties p
				WHERE e.uuid = :uuid';

			$q = $this->em->createQuery($dql)->setParameter('uuid', $uuid);

			try {
				$entity = $q->getSingleResult();
				return $entity;
			}
			catch (ORM\NoResultException $e) {
				throw new \Exception('No entity with UUID \'' . $uuid . '\'');
			}
		});
	}

	/**
	 * Return a single public entity by name, potentially from cache
	 *
	 * @param $name
	 * @param bool $cache Use cache
	 * @throws Exception on empty or multiple results
	 * @return Entity
	 */
	public function getByName($name, $cache = false) {
		return $this->cached($name, $cache, function() use ($name) {
			$entities = $this->getByProperties(array(
				'title' => $name,
				'public' => 1
			));

			if (count($entities) != 1) {
				throw new \Exception('Invalid UUID ' . $name);
			}

			return array_shift($entities);
		});
	}

	/**
	 * Return multiple entities by properties
	 *
	 * @param array of property => value filters
	 * @return array of entities
	 */
	public function getByProperties(array $properties) {
		$dql = 'SELECT e, p
			FROM Volkszaehler\Model\Entity e
			LEFT JOIN e.properties p';

		$i = 0;
		$where = array();
		$params = array();
		foreach ($properties as $key => $value) {
			if (PropertyDefinition::get($key)->getType() == 'boolean') {
				$value = (int) $value;
			}

			$dql .= ' JOIN e.properties ' . $key;
			$where[] = $key.'.key = :key'.$i .' AND '. $key.'.value = :value'.$i;
			$params += array('key'.$i => $key, 'value'.$i => $value);

			$i++;
		}

		if (count($where) > 0) {
			$dql .= ' WHERE ' . implode(' AND ', $where);
		}

		$q = $this->em->createQuery($dql);
		return $q->execute($params);
	}

	/**
	 * Remove an entity by key from cache
	 *
	 * @param $key
	 */
	public function remove($key) {
		if (isset($key)) {
			$this->cache->delete($key);
		}
	}

	/**
	 * Wrap an EntityManager operation with cache read/write
	 *
	 * @throws Exception
	 */
	private function cached($key, $cache, $callable) {
		if ($cache && $this->cache->contains($key) && !Util\Configuration::read('devmode')) {
			$entity = $this->cache->fetch($key);

			if (!$entity instanceof Aggregator) {
				return $entity;
			}
		}

		$entity = $callable();

		if ($cache && isset($entity) &! $entity instanceof Aggregator) {
			if (!isset($this->ttl)) {
				$this->ttl = Util\Configuration::read('cache.ttl', 3600);
			}
			$this->cache->save($key, $entity, $this->ttl);
		}

		return $entity;
	}
}

?>
