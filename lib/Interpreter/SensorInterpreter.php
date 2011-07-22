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
 */
use Volkszaehler\Util;

class SensorInterpreter extends Interpreter {

	protected $consumption = NULL; // in Wms (Watt milliseconds)
	protected $min = NULL;
	protected $max = NULL;
	protected $first = NULL;
	protected $last = NULL;
	
	/**
	 * Calculates the consumption
	 *
	 * @return float total consumption in Wh
	 */
	public function getConsumption() {
		return $this->consumption / 3600000; // convert to Wh
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
	 * @return float 3600: 3600 s/h; 1000: ms -> s
	 */
	public function getAverage() {
		if ($this->consumption) {
			$delta = $this->last[0] - $this->first[0];
			return $this->consumption / $delta;
		}
		else { // prevents division by zero
			return 0;
		}
	}

	public function processData($callback) {
		$data = parent::getData();
		$tuples = array();
		
		$last = $data->rewind();
		$next = $data->next();
		
		while ($data->valid()) {
			$tuple = $callback(array(
				(float) $next[0],
				(float) $next[1] / $next[2],
				(int) $next[2]
			));
			
			if (is_null($this->max) || $tuple[1] > $this->max[1]) {
				$this->max = $tuple;
			}
			
			if (is_null($this->min) || $tuple[1] < $this->min[1]) {
				$this->min = $tuple;
			}
			
			/*
			 * Workaround for #73
			 * Due to the "overfetching"" at the boundary regions
			 */
			if ($last[0] > $this->from && $next[0] < $this->to) {
				$this->consumption += $next[1] * ($next[0] - $last[0]);
			}
				
			$tuples[] = $tuple;
			$last = $next;			
			$next = $data->next();
		}
		
		$this->first = reset($tuples);
		$this->last = end($tuples);

		return $tuples;
	}
}

?>
