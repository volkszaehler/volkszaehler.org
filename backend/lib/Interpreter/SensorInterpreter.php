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
 * Sensor interpreter
 *
 * @package default
 * @author Steffen Vogel <info@steffenvogel.de>
 */
use Volkszaehler\Util;

class SensorInterpreter extends Interpreter {

	/**
	 * @todo untested
	 * @param string|integer $groupBy
	 */
	public function getValues($tuples = NULL, $groupBy = NULL) {
		$data = parent::getData($tuples, $groupBy);

		$values = array();
		foreach ($data as $reading) {
			$values[] = array(
				(float) $reading[0],
				(float) $reading[1] / $reading[2],
				(int) $reading[2]
			);
		}

		return $values;
	}

	/**
	 * Fetch the smallest value from database
	 * @internal doesn't fits the SQL standard
	 * @return array (0 => timestamp, 1 => value)
	 */
	public function getMin() {
		return array_map('floatval', $this->conn->fetchAssoc('SELECT value, timestamp FROM data WHERE channel_id = ' . (int) $this->channel->getId() . parent::buildDateTimeFilterSQL($this->from, $this->to) . ' ORDER BY value ASC LIMIT 1'));
	}

	/**
	 * Fetch the greatest value from the database
	 * @internal doesn't fits the SQL standard
	 * @return array (0 => timestamp, 1 => value)
	 */
	public function getMax() {
		return array_map('floatval', $this->conn->fetchAssoc('SELECT value, timestamp FROM data WHERE channel_id = ' . (int) $this->channel->getId() . parent::buildDateTimeFilterSQL($this->from, $this->to) . ' ORDER BY value DESC LIMIT 1'));
	}

	/**
	 * Fetch the average value from the database
	 * @internal doesn't fits the SQL standard
	 * @return float
	 */
	public function getAverage() {
		return (float) $this->conn->fetchColumn('SELECT AVG(value) FROM data WHERE channel_id = ' . (int) $this->channel->getId() . parent::buildDateTimeFilterSQL($this->from, $this->to));
	}

	/**
	 * @todo possible and/or required?
	 * @return float
	 */
	public function getConsumption() {

	}
}

?>
