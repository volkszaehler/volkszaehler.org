<?php
/**
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
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
 * PostgreSQLOptimizer provides basic DB-specific optimizations
 */
class PostgreSQLOptimizer extends SQLOptimizer {

	use SensorInterpreterAverageTrait;

	/**
	 * DB-specific data grouping by date functions
	 *
	 * @param string $groupBy
	 * @return string the sql part
	 */
	public static function buildGroupBySQL($groupBy) {
		$ts = "TIMESTAMP 'epoch' + timestamp * INTERVAL '1 millisecond'"; // just for saving space

		switch ($groupBy) {
			case 'year':
			case 'month':
			case 'week':
			case 'day':
			case 'hour':
			case 'minute':
			case 'second':
				return "DATE_TRUNC('" . $groupBy . "', " . $ts . ")";
			default:
				return FALSE;
		}
	}

	/**
	 * Provide SQL statement for SensorInterpreterAverageTrait->optimizeDataSQL
	 * SensorInterpreter special case
	 */
	function weighedAverageSQL($sqlTimeFilter) {
		$sql =
			'SELECT MAX(agg.timestamp) AS timestamp, ' .
				  'COALESCE( ' .
					  'SUM(agg.val_by_time) / (MAX(agg.timestamp) - MIN(agg.prev_timestamp)), ' .
					  $this->interpreter::groupExprSQL('agg.value') .
				  ') AS value, ' .
				  'COUNT(agg.value) AS count ' .
			'FROM ( ' .
				'SELECT timestamp, value, ' .
					'value * (timestamp - LAG(timestamp) OVER (ORDER BY timestamp)) AS val_by_time, ' .
					'LAG(timestamp) OVER (ORDER BY timestamp) AS prev_timestamp ' .
				'FROM data ' .
				'WHERE channel_id=? ' . $sqlTimeFilter . ' ' .
				'ORDER BY timestamp ' .
			') AS agg ';
		return $sql;
	}
}

?>
