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

use Volkszaehler\Interpreter;
use Volkszaehler\Util;
use Volkszaehler\Model;
use Doctrine\DBAL;

/**
 * MySQLOptimizer provides basic DB-specific optimizations
 */
class MySQLOptimizer extends SQLOptimizer {

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
	 * SQL statement optimization for perfromance
	 *
	 * @param  string $sql           SQL statement to modify
	 * @param  array  $sqlParameters Parameters list
	 * @return boolean               Success
	 */
	public function optimizeDataSQL(&$sql, &$sqlParameters) {
		if ($this->groupBy) {
			// SensorInterpreter needs weighed average calculation for correctness - MySQL-specific implementation below
			if (get_class($this->interpreter) !== Interpreter\SensorInterpreter::class)
				return false;

			$foo = array();
			$sqlTimeFilter = $this->interpreter->buildDateTimeFilterSQL($this->from, $this->to, $foo);
			$sqlGroupFields = $this->interpreter->buildGroupBySQL($this->groupBy);

			// SensorInterpreter needs weighed average
			// note:	GREATEST() is required to force MySQL to evaluate the variables in the needed order (hacky)
			// see:		https://bugs.php.net/bug.php?id=67537
			// 			http://stackoverflow.com/questions/24457442/how-to-find-previous-record-n-per-group-maxtimestamp-timestamp
			// 			http://www.xaprb.com/blog/2006/12/15/advanced-mysql-user-variable-techniques/
			$sql = 'SELECT MAX(agg.timestamp) AS timestamp, ' .
						  'COALESCE( ' .
							  'SUM(agg.val_by_time) / (MAX(agg.timestamp) - MIN(agg.prev_timestamp)), ' .
							  $this->interpreter->groupExprSQL('agg.value') .
						  ') AS value, ' .
						  'COUNT(agg.value) AS count ' .
				   'FROM ( ' .
						'SELECT timestamp, value, ' .
							'value * (timestamp - @prev_timestamp) AS val_by_time, ' .
							'GREATEST(0, IF(@prev_timestamp = NULL, NULL, @prev_timestamp)) AS prev_timestamp, ' .
							'@prev_timestamp := timestamp ' .
						'FROM data ' .
						'CROSS JOIN (SELECT @prev_timestamp := NULL) AS vars ' .
						'WHERE channel_id=? ' . $sqlTimeFilter . ' ' .
						'ORDER BY timestamp ' .
				   ') AS agg ' .
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

				// optimize package statement general case: tuple packaging
				$foo = array();
				$sqlTimeFilter = $this->interpreter->buildDateTimeFilterSQL($this->from, $this->to, $foo);
				// $this->rowCount = floor($this->rowCount / $packageSize);

				// Speedup - general case
				if (get_class($this->interpreter) !== Interpreter\SensorInterpreter::class) {
					// TODO: find solution to ensure we get 2 rows even if
					// tuples=1 requested (first row is discarded by DataIterator)
					$sql = 'SELECT MAX(agg.timestamp) AS timestamp, ' .
								   $this->interpreter->groupExprSQL('agg.value') . ' AS value, ' .
								  'COUNT(agg.value) AS count ' .
						   'FROM (' .
								 'SELECT timestamp, value ' .
								 'FROM data ' .
								 'WHERE channel_id=?' . $sqlTimeFilter . ' ' .
						   		 'ORDER BY timestamp ASC' .
						   ') AS agg ' .
						   'GROUP BY (timestamp - ' . $timestampOffset . ') >> ' . $bitShift . ' ' .
						   'ORDER BY timestamp ASC';
				}
				else {
					// Speedup - SensorInterpreter case (weighed average calculation)
					$sql = 'SELECT MAX(agg.timestamp) AS timestamp, ' .
								  'COALESCE( ' .
									  'SUM(agg.val_by_time) / (MAX(agg.timestamp) - MIN(agg.prev_timestamp)), ' .
									  $this->interpreter->groupExprSQL('agg.value') .
								  ') AS value, ' .
								  'COUNT(agg.value) AS count ' .
						   'FROM ( ' .
								'SELECT timestamp, value, ' .
									'value * (timestamp - @prev_timestamp) AS val_by_time, ' .
									'GREATEST(0, IF(@prev_timestamp = NULL, NULL, @prev_timestamp)) AS prev_timestamp, ' .
									'@prev_timestamp := timestamp ' .
								'FROM data ' .
								'CROSS JOIN (SELECT @prev_timestamp := NULL) AS vars ' .
								'WHERE channel_id=? ' . $sqlTimeFilter . ' ' .
								'ORDER BY timestamp ASC' .
						   ') AS agg ' .
						   'GROUP BY (timestamp - ' . $timestampOffset . ') >> ' . $bitShift . ' ' .
						   'ORDER BY timestamp ASC';
				}

				return true;
			}
		}

		return false;
	}
}

?>
