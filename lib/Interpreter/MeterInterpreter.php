<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
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

	protected $min = NULL;
	protected $max = NULL;
	protected $pulseCount = NULL;
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
	 * @return float 3600: 3600 s/h; 1000: ms -> s
	 */
	public function getAverage() {
		return (3600 * 1000 * $this->getConsumption()) / ($this->to - $this->from);
	}

	/**
	 * Raw pulses to power conversion
	 *
	 * @param $callback a callback called each iteration for output
	 * @return array with timestamp, values, and pulse count
	 */
	public function processData($callback) {
		$tuples = array();
		$pulses = parent::getData();

		$this->resolution = $this->channel->getProperty('resolution');
		$this->pulseCount = 0;
		
		$last = $pulses->rewind();
		$next = $pulses->next();
		
		while ($pulses->valid()) {
			$tuple = $callback($this->raw2differential($last, $next));
			
			if (is_null($this->max) || $tuple[1] > $this->max[1]) {
				$this->max = $tuple;
			}
			
			if (is_null($this->min) || $tuple[1] < $this->min[1]) {
				$this->min = $tuple;
			}
				
			$this->pulseCount += $tuple[2];

			$tuples[] = $tuple;
			$last = $next;			
			$next = $pulses->next();
		}

		return $tuples;
	}

	/**
	 * Calculates the differential quotient of two consecutive pulses
	 *
	 * @param array $last the last pulse
	 * @param array $next the next pulse
	 */
	protected function raw2differential(array $last, array $next) {
		$delta = $next[0] - $last[0];

		return array(
			//($next[0] - $delta / 2), // timestamp in the middle
			$next[0], // timestamp at the end
			$next[1] * (3600000 / (($this->resolution / 1000) * $delta)), // value
			$next[2] // num of pulses
		);
	}
}

?>
