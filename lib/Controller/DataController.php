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
use Doctrine\ORM\ORMException;

use Volkszaehler\Model;
use Volkszaehler\Util;
use Volkszaehler\Interpreter\Interpreter;
use Volkszaehler\View\View;

/**
 * Data controller
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class DataController extends Controller {

	const OPT_SKIP_DUPLICATES = 'skipduplicates';

	protected $options;	// optional request parameters

	public function __construct(Request $request, EntityManager $em, View $view) {
		parent::__construct($request, $em, $view);

		$this->options = self::makeArray(strtolower($this->getParameters()->get('options')));
	}

	/**
	 * Return a single entity by name
	 * @param $name
	 * @throws ORMException on empty or multiple results
	 * @return mixed
	 */
	protected function getSingleEntityByName($name) {
		$dql = 'SELECT a, p
			FROM Volkszaehler\Model\Entity a
			LEFT JOIN a.properties p
			WHERE p.key = :key
			AND p.value = :name';

		$q = $this->em->createQuery($dql)
			->setParameter('key', 'title')
			->setParameter('name', $name);

		$entity = $q->getSingleResult();
		return $entity;
	}

	/**
	 * Query for data by given channel or group or multiple channels
	 *
	 * @param string|array uuid
	 * @return array
	 */
	public function get($uuid) {
		$from = $this->getParameters()->get('from');
		$to = $this->getParameters()->get('to');
		$tuples = $this->getParameters()->get('tuples');
		$groupBy = $this->getParameters()->get('group');

		// single UUID
		if (is_string($uuid)) {
			if (!Util\UUID::validate($uuid)) {
				// allow retrieving entity by name
				try {
					$entity = $this->getSingleEntityByName($uuid);
					$uuid = $entity->getUuid();
				}
				catch (ORMException $e) {
					throw new \Exception('Channel \'' . $uuid . '\' does not exist or is not unique.');
				}
			}

			$entity = EntityController::factory($this->em, $uuid, true); // from cache
			$class = $entity->getDefinition()->getInterpreter();
			return new $class($entity, $this->em, $from, $to, $tuples, $groupBy, $this->options);
		}

		// multiple UUIDs
		return array_map(function($uuid) {
			return $this->get($uuid);
		}, self::makeArray($uuid));
	}

	/**
	 * Add single or multiple tuples
	 *
	 * @todo deduplicate Model\Channel code
	 * @param string|array uuid
	 * @return array
	 * @throws \Exception
	 */
	public function add($uuid) {
		$channel = EntityController::factory($this->em, $uuid, true);

		if (!$channel instanceof Model\Channel) {
			throw new \Exception('Adding data is only supported for channels');
		}

		try { /* to parse new submission protocol */
			$rawPost = $this->request->getContent(); // file_get_contents('php://input')

			// check maximum size allowed
			if ($maxSize = Util\Configuration::read('security.maxbodysize')) {
				if (strlen($rawPost) > $maxSize) {
					throw new \Exception('Maximum message size exceeded');
				}
			}

			$json = Util\JSON::decode($rawPost);

			if (isset($json['data'])) {
				throw new \Exception('Can only add data for a single channel at a time'); /* backed out b111cfa2 */
			}

			// convert nested ArrayObject to plain array with flattened tuples
			$data = array_reduce($json, function($carry, $tuple) {
				return array_merge($carry, $tuple);
			}, array());
		}
		catch (\RuntimeException $e) { /* fallback to old method */
			$timestamp = $this->getParameters()->get('ts');
			$value = $this->getParameters()->get('value');

			if (is_null($timestamp)) {
				$timestamp = (double) round(microtime(TRUE) * 1000);
			}
			else {
				$timestamp = Interpreter::parseDateTimeString($timestamp);
			}

			if (is_null($value)) {
				$value = 1;
			}

			// same structure as JSON request result
			$data = array($timestamp, $value);
		}

		$sql = 'INSERT ' . ((in_array(self::OPT_SKIP_DUPLICATES, $this->options)) ? 'IGNORE ' : '') .
			   'INTO data (channel_id, timestamp, value) ' .
			   'VALUES ' . implode(', ', array_fill(0, count($data)>>1, '(' . $channel->getId() . ',?,?)'));

		$rows = $this->em->getConnection()->executeUpdate($sql, $data);
		return array('rows' => $rows);
	}

	/**
	 * Delete tuples from single or multiple channels
	 *
	 * @todo deduplicate Model\Channel code
	 * @param string|array $uuids
	 * @return array
	 * @throws \Exception
	 */
	public function delete($uuids) {
		$from = null;
		$to = null;

		// parse interval
		if (null !== ($from = $this->getParameters()->get('from'))) {
			$from = Interpreter::parseDateTimeString($from);

			if (null !== ($to = $this->getParameters()->get('to'))) {
				$to = Interpreter::parseDateTimeString($to);

				if ($from > $to) {
					throw new \Exception('From is larger than to');
				}
			}
		}
		elseif ($from = $this->getParameters()->get('ts')) {
			$to = $from;
		}
		else {
			throw new \Exception('Missing timestamp (ts, from, to)');
		}

		$rows = 0;

		foreach (self::makeArray($uuids) as $uuid) {
			$channel = EntityController::factory($this->em, $uuid, true);
			$rows += $channel->clearData($this->em->getConnection(), $from, $to);
		}

		return array('rows' => $rows);
	}
}

?>
