<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package data
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
 *
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
 * @package data
 * @author Steffen Vogel <info@steffenvogel.de>
 */
class SensorInterpreter extends Interpreter {

	/**
	 *
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 */
	public function getValues($from = NULL, $to = NULL, $groupBy = NULL) {
		$data = parent::getData($from, $to, $groupBy);

		array_walk($data, function(&$reading) {
			$reading['value'] /= $reading['count'];	// calculate average (ungroup the sql sum() function)
		});

		return $data;
	}

	/**
	 *
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 *
	 * @todo untested
	 */
	public function getMin($from = NULL, $to = NULL) {
		return $this->dbh->query('SELECT value, timestamp FROM data WHERE channel_id = ' . (int) $this->id . self::buildFilterTime($from, $to) . ' ORDER BY value ASC', 1)->current();
	}

	/**
	 *
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 *
	 * @todo untested
	 */
	public function getMax($from = NULL, $to = NULL) {	// TODO untested
		return $this->dbh->query('SELECT value, timestamp FROM data WHERE channel_id = ' . (int) $this->id . self::buildFilterTime($from, $to) . ' ORDER BY value DESC', 1)->current();
	}

	/**
	 *
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 *
	 * @todo untested
	 */
	public function getAverage($from = NULL, $to = NULL) {	// TODO untested
		return $this->dbh->query('SELECT AVG(value) AS value FROM data WHERE channel_id = ' . (int) $this->id . self::buildFilterTime($from, $to))->current();
	}
}

?>