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

	/**
	 * Calculates the consumption for interval speciefied by $from and $to
	 *
	 * @todo improfve workaround
	 */
	public function getConsumption() {
		$sql = 'SELECT COUNT(*) FROM `data` WHERE `channel_id` = ?' . parent::buildDateTimeFilterSQL($this->from, $this->to);

		return $this->conn->fetchColumn($sql, array($this->channel->getId()), 0) / $this->channel->getProperty('resolution');     // return KWh
	}

	/**
	 * @return array (0 => timestamp, 1 => value)
	 * @todo reimplement according to new env
	 */
	public function getMin() {
		/*$data = $this->getData();

		$min = current($data);
		foreach ($data as $reading) {
			if ($reading['value '] < $min['value']) {
				$min = $reading;
			}
		}
		return $min;*/
	}

	/**
	 * @return array (0 => timestamp, 1 => value)
	 * @todo reimplement according to new env
	 */
	public function getMax() {
		/*$data = $this->getData();

		$max = current($data);
		foreach ($data as $reading) {
			if ($reading['value '] > $max['value']) {
				$max = $reading;
			}
		}
		return $max;*/
	}

	/**
	 * @return float
	 */
	public function getAverage() {
		// 3600: 3600 s/h; 1000: ms -> s; 1000: KW -> W
		return (3600 * 1000 * 1000 * $this->getConsumption()) / ($this->to - $this->from);
	}

	/**
	 * Raw pulses to power conversion
	 *
	 * @todo untested
	 * @return array with timestamp, values, and pulse count
	 */
	public function getValues($tuples, $groupBy, $callback) {
		$pulses = parent::getData($tuples, $groupBy);

		$values = array();
		foreach ($pulses as $pulse) {
			if (isset($last)) {
				$values[] = $callback($this->raw2differential($last, $pulse));
				$last = $pulse;
			}
			else {
				$last = $pulse;
			}
		}

		return $values;
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
			($next[0] - $delta / 2),	// timestamp
			$next[1] * (3600000 / (($this->channel->getProperty('resolution') / 1000) * $delta)),	// value
			$next[2]
		);
	}
}

?>
