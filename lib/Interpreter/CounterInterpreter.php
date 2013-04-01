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
		return $this->channel->getDefinition()->hasConsumption ? $this->valsum * 1000 / $this->resolution : NULL;
	}

	/**
	 * Get Average
	 *
	 * @return float average in W
	 */
	public function getAverage() {
		if ($this->valsum) {
			$delta = $this->rows->getTo() - $this->rows->getFrom();
			return (3.6e9 * $this->valsum) / ($this->resolution * $delta); // 60 s/min * 60 min/h * 1.000ms/s * 1.000W/KW = 3.6e9 (Units: s/h*ms/s*W/KW = s/3.600s*.001s/s*W/1.000W = 1)
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
		$this->rows = parent::getData();

		$this->resolution = $this->channel->getProperty('resolution');
		$this->valsum = 0;
		
		foreach ($this->rows as $row) {
			$val = $row[1] / $row[2]; // kind of revert what DataIterator::next did to our data

			if (!isset($last_val)) { # skip first row - we need a starting value
				$last_ts = $row[0];
				$last_val = $val;
				continue;
			}
			if ($val === $last_val)
				continue; # skip duplicate values

			$delta_ts = $row[0] - $last_ts; # time between now and row before
			$delta_val = $val - $last_val;
			$tuple = $callback(array(
				(float) $last_ts, // timestamp of interval start
				(float) ($delta_val / $this->resolution) * 3.6e9 / $delta_ts, // doing df/dt
				(int) $row[2] // num of rows
			));
			$last_ts = $row[0];
			$last_val = $val;
			
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
}

?>
