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

	protected $consumption; // in Wms (Watt milliseconds)

	/**
	 * Calculates the consumption
	 *
	 * @return float total consumption in Wh
	 */
	public function getConsumption() {
		return $this->channel->getDefinition()->hasConsumption ? $this->consumption / 3.6e6 : NULL; // convert to Wh
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

	public function processData($callback) {
		$tuples = array();
		$this->rows = $this->getData();

		$ts_last = $this->getFrom();
		foreach ($this->rows as $row) {
			$delta = $row[0] - $ts_last;
			$tuple = $callback(array(
				(float) ($ts_last = $row[0]), // timestamp of interval end
				(float) $row[1] / $row[2],
				(int) $row[2]
			));

			if (is_null($this->max) || $tuple[1] > $this->max[1]) {
				$this->max = $tuple;
			}

			if (is_null($this->min) || $tuple[1] < $this->min[1]) {
				$this->min = $tuple;
			}

			$this->consumption += $tuple[1] * $delta;

			$tuples[] = $tuple;
		}

		return $tuples;
	}
}

?>
