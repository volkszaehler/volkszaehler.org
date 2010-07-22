<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package data
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
 * meter interpreter
 *
 * @package data
 * @author Steffen Vogel (info@steffenvogel.de)
 *
 */
class MeterInterpreter extends Interpreter {

	/**
	 * calculates the consumption for interval speciefied by $from and $to
	 *
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 */
	public function getConsumption($from = NULL, $to = NULL) {	// TODO untested
		$sql = 'SELECT SUM(value) AS count
				FROM data
				WHERE
					channel_id = ' . (int) $this->id . ' &&
					' . $this->getTimeFilter($from, $to) . '
				GROUP BY channel_id';

		$result = $this->dbh->query($sql)->rewind();

		return $result['count'] / $this->resolution / 1000;	// returns Wh
	}

	/**
	 *
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 */
	public function getMin($from = NULL, $to = NULL) {
		$data = $this->getData($from, $to);

		$min = current($data);
		foreach ($data as $reading) {
			if ($reading['value '] < $min['value']) {
				$min = $reading;
			}
		}
		return $min;
	}

	/**
	 *
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 */
	public function getMax($from = NULL, $to = NULL) {
		$data = $this->getData($from, $to);

		$min = current($data);
		foreach ($data as $reading) {
			if ($reading['value '] > $min['value']) {
				$min = $reading;
			}
		}
		return $min;
	}

	/**
	 *
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 * @todo calculate timeinterval if no params were given
	 */
	public function getAverage($from = NULL, $to = NULL) {
		return $this->getConsumption($from, $to) / ($to - $from) / 1000;	// return W
	}

	/**
	 * just a passthru of raw data
	 *
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 */
	public function getPulses($from = NULL, $to = NULL, $groupBy = NULL) {
		return parent::getData($from, $to, $groupBy);
	}

	/**
	 * raw pulses to power conversion
	 *
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 */
	public function getValues($from = NULL, $to = NULL, $groupBy = NULL) {
		$pulses = parent::getData($from, $to, $groupBy);
		$pulseCount = count($pulses);

		for ($i = 1; $i < $pulseCount; $i++) {
			$delta = $pulses[$i]['timestamp'] - $pulses[$i-1]['timestamp'];

			$pulses[$i]['timestamp'] -= $delta/2;
			$pulses[$i]['value'] *= 3600000/(($this->channel->getResolution() / 1000) * $delta);	// TODO untested
		}

		return $pulses;	// returns W
	}
}

?>