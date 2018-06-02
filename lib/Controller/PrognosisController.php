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

/**
 * Prognose controller
 *
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @package default
 */
class PrognosisController extends Controller {

	/**
	 * Create and populate an interpreter
	 */
	private function populate($uuid, $from, $to, $tuples = null, $groupBy = null) {
		$entity = $this->ef->get($uuid, true);
		$class = $entity->getDefinition()->getInterpreter();
		$interpreter = new $class($entity, $this->em, $from, $to, $tuples, $groupBy);

		foreach ($interpreter as $tuple) {
			// loop through iterator tuples
		}

		return $interpreter;
	}

	/**
	 * Query for data by given channel or group or multiple channels
	 *
	 * @param string|array uuid
	 * @return array
	 */
	public function get($uuid) {
		$period = $this->getParameters()->get('period');
		$groupBy = $this->getParameters()->get('group');

		$entity = $this->ef->get($uuid, true);

		$partial = 'now';
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
		}

		// calculate reference period
		foreach (array('from', 'partial', 'to') as $var) {
			$reference_var = 'reference_' . $var;
			$$reference_var = $$var . ' -1 ' . $period;
		}

		// iterate through interpreters
		$current = $this->populate($uuid, $from, $partial, null, $groupBy);
		$ref_partial = $this->populate($uuid, $reference_from, $reference_partial, null, $groupBy);
		$ref_period = $this->populate($uuid, $reference_from, $reference_to, null, $groupBy);

		// get consumption values
		$current_consumption = $current->getConsumption();
		$ref_partial_consumption = $ref_partial->getConsumption();
		$ref_period_consumption = $ref_period->getConsumption();

		// if reference period is empty
		$factor = $prognosis = 0;

		// otherwise actual forecast/prognosis
		if ($ref_partial_consumption) {
			$factor = $current_consumption / $ref_partial_consumption;
			$prognosis = $ref_period_consumption * $factor;
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
}

?>
