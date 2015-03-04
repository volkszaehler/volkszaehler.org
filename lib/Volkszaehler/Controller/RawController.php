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
use Volkszaehler\Interpreter\RawInterpreter;

/**
 * Raw data controller
 * Allow read-only access to raw database values
 *
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @package default
 */
class RawController extends Controller {

	protected $ec;	// EntityController instance

	public function __construct(Request $request, EntityManager $em) {
		parent::__construct($request, $em);

		$this->ec = new EntityController($this->request, $this->em);
	}

	/**
	 * Query for data by given channel or group or multiple channels
	 *
	 * @param Model\Entity $entity - can be null
	 */
	public function get($entity) {
		$from = $this->request->parameters->get('from');
		$to = $this->request->parameters->get('to');

		if ($this->request->parameters->has('tuples')) {
			throw new \Exception('Invalid argument tuples');
		}
		if ($this->request->parameters->has('group')) {
			throw new \Exception('Invalid argument group');
		}

		// single entity
		if ($entity) {
			return new RawInterpreter($entity, $this->em, $from, $to);
		}

		// multiple UUIDs
		if ($uuids = self::makeArray($this->request->parameters->get('uuid'))) {
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
	 * @param Model\Channel $channel
	 */
	public function add($channel) {
		throw new \Exception('Invalid operation');
	}

	/**
	 * Run operation
	 */
	public function run($operation, $uuid = null) {
		$entity = isset($uuid) ? $this->ec->getSingleEntity($uuid, true) : null; // from cache if GET
		return $this->{$operation}($entity);
	}
}

?>
