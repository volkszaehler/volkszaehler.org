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
 * @author Jakob Hirsch <jh.vz@plonk.de>
 * @author Andreas Götz <cpuidle@gmx.de>
 */

class CounterInterpreter extends Interpreter {

	protected $valsum;
	protected $ts_last; // previous tuple timestamp
	protected $last_val; // previous tuple value

	/**
	 * Initialize data iterator
	 */
	public function rewind() {
		$this->key = 0;
		$this->rows = $this->getData();
		$this->rows->rewind();

		$this->valsum = 0;

		// get starting value from skipped first row
		if ($this->rowCount > 0) {
			$this->last_ts = $this->getFrom();
			$this->last_val = $this->rows->firstValue();
		}
	}

	/**
	 * Iterate over result set
	 */
	public function current() {
		$row = $this->rows->current();

		// raw database values
		if ($this->raw) {
			return(array_slice($row, 0, 3));
		}

		$delta_ts = $row[0] - $this->last_ts; // time between now and row before

		// instead of reverting what DataIterator->next did by $val = $row[1] / $row[2]
		// get max value which DataIterator->next provides as courtesy
		$delta_val = $row[3] - $this->last_val;

		// (1 imp / 1 imp/kWh) * (60 min/h * 60 s/min * 1000 ms/s * scale) / 1 ms
		$tuple = array(
			(float) ($this->last_ts = $row[0]), // timestamp of interval end
			(float) ($delta_val * 3.6e6 * $this->scale) / ($delta_ts * $this->resolution), // doing df/dt
			(int) $row[2] // num of rows
		);
		$this->last_val = $row[3];

		if (is_null($this->max) || $tuple[1] > $this->max[1]) {
			$this->max = $tuple;
		}

		if (is_null($this->min) || $tuple[1] < $this->min[1]) {
			$this->min = $tuple;
		}

		$this->valsum += $delta_val;

		return $tuple;
	}

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
