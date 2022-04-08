<?php
/**
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2020, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
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

namespace Volkszaehler\Interpreter\SQL;

use Volkszaehler\Interpreter\SensorInterpreter;

/**
 * Weighed average optimizations for SensorInterpreter
 * database-agnostic part
 */
trait SensorInterpreterAverageTrait {

	/**
	 * Determine if interpreter requires a weighed average calculation
	 * @return boolean Weighed average required
	 */
	public function weighedAverageRequired() {
		return get_class($this->interpreter) == SensorInterpreter::class;
	}

	/**
	 * SQL statement optimization for performance
	 *
	 * @param  string $sql           SQL statement to modify
	 * @param  array  $sqlParameters Parameters list
	 * @param  int    $rowCount		 Desired number of result rows
	 * @return boolean
	 */
	public function optimizeDataSQL(&$sql, &$sqlParameters, $rowCount) {
		// additional optimizations for SensorInterpreter only
		if (!$this->weighedAverageRequired()) {
			return parent::optimizeDataSQL($sql, $sqlParameters, $rowCount);
		}

		// SensorInterpreter needs weighed average calculation
		$foo = array();
		$sqlTimeFilter = self::buildDateTimeFilterSQL($this->from, $this->to, $foo);

		if ($this->groupBy) {
			$sqlGroupFields = self::buildGroupBySQL($this->groupBy);
			$sql = $this->weighedAverageSQL($sqlTimeFilter) .
				   'GROUP BY ' . $sqlGroupFields . ' ' .
				   'ORDER BY timestamp ASC';

			return true;
		}

		// perform tuple packaging in SQL - can't do this for grouped queries
		if (list($bitShift, $timestampOffset) = $this->applyBinaryTuplePackaging($rowCount)) {
			// optimize package statement general case: tuple packaging
			$sql = $this->weighedAverageSQL($sqlTimeFilter) .
				   'GROUP BY (timestamp - ' . $timestampOffset . ') >> ' . $bitShift . ' ' .
				   'ORDER BY timestamp ASC';

			return true;
		}

		// we know the parent optimizer doesn't have anything better to offer
		return false;
	}
}

?>
