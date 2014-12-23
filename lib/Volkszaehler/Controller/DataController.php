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
use Volkszaehler\Interpreter\Interpreter;

/**
 * Data controller
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class DataController extends Controller {

	const OPT_SKIP_DUPLICATES = 'skipduplicates';

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
		$options = $this->view->request->getArrayParameter('options');

		// single entity
		if ($entity) {
			$class = $entity->getDefinition()->getInterpreter();
			return new $class($entity, $this->em, $from, $to, $tuples, $groupBy, $options);
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
	 * Add single or multiple tuples
	 *
	 * @todo replace by pluggable api parser
	 * @param Model\Channel $channel
	 */
	public function add($channel) {
		try { /* to parse new submission protocol */
			$rawPost = file_get_contents('php://input');
			$json = Util\JSON::decode($rawPost);

			// multiple tuples - bundle in single query
			if (isset($json['data']))
				throw new \Exception('Can only add data for a single channel at a time'); /* backed out b111cfa2 */

			// convert ArrayObject to native Array
			$json = $json->getArrayCopy();
			$options = $this->view->request->getArrayParameter('options');

			$sql =
				'INSERT ' . ((in_array(self::OPT_SKIP_DUPLICATES, $options)) ? 'IGNORE' : '') . ' INTO data (channel_id, timestamp, value) ' .
				'VALUES ' . implode(', ', array_fill(0, count($json), '(' . $channel->getId() . ',?,?)'));

			$params = array_reduce($json, function($carry, $tuple) {
				return array_merge($carry, $tuple);
			}, array());

			$rows = $this->em->getConnection()->executeUpdate($sql, $params);
			return(array('rows' => $rows));
		}
		catch (Util\JSONException $e) { /* fallback to old method */
			$timestamp = $this->view->request->getParameter('ts');
			$value = $this->view->request->getParameter('value');

			if (is_null($timestamp)) {
				$timestamp = (double) round(microtime(TRUE) * 1000);
			}
			else {
				$timestamp = Interpreter::parseDateTimeString($timestamp);
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
