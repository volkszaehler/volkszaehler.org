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
 * Counter interpreter
 * Collects values as-is and does not differentiate counted value over time
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 */

class CounterInterpreter extends AccumulatorInterpreter {

	/**
	 * Convert raw meter readings
	 */
	public function convertRawTuple($row) {
		$delta_ts = $row[0] - $this->ts_last; // time between now and row before

		// instead of reverting what DataIterator->next did when packaging by $val = $row[1] / $row[2]
		// get max value which DataIterator->next provides as courtesy
		$value = isset($row[3]) ? $row[3] : $row[1];
		$this->delta_val = $value - $this->last_val;
		$this->last_val = $value;

		$tuple = array(
			(float) ($this->ts_last = $row[0]), // timestamp of interval end
			(float) ($this->delta_val * $this->scale) / $this->resolution,
			(int) $row[2] // num of rows
		);

		return $tuple;
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
			return ($this->scale * $this->valsum) / ($this->resolution * $delta);
		}
		else { // prevents division by zero
			return 0;
		}
	}
}

?>
