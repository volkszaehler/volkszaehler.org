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

use Volkszaehler\Util;
use Volkszaehler\Model;
use Doctrine\DBAL;

/**
 * MySQLOptimizer provides basic DB-specific optimizations
 */
class MySQLOptimizer extends SQLOptimizer {

	use SensorInterpreterAverageTrait;

	/**
	 * Disable SQL statement caching
	 */
	public function disableCache() {
		$this->conn->executeQuery('SET SESSION query_cache_type = 0');
	}

	/**
	 * DB-specific data grouping by date functions
	 *
	 * @param string $groupBy
	 * @return string the sql part
	 */
	public static function buildGroupBySQL($groupBy) {
		$ts = 'FROM_UNIXTIME(timestamp/1000)'; // just for saving space

		switch ($groupBy) {
			case 'year':
				return 'YEAR(' . $ts . ')';
				break;

			case 'month':
				return 'YEAR(' . $ts . '), MONTH(' . $ts . ')';
				break;

			case 'week':
				return 'YEAR(' . $ts . '), WEEKOFYEAR(' . $ts . ')';
				break;

			case 'day':
				return 'YEAR(' . $ts . '), DAYOFYEAR(' . $ts . ')';
				break;

			case 'hour':
				return 'YEAR(' . $ts . '), DAYOFYEAR(' . $ts . '), HOUR(' . $ts . ')';
				break;

			case '15m':
				return 'YEAR(' . $ts . '), DAYOFYEAR(' . $ts . '), HOUR(' . $ts . '), FLOOR(MINUTE(' . $ts . ') / 15)';
				break;

			case 'minute':
				return 'YEAR(' . $ts . '), DAYOFYEAR(' . $ts . '), HOUR(' . $ts . '), MINUTE(' . $ts . ')';
				break;

			case 'second':
				return 'YEAR(' . $ts . '), DAYOFYEAR(' . $ts . '), HOUR(' . $ts . '), MINUTE(' . $ts . '), SECOND(' . $ts . ')';
				break;

			default:
				return FALSE;
		}
	}

	/**
	 * DB-specific cross-database join table delete statements
	 *
	 * @param string $table table name
	 * @param string $join join table name
	 * @param string $id id column name
	 * @return string the sql part
	 */
	public static function buildDeleteFromJoinSQL($table, $join, $id = 'id') {
		$sql = 'DELETE ' . $table . ' FROM ' . $table . ' ' . $join;
		return $sql;
	}

	/**
	 * Provide SQL statement for SensorInterpreterAverageTrait->optimizeDataSQL
	 * SensorInterpreter special case
	 *
	 * Fir MySQL discussion see
	 * https://bugs.php.net/bug.php?id=67537
	 * http://stackoverflow.com/questions/24457442/how-to-find-previous-record-n-per-group-maxtimestamp-timestamp
	 * http://www.xaprb.com/blog/2006/12/15/advanced-mysql-user-variable-techniques/
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
					'value * (timestamp - @prev_timestamp) AS val_by_time, ' .
					'COALESCE(@prev_timestamp, 0) AS prev_timestamp, ' .
					'@prev_timestamp := timestamp ' .
				'FROM data ' .
				'CROSS JOIN (SELECT @prev_timestamp := NULL) AS vars ' .
				'WHERE channel_id=? ' . $sqlTimeFilter . ' ' .
				'ORDER BY timestamp ASC' .
		   ') AS agg ';
		return $sql;
	}
}

?>
