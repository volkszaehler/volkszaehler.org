<?php
/**
 * Data aggregation utility
 *
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2013, The volkszaehler.org project
 * @package util
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
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

namespace Volkszaehler\Util;

use Volkszaehler\Util;
use Volkszaehler\Interpreter;
use Volkszaehler\Definition;
use Doctrine\DBAL;

class Aggregation {

	/*
	 * Aggregation modes
	 */
	public const MODE_FULL = 'full';
	public const MODE_DELTA = 'delta';

	/**
	 * @var \Doctrine\DBAL\Connection Database connection
	 */
	protected $conn;

	/**
	 * @var SQL aggregation types and assorted date formats
	 */
	protected static $aggregationLevels = array();

	/**
	 * @var Aggregation target
	 */
	protected $targetTable = 'aggregate';

	/**
	 * Initialize static variables
	 *
	 * @todo When changing order or this array the aggregation table must be rebuilt
	 */
	static function init() {
		self::$aggregationLevels = array(
			'second' => '"%Y-%m-%d %H:%i:%s"',	// type 0
			'minute' => '"%Y-%m-%d %H:%i:00"',	// type 1
			'hour' => 	'"%Y-%m-%d %H:00:00"',	// type 2
			'day' => 	'"%Y-%m-%d"',			// type 3
			'week' => 	null,					// type 4 - not supported
			'month' => 	'"%Y-%m-1"',			// type 5
			'year' => 	'"%Y-1-1"'				// type 6
		);
	}

	public function __construct(DBAL\Connection $conn) {
		$this->conn = $conn;
	}

	/**
	 * Get list of aggregation levels
	 *
	 * @param  string  $level aggregation level (e.g. 'day')
	 * @return boolean        validity
	 */
	public static function getAggregationLevels() {
		return array_keys(self::$aggregationLevels);
	}

	/**
	 * Test if aggregation level is valid and implemented
	 *
	 * @param  string  $level aggregation level (e.g. 'day')
	 * @return boolean        validity
	 */
	public static function isValidAggregationLevel($level) {
		return in_array($level, self::getAggregationLevels())
			&& (isset(self::$aggregationLevels[$level]));
	}

	/**
	 * Convert aggregation level to numeric type
	 *
	 * @param  string $level aggregation level (e.g. 'day')
	 * @return integer       aggregation level numeric value
	 */
	public static function getAggregationLevelTypeValue($level) {
		if (($type = array_search($level, self::getAggregationLevels(), true)) === false) {
			throw new \RuntimeException('Invalid aggregation level \'' . $level . '\'');
		};
		return($type);
	}

	/**
	 * SQL format for grouping data by aggregation level
	 *
	 * @param  string $level aggregation level (e.g. 'day')
	 * @return string        SQL date format
	 */
	public static function getAggregationDateFormat($level) {
		if (!self::isValidAggregationLevel($level)) {
			throw new \RuntimeException('Invalid aggregation level \'' . $level . '\'');
		}
		return self::$aggregationLevels[$level];
	}

	/**
	 * Simple optimizer - choose aggregation level with most data available
	 *
	 * @param  string  $targetLevel desired highest level (e.g. 'day')
	 * @return boolean list of valid aggregation levels
	 */
	public function getOptimalAggregationLevel($uuid, $targetLevel = null) {
		$levels = self::getAggregationLevels();

		$sqlParameters = array($uuid);
		$sql = 'SELECT aggregate.type, COUNT(aggregate.id) AS count ' .
			   'FROM aggregate INNER JOIN entities ON aggregate.channel_id = entities.id ' .
			   'WHERE uuid = ? ';
		if ($targetLevel) {
			$sqlParameters[] = self::getAggregationLevelTypeValue($targetLevel);
			$sql .= 'AND aggregate.type <= ? ';
		}
		$sql.= 'GROUP BY type ' .
			   'HAVING count > 0 ' .
			   'ORDER BY type DESC';

		$rows = $this->conn->fetchAll($sql, $sqlParameters);

		// append readable level name
		for ($i=0; $i<count($rows); $i++) {
			$rows[$i]['level'] = $levels[$rows[$i]['type']];
		}

		return count($rows) ? $rows : FALSE;
	}

	/**
	 * Remove aggregration data - either all or selected type
	 *
	 * @param  string $level aggregation level to remove data for
	 * @return int 			 number of affected rows
	 */
	public function clear($uuid = null, $level = 'all') {
		$sqlParameters = array();

		if ($level == 'all') {
			if ($uuid) {
				$sql = 'DELETE aggregate FROM aggregate ' .
					   'INNER JOIN entities ON aggregate.channel_id = entities.id ' .
					   'WHERE entities.uuid = ?';
				$sqlParameters[] = $uuid;
			}
			else {
				$sql = 'TRUNCATE TABLE aggregate';
			}
		}
		else {
			$sqlParameters[] = self::getAggregationLevelTypeValue($level);
			$sql = 'DELETE aggregate FROM aggregate ' .
				   'INNER JOIN entities ON aggregate.channel_id = entities.id ' .
				   'WHERE aggregate.type = ? ';
			if ($uuid) {
				$sql .= 'AND entities.uuid = ?';
				$sqlParameters[] = $uuid;
			}
		}

		if (Util\Debug::isActivated())
			echo(Util\Debug::getParametrizedQuery($sql, $sqlParameters)."\n");

		$rows = $this->conn->executeQuery($sql, $sqlParameters);
	}

	/**
	 * Create temporary aggregate table for rebuild
	 */
	public function startRebuild() {
		$this->conn->executeQuery('DROP TABLE IF EXISTS aggregate_temp');
		$this->conn->executeQuery('CREATE TABLE aggregate_temp LIKE aggregate');
		$this->targetTable = 'aggregate_temp';
	}

	/**
	 * Appy rebuild temporary aggregate table
	 */
	public function finishRebuild() {
		$this->conn->beginTransaction();
		$this->conn->executeQuery('DROP TABLE aggregate');
		$this->conn->executeQuery('RENAME TABLE aggregate_temp TO aggregate');
		$this->conn->commit();
		$this->targetTable = 'aggregate';
	}

	/**
	 * Core data aggregation
	 *
	 * @param  int 	  $channel_id  id of channel to perform aggregation on
	 * @param  string $interpreter interpreter class name
	 * @param  string $mode        aggregation mode (full, delta)
	 * @param  string $level       aggregation level (day...)
	 * @param  int 	  $period      delta days to aggregate
	 * @return int    number of rows
	 */
	protected function aggregateChannel($channel_id, $interpreter, $mode, $level, $period) {
		$format = self::getAggregationDateFormat($level);
		$type = self::getAggregationLevelTypeValue($level);

		$weighed_avg = ($interpreter == 'Volkszaehler\\Interpreter\\SensorInterpreter');

		$sqlParameters = array($type);
		$sql = 'REPLACE INTO ' . $this->targetTable . ' (channel_id, type, timestamp, value, count) ';

		if ($weighed_avg) {
			// get interpreter's aggregation function
			$aggregationFunction = $interpreter::groupExprSQL('agg.value');

			// max aggregated timestamp before current aggregation range
			$intialTimestamp = 'NULL';
			if ($mode == self::MODE_DELTA) {
				// since last aggregation only
				array_push($sqlParameters, $type, $channel_id);
				$intialTimestamp =
					'UNIX_TIMESTAMP(DATE_ADD(' .
					'FROM_UNIXTIME(MAX(timestamp) / 1000, ' . $format . '), ' .
					'INTERVAL 1 ' . $level . ')) * 1000 ' .
					'FROM aggregate ' .
					'WHERE type = ? AND aggregate.channel_id = ?';
			}
			elseif ($period) {
				// selected number of periods only
				array_push($sqlParameters, $type, $channel_id, $period);
				$intialTimestamp =
					'MAX(timestamp) FROM aggregate ' .
					'WHERE type = ? AND aggregate.channel_id = ? ' .
					'AND timestamp < UNIX_TIMESTAMP(DATE_SUB(DATE_FORMAT(NOW(), ' . $format . '), INTERVAL ? ' . $level . ')) * 1000';
			}

			// SQL query similar to MySQLOptimizer group mode
			$sql .=
				'SELECT channel_id, ? AS type, ' .
					'MAX(agg.timestamp) AS timestamp, ' .
					'COALESCE( ' .
						'SUM(agg.val_by_time) / (MAX(agg.timestamp) - MIN(agg.prev_timestamp)), ' .
						$aggregationFunction .
					') AS value, ' .
					'COUNT(agg.value) AS count ' .
				'FROM ( ' .
					'SELECT channel_id, timestamp, value, ' .
						'value * (timestamp - @prev_timestamp) AS val_by_time, ' .
						'GREATEST(0, IF(@prev_timestamp = NULL, NULL, @prev_timestamp)) AS prev_timestamp, ' .
						'@prev_timestamp := timestamp ' .
					'FROM data ' .
					'CROSS JOIN (SELECT @prev_timestamp := ' . $intialTimestamp . ') AS vars ' .
					'WHERE ';
		}
		else {
			// get interpreter's aggregation function
			$aggregationFunction = $interpreter::groupExprSQL('value');

			$sql .=
			   'SELECT channel_id, ? AS type, MAX(timestamp) AS timestamp, ' .
			   $aggregationFunction . ' AS value, COUNT(timestamp) AS count ' .
			   'FROM data WHERE ';
		}

		// selected channel only
		$sqlParameters[] = $channel_id;
		$sql .= 'channel_id = ? ';

		// since last aggregation only
		if ($mode == self::MODE_DELTA) {
			// timestamp at start of next period after last aggregated period
			array_push($sqlParameters, $type, $channel_id);
			$sql .=
			   'AND timestamp >= IFNULL((' .
				   'SELECT UNIX_TIMESTAMP(DATE_ADD(' .
						  'FROM_UNIXTIME(MAX(timestamp) / 1000, ' . $format . '), ' .
						  'INTERVAL 1 ' . $level . ')) * 1000 ' .
				   'FROM aggregate ' .
				   'WHERE type = ? AND aggregate.channel_id = ? ' .
			   '), 0) ';
		}

		// selected number of periods only
		elseif ($period) {
			$sqlParameters[] = $period;
			$sql .=
			   'AND timestamp >= (SELECT UNIX_TIMESTAMP(DATE_SUB(DATE_FORMAT(NOW(), ' . $format . '), INTERVAL ? ' . $level . ')) * 1000) ';
		}

		// up to before current period
		$sql .= 'AND timestamp < UNIX_TIMESTAMP(DATE_FORMAT(NOW(), ' . $format . ')) * 1000 ';

		if ($weighed_avg) {
			// close inner table
			$sql .= ') AS agg ';
		}

		$sql .= 'GROUP BY channel_id, ' . Interpreter\Interpreter::buildGroupBySQL($level);

		if (Util\Debug::isActivated())
			echo(Util\Debug::getParametrizedQuery($sql, $sqlParameters)."\n");

		$rows = $this->conn->executeUpdate($sql, $sqlParameters);

		return($rows);
	}

	/**
	 * Retrieve aggregatable entities as array of database rows
	 */
	public function getAggregatableEntitiesArray($uuid = null) {
		// get channel definition to select correct aggregation function
		$sqlParameters = array('channel');
		$sql = 'SELECT id, uuid, type FROM entities WHERE class = ?';
		if ($uuid) {
			$sqlParameters[] = $uuid;
			$sql .= ' AND uuid = ?';
		}

		return $this->conn->fetchAll($sql, $sqlParameters);
	}

	/**
	 * Core data aggregation wrapper
	 *
	 * @param  string $uuid   channel UUID
	 * @param  string $level  aggregation level (e.g. 'day')
	 * @param  string $mode   MODE_FULL or MODE_DELTA aggretation
	 * @param  int    $period number of prior periods to aggregate in delta mode
	 * @param  callable $progress progress callback
	 * @return int         	  number of affected rows
	 */
	public function aggregate($uuid = null, $level = 'day', $mode = self::MODE_FULL, $period = null, $progress = null) {

		// validate settings
		if (!in_array($mode, array(self::MODE_FULL, self::MODE_DELTA))) {
			throw new \RuntimeException('Unsupported aggregation mode ' . $mode);
		}
		if (!$this->isValidAggregationLevel($level)) {
			throw new \RuntimeException('Unsupported aggregation level ' . $level);
		}

		$total = 0;

		// aggregate each channel
		foreach ($this->getAggregatableEntitiesArray($uuid) as $row) {
			$entity = Definition\EntityDefinition::get($row['type']);
			$interpreter = $entity->getInterpreter();

			$rows = $this->aggregateChannel($row['id'], $interpreter, $mode, $level, $period);
			$total += $rows;

			if (is_callable($progress)) {
				$progress($rows);
			}
		}

		return($total);
	}
}

// initialize static variables
Aggregation::init();

?>
