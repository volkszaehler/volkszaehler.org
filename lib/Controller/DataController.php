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
 */
class DataController extends Controller {

	const OPT_SKIP_DUPLICATES = 'skipduplicates';
	const REGEX_FLOAT = '[-+]?[0-9]*\.?[0-9]+';

	protected $options;	// optional request parameters

	public function __construct(Request $request, EntityManager $em, View $view) {
		parent::__construct($request, $em, $view);
		$this->options = (array) strtolower($this->getParameters()->get('options'));
	}

	/**
	 * Query for data by given channel or group or multiple channels
	 *
	 * @param string|array uuid
	 * @return array
	 */
	public function get($uuid) {
		// single UUID
		if (is_string($uuid)) {
			$from = $this->getParameters()->get('from');
			$to = $this->getParameters()->get('to');
			$tuples = $this->getParameters()->get('tuples');
			$groupBy = $this->getParameters()->get('group');

			$entity = $this->ef->get($uuid, true);
			$class = $entity->getDefinition()->getInterpreter();
			$interpreter = new $class($entity, $this->em, $from, $to, $tuples, $groupBy, $this->options);

			// parse value filters
			if ($filters = $this->parseValueParamFilter('value')) {
				$interpreter->setValueFilter($filters);
			}

			return $interpreter;
		}

		// multiple UUIDs
		return array_map(function($uuid) {
			return $this->get($uuid);
		}, (array) $uuid);
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
		$channel = $this->ef->get($uuid, true);

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

		// parse value filters
		$filters = [];
		if ($values = (array) $this->getParameters()->get('value')) {
			$filters = $this->parseValueParamFilter($values);
		}

		$rows = 0;

		foreach ((array) $uuids as $uuid) {
			$channel = $this->ef->get($uuid, true);
			$rows += $channel->clearData($this->em->getConnection(), $from, $to, $filters);
		}

		return array('rows' => $rows);
	}


	/**
	 * Parse query parameters into SQL filters
	 */
	private function parseValueParamFilter($param) {
		// array of [operator,value]
		$result = [];

		// array syntax (value[]=ge0&value[]=lt1) or quality (value=0)
		$re = sprintf('/^(lt|gt|le|ge)?(%s)$/', self::REGEX_FLOAT);
		$ops = ['' => '=', 'lt' => '<', 'gt' => '>', 'le' => '<=', 'ge' => '>='];

		foreach ((array) $this->getParameters()->get($param) as $filter) {
			if (!preg_match($re, $filter, $matches)) {
				throw new \Exception('Invalid filter value ' . $filter);
			}

			$result[$ops[$matches[1]]] = (float)$matches[2];
		}

		// value syntax (value>=0&value<1)
		$re = sprintf('/%s([<>])(%s)?$/', $param, self::REGEX_FLOAT);

		foreach ($this->getParameters()->keys() as $key) {
			if (preg_match($re, $key, $matches)) {
				if (isset($matches[2])) {
					// no = sign included in operator
					$result[$matches[1]] = (float)$matches[2];
				}
				else {
					// = sign included in operator
					$result[$matches[1] . '='] = (float)$this->getParameters()->get($key);
				}
			}
		}

		return $result;
	}
}

?>
