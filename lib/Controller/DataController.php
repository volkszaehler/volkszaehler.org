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
use Volkszaehler\Interpreter\Interpreter;

/**
 * Data controller
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class DataController extends Controller {

	const OPT_SKIP_DUPLICATES = 'skipduplicates';

	protected $ec;	// EntityController instance
	protected $options;	// optional request parameters

	public function __construct(Request $request, EntityManager $em) {
		parent::__construct($request, $em);

		$this->options = self::makeArray(strtolower($this->request->query->get('options')));
		$this->ec = new EntityController($this->request, $this->em);
	}

	/**
	 * Query for data by given channel or group or multiple channels
	 *
	 * @param Model\Entity $entity - can be null
	 */
	public function get(Model\Channel $entity = null) {
		$from = $this->request->query->get('from');
		$to = $this->request->query->get('to');
		$tuples = $this->request->query->get('tuples');
		$groupBy = $this->request->query->get('group');

		// single entity interpreter
		if ($entity) {
			$class = $entity->getDefinition()->getInterpreter();
			return new $class($entity, $this->em, $from, $to, $tuples, $groupBy, $this->options);
		}

		// multiple UUIDs
		if ($uuids = self::makeArray($this->request->query->get('uuid'))) {
			$interpreters = array();

			foreach ($uuids as $uuid) {
				$entity = $this->ec->getSingleEntity($uuid, true); // from cache
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
	public function add(Model\Channel $channel) {
		try { /* to parse new submission protocol */
			$rawPost = $this->request->getContent(); // file_get_contents('php://input')
			$json = Util\JSON::decode($rawPost);

			if (isset($json['data'])) {
				throw new \Exception('Can only add data for a single channel at a time'); /* backed out b111cfa2 */
			}

			// convert nested ArrayObject to plain array with flattened tuples
			$data = array_reduce($json->getArrayCopy(), function($carry, $tuple) {
				return array_merge($carry, $tuple);
			}, array());
		}
		catch (Util\JSONException $e) { /* fallback to old method */
			$timestamp = $this->request->query->get('ts');
			$value = $this->request->query->get('value');

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
	 * Run operation
	 */
	public function run($operation, $uuid = null) {
		$entity = isset($uuid) ? $this->ec->getSingleEntity($uuid, true) : null; // from cache (GET requests only)
		return $this->{$operation}($entity);
	}
}

?>
