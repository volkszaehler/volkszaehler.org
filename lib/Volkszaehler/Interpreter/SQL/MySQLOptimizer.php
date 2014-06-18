<?php
/**
 * @copyright Copyright (c) 2013, The volkszaehler.org project
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

use Volkszaehler\Interpreter;
use Volkszaehler\Util;
use Volkszaehler\Model;
use Doctrine\DBAL;

/**
 * MySQLOptimizer provides basic DB-specific optimizations
 */
class MySQLOptimizer extends SQLOptimizer {

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
		// special treatment
		if (get_class($this->interpreter) == 'Volkszaehler\\Interpreter\\SensorInterpreter')
			return $this->optimizeDataSQLforSensorInterpreter($sql, $sqlParameters);

		if ($this->groupBy)
			return false;

		// potential to reduce result set - can't do this for already grouped SQL
		if ($this->tupleCount && ($this->rowCount > $this->tupleCount)) {
			$packageSize = floor($this->rowCount / $this->tupleCount);

			if ($packageSize > 1) { // worth doing -> go
				// optimize package statement general case: tuple packaging
				$foo = array();
				$sqlTimeFilter = $this->interpreter->buildDateTimeFilterSQL($this->from, $this->to, $foo);

				$this->rowCount = floor($this->rowCount / $packageSize);

				// setting @row to packageSize-2 will make the first package contain 1 tuple only
				// this pushes as much 'real' data as possible into the first used package and ensures
				// we get 2 rows even if tuples=1 requested (first row is discarded by DataIterator)
				$sql = 'SELECT MAX(agg.timestamp) AS timestamp, ' .
							   $this->interpreter->groupExprSQL('agg.value') . ' AS value, ' .
							  'COUNT(agg.value) AS count ' .
					   'FROM (' .
							 'SELECT timestamp, value, @row:=@row+1 AS row ' .
							 'FROM data ' .
							 'CROSS JOIN (SELECT @row := ' . ($packageSize-2) . ') AS vars ' . // initialize rowcount variable
							 'WHERE channel_id=?' . $sqlTimeFilter . ' ' .
					   		 'ORDER BY timestamp' .
					   ') AS agg ' .
					   'GROUP BY row DIV ' . $packageSize . ' ' .
					   'ORDER BY timestamp ASC';

				return true;
			}
		}

		return false;
	}

	/**
	 * Special case handling: SensorInterpreter needs weighed average if correctness is required
	 *
	 * @param  string $sql           SQL statement to modify
	 * @param  array  $sqlParameters Parameters list
	 * @return boolean               Success
	 */
	public function optimizeDataSQLforSensorInterpreter(&$sql, &$sqlParameters) {
		$foo = array();
		$sqlTimeFilter = $this->interpreter->buildDateTimeFilterSQL($this->from, $this->to, $foo);

		if ($this->groupBy) {
			$sql = 'SELECT MAX(agg.timestamp) AS timestamp, ' .
						  'COALESCE( ' .
							  'SUM(agg.val_by_time) / (MAX(agg.timestamp) - MIN(agg.prev_timestamp)), ' .
							  $this->interpreter->groupExprSQL('agg.value') .
						  ') AS value, ' .
						  'COUNT(agg.value) AS count ' .
				   'FROM ( ' .
					   'SELECT collect.timestamp, collect.value, ' .
							  'prev.timestamp AS prev_timestamp, collect.value * (collect.timestamp - prev.timestamp) AS val_by_time ' .
					   'FROM data AS collect ' .
					   'LEFT JOIN data AS prev ON ' .	// subquery for previous row's timestamp
						   'prev.channel_id = collect.channel_id AND ' .
						   'prev.timestamp = ( ' .
							   'SELECT MAX(timestamp) ' .
							   'FROM data ' .
							   'WHERE data.channel_id = collect.channel_id AND ' .
									 'data.timestamp < collect.timestamp ' .
						   ') ' .
					   'WHERE collect.channel_id=?' . str_replace('timestamp', 'collect.timestamp', $sqlTimeFilter) . ' ' .
					   'ORDER BY collect.timestamp ' .
				   ') AS agg ' .
				   'GROUP BY ' . $this->interpreter->buildGroupBySQL($this->groupBy) . ' ' .
				   'ORDER BY timestamp ASC';

			return true;
		}

		// potential to reduce result set
		if ($this->tupleCount && ($this->rowCount > $this->tupleCount)) {
			$packageSize = floor($this->rowCount / $this->tupleCount);

			if ($packageSize > 1) { // worth doing -> go
				// optimize package statement general case: tuple packaging
				$this->rowCount = floor($this->rowCount / $packageSize);

				$sql = 'SELECT MAX(agg.timestamp) AS timestamp, ' .
							  'COALESCE( ' .
								  'SUM(agg.val_by_time) / (MAX(agg.timestamp) - MIN(agg.prev_timestamp)), ' .
								  $this->interpreter->groupExprSQL('agg.value') .
							  ') AS value, ' .
							  'COUNT(agg.value) AS count ' .
					   'FROM ( ' .
						   'SELECT collect.timestamp, collect.value, @row:=@row+1 AS row, ' .
								  'prev.timestamp AS prev_timestamp, collect.value * (collect.timestamp - prev.timestamp) AS val_by_time ' .
						   'FROM data AS collect ' .
						   'CROSS JOIN (SELECT @row := ' . ($packageSize-2) . ') AS vars ' . // initialize rowcount variable
						   'LEFT JOIN data AS prev ON ' .	// subquery for previous row's timestamp
							   'prev.channel_id = collect.channel_id AND ' .
							   'prev.timestamp = ( ' .
								   'SELECT MAX(timestamp) ' .
								   'FROM data ' .
								   'WHERE data.channel_id = collect.channel_id AND ' .
										 'data.timestamp < collect.timestamp ' .
							   ') ' .
						   'WHERE collect.channel_id=?' . str_replace('timestamp', 'collect.timestamp', $sqlTimeFilter) . ' ' .
						   'ORDER BY collect.timestamp ' .
					   ') AS agg ' .
					   'GROUP BY row div ' . $packageSize . ' ' .
					   'ORDER BY timestamp ASC';

				return true;
			}
		}

		return false;
	}
}

?>
