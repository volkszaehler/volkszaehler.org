<?php
/**
 * @copyright Copyright (c) 2011-2017, The volkszaehler.org project
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
 * Meter interpreter
 *
 * @package default
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Götz <cpuidle@gmx.de>
 */

class ImpulseInterpreter extends Interpreter {

	protected $pulseCount;
	protected $ts_last; // previous tuple timestamp

	/**
	 * Generate database tuples
	 *
	 * @return \Generator
	 */
	public function getIterator() {
		$this->rows = $this->getData();
		$this->pulseCount = 0;
		$this->ts_last = $this->getFrom();

		foreach ($this->rows as $row) {
			if ($this->raw) {
				// raw database values
				yield array_slice($row, 0, 3);
			}
			else {
				$tuple = $this->convertRawTuple($row);
				$this->pulseCount += $row[1];

				$this->updateMinMax($tuple);

				yield $tuple;
			}
		}
	}

	/**
	 * Convert raw meter readings
	 */
	public function convertRawTuple($row) {
		$delta = $row[0] - $this->ts_last;

		// (1 imp * 60 min/h * 60 s/min * 1000 ms/s * scale) / (1 imp/kWh * 1ms) = 3.6e6 kW
		$tuple = array(
			(float) ($this->ts_last = $row[0]), // timestamp of interval end
			(float) ($row[1] * 3.6e6 * $this->scale) / ($this->resolution * $delta), // doing df/dt
			(int) $row[2] // num of rows
		);

		return $tuple;
	}

	/**
	 * Calculates the consumption
	 *
	 * @return float total consumption in Wh
	 */
	public function getConsumption() {
		return $this->channel->getDefinition()->hasConsumption ? $this->scale * $this->pulseCount / $this->resolution : NULL;
	}

	/**
	 * Get Average
	 *
	 * @return float average in W
	 */
	public function getAverage() {
		if ($this->pulseCount) {
			$delta = $this->getTo() - $this->getFrom();
			// 60 s/min * 60 min/h * 1.000 ms/s * 1.000 W/kW = 3.6e9 (Units: s/h*ms/s*W/KW = s/3.600s*.001s/s*W/1.000W = 1)
			return (3.6e6 * $this->scale * $this->pulseCount) / ($this->resolution * $delta);
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
	 * AccumulatorInterpreter needs MAX instead of SUM.
	 *
	 * @author Andreas Götz <cpuidle@gmx.de>
	 * @param string $expression sql parameter
	 * @return string grouped sql expression
	 */
	public static function groupExprSQL($expression) {
		return 'SUM(' . $expression . ')';
	}
}

?>
