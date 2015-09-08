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

namespace Volkszaehler\Interpreter;

/**
 * Sensor interpreter
 *
 * @package default
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Götz <cpuidle@gmx.de>
 */

class SensorInterpreter extends Interpreter {

	protected $consumption; // in Wms (Watt milliseconds)
	protected $ts_last; // previous tuple timestamp

	/**
	 * Initialize data iterator
	 */
	public function rewind() {
		$this->key = 0;
		$this->rows = $this->getData();
		$this->rows->rewind();

		$this->ts_last = $this->getFrom();
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

		$delta_ts = $row[0] - $this->ts_last;
		$tuple = $this->convertRawTuple($row);
		$this->consumption += $tuple[1] * $delta_ts;

		if (is_null($this->max) || $tuple[1] > $this->max[1]) {
			$this->max = $tuple;
		}

		if (is_null($this->min) || $tuple[1] < $this->min[1]) {
			$this->min = $tuple;
		}

		return $tuple;
	}

	/**
	 * Convert raw meter readings
	 */
	public function convertRawTuple($row) {
		// instead of using $row[1], which is value, get weighed average value from $row[4] which
		// DataIterator->next provides as courtesy
		// otherwise the default, non-optimized tuple packaging SQL statement will yield incorrect
		// results with non-equidistant timestamps
		$value = isset($row[4]) ? $row[4] : $row[1];

		// @TODO check if scale is needed here
		$tuple = array(
			(float) ($this->ts_last = $row[0]),	// timestamp of interval end
			(float) $value / $this->resolution,
			(int) $row[2]
		);

		return $tuple;
	}

	/**
	 * Calculates the consumption
	 *
	 * @return float total consumption in Wh
	 */
	public function getConsumption() {
		// convert to Wh
		// @TODO check if resolution is needed here
		return $this->channel->getDefinition()->hasConsumption ? $this->consumption / (3.6e3 * $this->scale) : NULL;
	}

	/**
	 * Get Average
	 *
	 * @return float average
	 */
	public function getAverage() {
		if ($this->consumption) {
			$delta = $this->getTo() - $this->getFrom();
			return $this->consumption / $delta;
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
	 * @author Andreas Götz <cpuidle@gmx.de>
	 * @param string $expression sql parameter
	 * @return string grouped sql expression
	 */
	public static function groupExprSQL($expression) {
		return 'AVG(' . $expression . ')';
	}
}

?>
