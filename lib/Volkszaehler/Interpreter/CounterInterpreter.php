<?php
/**
 * @copyright Copyright (c) 2012, The volkszaehler.org project
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

namespace Volkszaehler\Interpreter;

/**
 * Counter interpreter
 *
 * @package default
 * @author Jakob Hirsch (jh.vz@plonk.de)
 *
 */
use Volkszaehler;
use Volkszaehler\Util;

class CounterInterpreter extends Interpreter {

	protected $valsum;
	protected $resolution;

	/**
	 * Calculates the consumption
	 *
	 * @return float total consumption in Wh
	 */
	public function getConsumption() {
		return $this->channel->getDefinition()->hasConsumption ? $this->valsum * $this->scale / $this->resolution : NULL;
	}

	/**
	 * Get Average
	 *
	 * @return float average in W
	 */
	public function getAverage() {
		if ($this->valsum) {
			$delta = $this->getTo() - $this->getFrom();
			// 60 s/min * 60 min/h * 1.000 ms/s * 1.000 W/kW = 3.6e9 (Units: s/h*ms/s*W/kW = s/3.600s*.001s/s*W/1.000W = 1)
			return (3.6e6 * $this->scale * $this->valsum) / ($this->resolution * $delta);
		}
		else { // prevents division by zero
			return 0;
		}
	}

	/**
	 * Raw counter value to power conversion
	 *
	 * @param $callback a callback called each iteration for output
	 * @return array with arrays of timestamp, energy and value count
	 */
	public function processData($callback) {
		$tuples = array();
		$this->rows = $this->getData();

		$this->resolution = $this->channel->getProperty('resolution');
		$this->valsum = 0;

		// get starting value from skipped first row
		if ($this->rowCount > 0) {
			$last_ts = $this->getFrom();
			$last_val = $this->rows->firstValue();
		}

		foreach ($this->rows as $row) {
			$delta_ts = $row[0] - $last_ts; // time between now and row before

			// instead of reverting what DataIterator->next did by $val = $row[1] / $row[2]
			// get max value which DataIterator->next provides as courtesy
			$delta_val = $row[3] - $last_val;

			// (1 imp / 1 imp/kWh) * (60 min/h * 60 s/min * 1000 ms/s * scale) / 1 ms
			$tuple = $callback(array(
				(float) ($last_ts = $row[0]), // timestamp of interval end
				(float) ($delta_val * 3.6e6 * $this->scale) / ($delta_ts * $this->resolution), // doing df/dt
				(int) $row[2] // num of rows
			));
			$last_val = $row[3];

			if (is_null($this->max) || $tuple[1] > $this->max[1]) {
				$this->max = $tuple;
			}

			if (is_null($this->min) || $tuple[1] < $this->min[1]) {
				$this->min = $tuple;
			}

			$this->valsum += $delta_val;

			$tuples[] = $tuple;
		}

		return $tuples;
	}

	/**
	 * Return sql grouping expression
	 *
	 * Override Interpreter->groupExpr
	 *
	 * For precision when bundling tuples into packages
	 * CounterInterpreter needs MAX instead of SUM.
	 *
	 * @author Andreas Götz <cpuidle@gmx.de>
	 * @param string $expression sql parameter
	 * @return string grouped sql expression
	 */
	public static function groupExprSQL($expression) {
		return 'MAX(' . $expression . ')';
	}
}

?>
