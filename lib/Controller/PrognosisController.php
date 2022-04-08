<?php
/**
 * @author Andreas Goetz <cpuidle@gmx.de>
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
 * Prognosis controller
 *
 * @author Andreas Goetz <cpuidle@gmx.de>
 */
class PrognosisController extends DataController {

	/**
	 * Create and populate an interpreter
	 *
	 * @param string $uuid
	 * @param string|int $from
	 * @param string|int $to
	 * @param string|null $groupBy
	 */
	private function populate($uuid, $from, $to, $groupBy = null) {
		$entity = $this->ef->get($uuid, true); // from cache
		$class = $entity->getDefinition()->getInterpreter();
		$interpreter = new $class($entity, $this->em, $from, $to, null, $groupBy, $this->options);

		foreach ($interpreter as $tuple) {
			// loop through iterator tuples to retrieve data
		}

		return $interpreter;
	}

	/**
	 * Query for data by given channel or group or multiple channels
	 *
	 * @param string|array $uuid
	 * @return array
	 */
	public function get($uuid) {
		$period = $this->getParameters()->get('period');
		$groupBy = $this->getParameters()->get('group');
		$now = $this->getParameters()->get('now');

		// specifing "now" can be used for testing purposes to give refence
		$partial = $now ? $now : 'now';
		$format = 'd.m.Y';

		switch ($period) {
			case 'year':
				$from = 'first day of january this year';
				$to = 'last day of december this year';
				break;
			case 'month':
				$from = 'first day of this month';
				$to = 'last day of this month';
				break;
			case 'day':
				$from = 'today';
				$to = 'tomorrow';
				$format = 'd.m.Y H:i:s';
				break;
			default:
        		throw new \Exception('Unsupported period: \'' . $period . '\'');
		}

		// calculate reference period
		foreach (array('from', 'partial', 'to') as $var) {
			$reference_var = 'reference_' . $var;
			$$reference_var = $$var . ' -1 ' . $period;
		}

		// iterate through interpreters
		$current = $this->populate($uuid, $from, $partial, $groupBy);
		$ref_partial = $this->populate($uuid, $reference_from, $reference_partial, $groupBy);
		$ref_period = $this->populate($uuid, $reference_from, $reference_to, $groupBy);

		// get consumption values
		$current_consumption = $current->getConsumption();
		$ref_partial_consumption = $ref_partial->getConsumption();
		$ref_period_consumption = $ref_period->getConsumption();

		// actual forecast/prognosis
		if ($ref_partial_consumption) {
			$factor = $current_consumption / $ref_partial_consumption;
			$prognosis = $ref_period_consumption * $factor;
		}
		else {
			$factor = null;
			$prognosis = null;
		}

		// result
		return array(
			'prognosis' => array(
				'from' => date($format, strtotime($from)),
				'to' => date($format, strtotime($to)),
				'consumption' => $prognosis,
				'factor' => $factor,
			),
			'periods' => array(
				'current' => array(
					'from' => date($format, strtotime($from)),
					'partial' => date($format, strtotime($partial)),
					'partial_consumption' => $current_consumption
				),
				'reference' => array(
					'from' => date($format, strtotime($reference_from)),
					'partial' => date($format, strtotime($reference_partial)),
					'to' => date($format, strtotime($reference_to)),
					'partial_consumption' => $ref_partial_consumption,
					'consumption' => $ref_period_consumption,
				),
			)
		);
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
