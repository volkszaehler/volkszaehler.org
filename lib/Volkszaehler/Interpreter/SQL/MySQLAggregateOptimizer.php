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
 * MySQLAggregateOptimizer
 *
 * Provides additional DB-specific optimizations by utilizing
 * an additional 'materialized view' table.
 *
 * Speedup is achieved by combining main data and materialized view 'aggregate' tables
 * into single query.
 *
 * Tables are 'stitched' together by evaluating suitable timestamps:
 *
 *     table:   --data-- -----aggregate----- -data-
 * timestamp:   from ... aggFrom ..... aggTo ... to
 */
class MySQLAggregateOptimizer extends MySQLOptimizer {

	static $debug = false;	// development diagnosis

	protected $aggregator;

	protected $aggFrom;
	protected $aggTo;

	protected $aggValid;	// result of validateAggregationUsage
	protected $aggLevel;	// chosen aggregation level
	protected $aggType;		// numeric value of aggregation level

	protected $sqlTimeFilter;
	protected $sqlTimeFilterPre;
	protected $sqlTimeFilterPost;

	/**
	 * Must only be instantiated if config['aggregation'] = true
	 */
	public function __construct(Interpreter\Interpreter $interpreter, DBAL\Connection $conn) {
		parent::__construct($interpreter, $conn);
		$this->aggregator = new Util\Aggregation($this->conn);
	}

	/**
	 * Validate use of aggregation table
	 *
	 * 	1. find matching aggregation level <= $this->groupBy or highest level
	 * 	2. calculate if matching timestamps found
	 *
	 * @return boolean	Aggregation table usage validity
	 */
	private function validateAggregationUsage() {
		if ($this->aggValid === null) {
			$this->aggValid = false;

			$aggregationLevel = $this->aggregator->getOptimalAggregationLevel($this->channel->getUuid(), $this->groupBy);
			if ($aggregationLevel) {
				// choose highest level
				$this->aggLevel = $aggregationLevel[0]['level'];

				// numeric value of desired aggregation level
				$this->aggType = Util\Aggregation::getAggregationLevelTypeValue($this->aggLevel);

				// valid boundaries?
				$this->aggValid = $this->getAggregationBoundary();
			}
		}

		if (self::$debug) {
			$type = $this->aggValid ? 'true (' . $this->aggLevel . ')' : 'false';
			echo("validateAggregationUsage " . $type . "\n");
		}

		return $this->aggValid;
	}

	public function optimizeRowCountSQL(&$sqlRowCount, &$sqlParameters) {
		if ($optimize = $this->validateAggregationUsage()) {
			// numeric value of desired aggregation level
			$sqlParameters = $this->buildAggregationTableParameters();

			if ($this->groupBy) {
				// optimize grouped count statement by applying aggregation table
				$sqlGroupFields = $this->interpreter->buildGroupBySQL($this->groupBy);

				// 	   table:   --DATA-- -----aggregate----- -DATA-
				$sqlRowCount = 'SELECT DISTINCT ' . $sqlGroupFields . ' ' .
							   'FROM data WHERE channel_id = ? ' .
							   'AND (' . $this->sqlTimeFilterPre . ' OR' . $this->sqlTimeFilterPost . ') ';
				// 	   table:   --data-- -----AGGREGATE----- -data-
				$sqlRowCount.= 'UNION SELECT DISTINCT ' . $sqlGroupFields . ' ' .
							   'FROM aggregate ' .
							   'WHERE channel_id = ? AND type = ?' . $this->sqlTimeFilter;
				$sqlRowCount = 'SELECT COUNT(1) ' .
							   'FROM (' . $sqlRowCount . ') AS agg';
			}
			else {
				// optimize non-grouped count statement
				// 	   table:   --DATA-- -----aggregate----- -DATA-
				$sqlRowCount = 'SELECT COUNT(1) AS count ' .
							   'FROM data WHERE channel_id = ? ' .
							   'AND (' . $this->sqlTimeFilterPre . ' OR' . $this->sqlTimeFilterPost . ') ';
				// 	   table:   --data-- -----AGGREGATE----- -data-
				$sqlRowCount.= 'UNION SELECT SUM(count) AS count ' .
							   'FROM aggregate ' .
							   'WHERE channel_id = ? AND type = ?' . $this->sqlTimeFilter;
				$sqlRowCount = 'SELECT SUM(count) ' .
							   'FROM (' . $sqlRowCount . ') AS agg';
			}
		}

		// get upstream optimization
		return ($optimize) ?: parent::optimizeDataSQL($sql, $sqlParameters);
	}

	public function optimizeDataSQL(&$sql, &$sqlParameters) {
		$optimize = false;

		if ($this->validateAggregationUsage()) {

			if ($this->groupBy) {
				$optimize = true;

				// optimize grouped statement
				$sqlParameters = $this->buildAggregationTableParameters();
				$sqlGroupFields = $this->interpreter->buildGroupBySQL($this->groupBy);

				// 	   table:   --DATA-- -----aggregate----- -DATA-
				$sql = 'SELECT timestamp, value, 1 AS count ' .
					   'FROM data ' .
					   'WHERE channel_id = ? ' .
					   'AND (' . $this->sqlTimeFilterPre . ' OR' . $this->sqlTimeFilterPost . ') ';

				// 	   table:   --data-- -----AGGREGATE----- -data-
				$sql.= 'UNION SELECT timestamp, value, count ' .
					   'FROM aggregate ' .
					   'WHERE channel_id = ? AND type = ?' . $this->sqlTimeFilter;

				// add common aggregation and sorting on UNIONed table
				// (sorting applied outside UNION as MySQL doesn't guarantee UNION result ordering)
				$sql = 'SELECT MAX(timestamp) AS timestamp, ' .
						$this->interpreter->groupExprSQL('value') . ' AS value, SUM(count) AS count ' .
					   'FROM (' . $sql . ') AS agg ' .
					   'GROUP BY ' . $sqlGroupFields . ' ORDER BY timestamp ASC';
			}
			elseif ($this->tupleCount == 1 && ($this->rowCount > $this->tupleCount)) {
				// optimize non-grouped statement special case: package into 1 tuple
				$packageSize = floor($this->rowCount / $this->tupleCount);

				// shift aggregation boundary start by 1 unit to make sure first tuple is not aggregated
				$optimize = $packageSize > 1 && $this->getAggregationBoundary(1);

				if ($optimize) {
					$sqlParameters = $this->buildAggregationTableParameters();

					// 	   table:   --DATA-- -----aggregate----- -DATA-
					$sql = 'SELECT timestamp, value, 1 AS count, @row:=@row+1 AS row ' .
						   'FROM data ' .
						   'WHERE channel_id = ? ' .
						   'AND (' . $this->sqlTimeFilterPre . ' OR' . $this->sqlTimeFilterPost . ') ';

					// 	   table:   --data-- -----AGGREGATE----- -data-
					$sql.= 'UNION SELECT timestamp, value, count, @row:=@row+1 AS row ' .
						   'FROM aggregate ' .
						   'WHERE channel_id = ? AND type = ?' . $this->sqlTimeFilter;

					// add common aggregation and sorting on UNIONed table
					$sql = 'SELECT MAX(timestamp) AS timestamp, ' .
							$this->interpreter->groupExprSQL('value') . ' AS value, SUM(count) AS count ' .
						   'FROM (SELECT @row:=0) AS init, (' . $sql . ') AS agg ' .
						   'GROUP BY row > 1 ORDER BY timestamp ASC';
				}
			}
		}

		// get upstream optimization
		return ($optimize) ?: parent::optimizeDataSQL($sql, $sqlParameters);
	}

	/**
	 * Build SQL parameters for aggregation table access given timestamp boundaries
	 * @author Andreas Goetz <cpuidle@gmx.de>
	 */
	private function buildAggregationTableParameters() {
		$sqlParameters = array($this->channel->getId());

		// timestamp:   from ... aggFrom ..... aggTo ... to
		//     table:   --DATA-- -----aggregate----- -data-
		$this->sqlTimeFilterPre = self::buildDateTimeFilterSQL($this->from, $this->aggFrom, $sqlParameters, true, '');
		// 	   table:   --data-- -----aggregate----- -DATA-
		$this->sqlTimeFilterPost = self::buildDateTimeFilterSQL($this->aggTo, $this->to, $sqlParameters, false, '');

		array_push($sqlParameters, $this->channel->getId(), $this->aggType);
		// 	   table:   --data-- -----AGGREGATE----- -data-
		$this->sqlTimeFilter = self::buildDateTimeFilterSQL($this->aggFrom, $this->aggTo, $sqlParameters, true);

		return $sqlParameters;
	}

	/**
	 * Calculate valid timestamp boundaries for aggregation table usage
	 *
	 *     table:   --data-- -----aggregate----- -data-
	 * timestamp:   from ... aggFrom ..... aggTo ... to
	 *
	 * @param string $type aggregation level (e.g. 'day')
	 * @return boolean true: aggregate table contains data, aggFrom/aggTo contains valid range
	 * @author Andreas Goetz <cpuidle@gmx.de>
	 */
	private function getAggregationBoundary($aggFromDelta = null) {
		$dateFormat = Util\Aggregation::getAggregationDateFormat($this->aggLevel); // day = "%Y-%m-%d"

		// aggFrom becomes beginning of first period with aggregate data
		$sqlParameters = array($this->channel->getId(), $this->aggType, $this->from);
		if (isset($aggFromDelta)) {
			// shift 'left' border of aggregate table use by $aggFromDelta units
			$sql = 'SELECT UNIX_TIMESTAMP(' .
				   'DATE_ADD(' .
					   'FROM_UNIXTIME(MIN(timestamp) / 1000, ' . $dateFormat . '), ' .
					   'INTERVAL ' . $aggFromDelta . ' ' . $this->aggLevel .
				   ')) * 1000 ' .
				   'FROM aggregate WHERE channel_id=? AND type=? AND ' .
				   '     UNIX_TIMESTAMP(FROM_UNIXTIME(timestamp / 1000, ' . $dateFormat . ')) * 1000 >=?';
		}
		else {
			// find 'left' border of aggregate table after $from
			$sql = 'SELECT UNIX_TIMESTAMP(FROM_UNIXTIME(MIN(timestamp) / 1000, ' . $dateFormat . ')) * 1000 ' .
				   'FROM aggregate WHERE channel_id=? AND type=? AND ' .
				   '     UNIX_TIMESTAMP(FROM_UNIXTIME(timestamp / 1000, ' . $dateFormat . ')) * 1000 >=?';
		}
		$this->aggFrom = $this->conn->fetchColumn($sql, $sqlParameters, 0);
		$this->aggTo = null;

		// aggregate table contains relevant data?
		if (isset($this->aggFrom)) {
			// aggTo becomes beginning of first period without aggregate data
			$sqlParameters = array($this->channel->getId(), $this->aggType);
			$sql = 'SELECT UNIX_TIMESTAMP(' .
				   'DATE_ADD(' .
						'FROM_UNIXTIME(MAX(timestamp) / 1000, ' . $dateFormat . '), ' .
						'INTERVAL 1 ' . $this->aggLevel .
				   ')) * 1000 ' .
				   'FROM aggregate WHERE channel_id=? AND type=?';
			if (isset($this->to)) {
				$sqlParameters[] = $this->to;
				$sql .= ' AND timestamp<?';
			}
			$this->aggTo = $this->conn->fetchColumn($sql, $sqlParameters, 0);
		}

		if (self::$debug) {
			printf("from ..              aggFrom             ..               aggTo                .. to\n");
			printf("%s |%s .. %s| %s\n", self::pd($this->from), self::pd($this->aggFrom), self::pd($this->aggFrom), self::pd($this->to));
		}

		return isset($this->aggFrom) && isset($this->aggTo) &&
			   $this->aggFrom < $this->aggTo &&
			   $this->from <= $this->aggFrom && $this->aggTo <= $this->to;
	}

	/**
	 * Print formatted date
	 */
	private static function pd($ts) {
		$date = \DateTime::createFromFormat('U', (int)($ts/1000))->setTimeZone(new \DateTimeZone('Europe/Berlin'));
		return $date->format('d.m.Y H:i:s');
	}

	/**
	 * Build sql query part to filter specified time interval
	 *
	 * Replaces Interpreter::buildDateTimeFilterSQL
	 *
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 * @param boolean $sequential use < operator instead of <= for time comparison at end of period
	 * @param string  $op initial concatenation operator
	 * @return string the sql part
	 */
	public static function buildDateTimeFilterSQL($from = NULL, $to = NULL, &$parameters, $sequential = false, $op = ' AND') {
		$sql = '';

		if (isset($from)) {
			$sql .= $op . ' timestamp >= ?';
			$parameters[] = $from;
		}

		if (isset($to)) {
			$sql .= (($sql) ? ' AND' : $op) . ' timestamp ' . (($sequential) ? '<' : '<=') . ' ?';
			$parameters[] = $to;
		}

		return $sql;
	}
}

?>
