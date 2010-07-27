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
 * sensor interpreter
 *
 * @package default
 * @author Steffen Vogel <info@steffenvogel.de>
 */
class SensorInterpreter extends Interpreter {

	/**
	 * @todo untested
	 */
	public function getValues($groupBy = NULL) {
		$data = parent::getData($groupBy);

		$values = array();
		foreach ($data as $reading) {
			$values[] = array(
				$reading[0],
				$reading[1] / $reading[2],	// calculate average (ungroup the sql sum() function)
				$reading[2]
			);
		}

		return $values;
	}

	/**
	 * @todo adapt to doctrine orm
	 * @todo untested
	 */
	public function getMin() {
		return $this->dbh->query('SELECT value, timestamp FROM data WHERE channel_id = ' . (int) $this->id . self::buildFilterTime($this->from, $this->to) . ' ORDER BY value ASC', 1)->current();
	}

	/**
	 * @todo adapt to doctrine orm
	 * @todo untested
	 */
	public function getMax() {
		return $this->dbh->query('SELECT value, timestamp FROM data WHERE channel_id = ' . (int) $this->id . self::buildFilterTime($this->from, $this->to) . ' ORDER BY value DESC', 1)->current();
	}

	/**
	 * @todo adapt to doctrine orm
	 * @todo untested
	 */
	public function getAverage() {
		return $this->dbh->query('SELECT AVG(value) AS value FROM data WHERE channel_id = ' . (int) $this->id . self::buildFilterTime($this->from, $this->to))->current();
	}

	/**
	 * @todo to be implemented
	 */
	public function getConsumption() {

	}
}

?>