<?php
/**
 * @copyright Copyright (c) 2011-2019, The volkszaehler.org project
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
 * Prognose controller
 *
 * @author Andreas Goetz <cpuidle@gmx.de>
 */
class QueryController extends DataController {

	/**
	 * Create channel dynamically
	 */
	public static function channelFactory($type, $rule, $inputs) {
		$channel = new Model\Channel($type);
		$channel->setProperty('rule', $rule);

		foreach ($inputs as $key => $value) {
			if (is_numeric($key)) {
				$key = 'in' . ($key+1);
			}
			$channel->setProperty($key, $value);
		}

		return $channel;
	}

	/**
	 * Query for data by given channel or group or multiple channels
	 *
	 * @param string|array|null $uuid
	 * @return array
	 */
	public function get($uuid) {
		if (isset($uuid)) {
			throw new \Exception('Queries cannot be performed against UUIDs');
		}

		$from = $this->getParameters()->get('from');
		$to = $this->getParameters()->get('to');
		$tuples = $this->getParameters()->get('tuples');
		$groupBy = $this->getParameters()->get('group');

		// dynamic properties
		$type = $this->getParameters()->has('type') ? $this->getParameters()->get('type') : 'virtualsensor';
		$rule = $this->getParameters()->get('rule');

		$inputs = array();
		for ($i=1; $i<10; $i++) {
			$key = 'in' . $i;
			if ($this->getParameters()->has($key)) {
				$inputs[$key] = $this->getParameters()->get($key);
			}
		}

		$entity = self::channelFactory($type, $rule, $inputs);
		$class = $entity->getDefinition()->getInterpreter();
		return new $class($entity, $this->em, $from, $to, $tuples, $groupBy, $this->options);
	}

	/*
	 * Override inherited visibility
	 */

	public function add($uuid) {
		throw new \Exception('Invalid context operation: \'add\'');
	}

	public function delete($uuids) {
		throw new \Exception('Invalid context operation: \'delete\'');
	}
}

?>
