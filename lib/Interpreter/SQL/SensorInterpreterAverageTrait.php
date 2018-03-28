<?php
/**
 * @copyright Copyright (c) 2017, The volkszaehler.org project
 * @package default
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
 * @author Andreas Goetz <cpuidle@gmx.de>
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
	 * SQL statement optimization for performance
	 *
	 * @param  string $sql           SQL statement to modify
	 * @param  array  $sqlParameters Parameters list
	 * @return boolean               Success
	 */
	public function optimizeDataSQL(&$sql, &$sqlParameters, $rowCount) {
		// additional optimizations for SensorInterpreter only
		if (get_class($this->interpreter) !== SensorInterpreter::class) {
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
