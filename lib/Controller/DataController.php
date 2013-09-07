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
use Volkszaehler\Util;

/**
 * Data controller
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class DataController extends Controller {

	/**
	 * Query for data by given channel or group or multiple channels
	 *
	 * @param Model\Entity $entity - can be null
	 */
	public function get($entity) {
		$from = $this->view->request->getParameter('from');
		$to = $this->view->request->getParameter('to');
		$tuples = $this->view->request->getParameter('tuples');
		$groupBy = $this->view->request->getParameter('group');
		$tsFmt = $this->view->request->getParameter('tsfmt');
		$client = strtolower($this->view->request->getParameter('client'));

		// single entity
		if ($entity) {
			$class = $entity->getDefinition()->getInterpreter();
			return new $class($entity, $this->em, $from, $to, $tuples, $groupBy, $client);
		}

		// multiple UUIDs
		if ($uuids = self::makeArray($this->view->request->getParameter('uuid'))) {
			$ec = new EntityController($this->view, $this->em);

			$interpreters = array();
			foreach ($uuids as $uuid) {
				$entity = $ec->get($uuid);
				$interpreters[] = $this->get($entity);
			}

			return $interpreters;
		}
	}

	/**
	 * Sporadic test/demo implemenation
	 *
	 * @todo replace by pluggable api parser
	 * @param Model\Channel $channel - can be null
	 */
	public function add($channel) {
		try { /* to parse new submission protocol */
			$rawPost = file_get_contents('php://input');
			$json = Util\JSON::decode($rawPost);

			// multiple tuples - bundle in transaction
			$this->em->getConnection()->beginTransaction(); // suspend auto-commit
			try {
				if ($json['data']) {
					// multiple channels
					$ec = new EntityController($this->view, $this->em);

					foreach ($json['data'] as $data) {
						$uuid = $data->uuid;
						$channel = $ec->get($uuid);

						foreach ($data->tuples as $tuple) {
							$channel->addData(new Model\Data($channel, (double) round($tuple[0]), $tuple[1]));
						}
					}
				}
				else {
					// single channel
					foreach ($json as $tuple) {
						$channel->addData(new Model\Data($channel, (double) round($tuple[0]), $tuple[1]));
					}
				}
				$this->em->flush();
				$this->em->getConnection()->commit();
			}
			catch (Exception $e) {
				$this->em->getConnection()->rollback(); 
				throw($e);
			}
		} catch (Util\JSONException $e) { /* fallback to old method */
			$timestamp = $this->view->request->getParameter('ts');
			$value = $this->view->request->getParameter('value');

			if (is_null($timestamp)) {
				$timestamp = (double) round(microtime(TRUE) * 1000);
			}
			
			if (is_null($value)) {
				$value = 1;
			}
		
			$channel->addData(new Model\Data($channel, $timestamp, $value));
			$this->em->flush();
		}
	}

	/**
	 * Run operation
	 */
	public function run($operation, array $identifiers = array()) {
		$ec = new EntityController($this->view, $this->em);

		$entity = isset($identifiers[0]) ? $ec->get($identifiers[0]) : null;
		return $this->{$operation}($entity);
	}
}

?>
