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

namespace Volkszaehler\Interpreter;

/**
 * Sensor interpreter
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Götz <cpuidle@gmx.de>
 */

class SensorInterpreter extends Interpreter {

	protected $consumption; // in Wms (Watt milliseconds)
	protected $ts_last; // previous tuple timestamp

	/**
	 * Generate database tuples
	 *
	 * @return \Generator
	 */
	public function getIterator() {
		$this->rows = $this->getData();
		$this->ts_last = $this->getFrom();

		foreach ($this->rows as $row) {
			if ($this->raw) {
				// raw database values
				yield array_slice($row, 0, 3);
			}
			else {
				$delta_ts = $row[0] - $this->ts_last;
				$tuple = $this->convertRawTuple($row);
				$this->consumption += $tuple[1] * $delta_ts;

				$this->updateMinMax($tuple);

				yield $tuple;
			}
		}
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
