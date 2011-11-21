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
 * Meter interpreter
 *
 * @package default
 * @author Steffen Vogel (info@steffenvogel.de)
 *
 */
use Volkszaehler;
use Volkszaehler\Util;

class MeterInterpreter extends Interpreter {

	protected $pulseCount;
	protected $resolution;
	
	/**
	 * Calculates the consumption
	 *
	 * @return float total consumption in Wh
	 */
	public function getConsumption() {
		return 1000 * $this->pulseCount / $this->resolution;
	}

	/**
	 * Get minimum
	 *
	 * @return array (0 => timestamp, 1 => value)
	 */
	public function getMin() {
		return ($this->min) ? array_map('floatval', array_slice($this->min, 0 , 2)) : NULL;
	}

	/**
	 * Get maximum
	 *
	 * @return array (0 => timestamp, 1 => value)
	 */
	public function getMax() {
		return ($this->max) ? array_map('floatval', array_slice($this->max, 0 , 2)) : NULL;
	}

	/**
	 * Get Average
	 *
	 * @return float average in W
	 */
	public function getAverage() {
		if ($this->pulseCount) {
			$delta = $this->rows->getTo() - $this->rows->getFrom();
			return (3.6e9 * $this->pulseCount) / ($this->resolution * $delta); // 60 s/min * 60 min/h * 1.000ms/s * 1.000W/KW = 3.6e9 (Units: s/h*ms/s*W/KW = s/3.600s*.001s/s*W/1.000W = 1)
		}
		else { // prevents division by zero
			return 0;
		}
	}

	/**
	 * Raw pulse to power conversion
	 *
	 * @param $callback a callback called each iteration for output
	 * @return array with timestamp, values, and pulse count
	 */
	public function processData($callback) {
		$tuples = array();
		$this->rows = parent::getData();

		$this->resolution = $this->channel->getProperty('resolution');
		$this->pulseCount = 0;
		
		$last = $this->getFrom();
		foreach ($this->rows as $row) {
			$delta = $row[0] - $last;
			$tuple = $callback(array(
				(float) $last, // timestamp of interval start
				(float) ($row[1] * 3.6e9) / ($this->resolution * $delta), // doing df/dt
				(int) $row[2] // num of rows
			));
			
			if (is_null($this->max) || $tuple[1] > $this->max[1]) {
				$this->max = $tuple;
			}
			
			if (is_null($this->min) || $tuple[1] < $this->min[1]) {
				$this->min = $tuple;
			}
				
			$this->pulseCount += $row[1];

			$tuples[] = $tuple;
			$last = $row[0];
		}
		
		return $tuples;
	}
}

?>
