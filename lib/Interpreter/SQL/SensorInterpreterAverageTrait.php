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
	public function optimizeDataSQL(&$sql, &$sqlParameters) {
		// additional optimizations for SensorInterpreter only
		if (get_class($this->interpreter) !== SensorInterpreter::class)
			return parent::optimizeDataSQL($sql, $sqlParameters);

		// SensorInterpreter needs weighed average calculation
		$foo = array();
		$sqlTimeFilter = $this->interpreter->buildDateTimeFilterSQL($this->from, $this->to, $foo);

		// MySQL-specific implementation below
		if ($this->groupBy) {
			$sqlGroupFields = self::buildGroupBySQL($this->groupBy);

			$sql = $this->weighedAverageSQL($sqlTimeFilter) .
				   'GROUP BY ' . $sqlGroupFields . ' ' .
				   'ORDER BY timestamp ASC';
			return true;
		}

		// potential to reduce result set - can't do this for already grouped SQL
		if ($this->tupleCount && ($this->rowCount > $this->tupleCount)) {
			// use power of 2 instead of division for performance
			$bitShift = (int) floor(log(($this->to - $this->from) / $this->tupleCount, 2));

			if ($bitShift > 0) { // worth doing -> go
				// ensure first tuple consumes only record
				$packageSize = 1 << $bitShift;
				$timestampOffset = $this->from - $packageSize + 1;

				// prevent DataIterator from further packaging
				// unless exactly one tuple is requested
				if ($this->tupleCount != 1) $this->tupleCount = null;

				// optimize package statement general case: tuple packaging
				$sql = $this->weighedAverageSQL($sqlTimeFilter) .
					   'GROUP BY (timestamp - ' . $timestampOffset . ') >> ' . $bitShift . ' ' .
					   'ORDER BY timestamp ASC';
				return true;
			}
		}

		// we know the parent optimizer doesn't habe anything better to offer
		return false;
	}
}

?>
