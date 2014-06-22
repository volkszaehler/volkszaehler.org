<?php
/**
 * @copyright Copyright (c) 2011, The volkszaehler.org project
 * @package default
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
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

namespace Volkszaehler\Interpreter;

/**
 * Sensor interpreter
 *
 * @package default
 * @author Steffen Vogel <info@steffenvogel.de>
 */
use Volkszaehler\Util;

class SensorInterpreter extends Interpreter {

	protected $consumption; // in Wms (Watt milliseconds)
	protected $resolution;

	/**
	 * Create the SQL statements for flat and grouped data retrieval and counting rows
	 *
	 * SensorInterpreter requires special treatment to calculate weighed average
	 *
	 * @param  array  $sqlParameters array of parameters
	 * @param  string $sql           data retrieval SQL
	 * @param  string $sqlRowCount   row count SQL
	 */
	protected function buildSQLStatements(&$sqlParameters, &$sql, &$sqlRowCount) {
		if ($this->groupBy) {
			// common conditions for following SQL queries
			$sqlParameters = array($this->channel->getId());
			$sqlTimeFilter = self::buildDateTimeFilterSQL($this->from, $this->to, $sqlParameters);

			$sqlGroupFields = self::buildGroupBySQL($this->groupBy);
			if (!$sqlGroupFields)
				throw new \Exception('Unknown group');

			$sqlRowCount = 'SELECT COUNT(DISTINCT ' . $sqlGroupFields . ') FROM data WHERE channel_id = ?' . $sqlTimeFilter;

			// special query for SensorInterpreter
			$sql = 'SELECT MAX(agg.timestamp) AS timestamp, ' .
						  'COALESCE( ' .
							  'SUM(agg.val_by_time) / (MAX(agg.timestamp) - MIN(agg.prev_timestamp)), ' .
							  self::groupExprSQL('agg.value') .
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
					   'WHERE collect.channel_id=? ' . str_replace('timestamp', 'collect.timestamp', $sqlTimeFilter) . ' ' .
					   'ORDER BY collect.timestamp' .
				   ') AS agg ' .
				   'GROUP BY ' . self::buildGroupBySQL($this->groupBy) . ' ' .
				   'ORDER BY timestamp ASC';
		}
		else {
			parent::buildSQLStatements($sqlParameters, $sql, $sqlRowCount);
		}
	}

	/**
	 * Calculates the consumption
	 *
	 * @return float total consumption in Wh
	 */
	public function getConsumption() {
		return $this->channel->getDefinition()->hasConsumption ? $this->consumption / 3.6e6 : NULL; // convert to Wh
	}

	/**
	 * Get Average
	 *
	 * @return float average
	 */
	public function getAverage() {
		if ($this->consumption) {
			$delta = $this->getTo() - $this->getFrom();
			return $this->consumption / $delta;
		}
		else { // prevents division by zero
			return 0;
		}
	}

	public function processData($callback) {
		$tuples = array();
		$this->rows = $this->getData();

		// in case of SensorIntepreter resolution is optional
		$this->resolution = ($this->channel->hasProperty('resolution')) ?
			$this->channel->getProperty('resolution') : 1;

		$ts_last = $this->getFrom();
		foreach ($this->rows as $row) {
			$delta_ts = $row[0] - $ts_last;

			// instead of using $row[1], which is value, get weighed average value from $row[4] which
			// DataIterator->next provides as courtesy
			// otherwise the default, non-optimized tuple packaging SQL statement will yield incorrect results
			// due to non-equidistant timestamps
			$tuple = $callback(array(
				(float) ($ts_last = $row[0]),	// timestamp of interval end
				(float) $row[4] / $this->resolution,
				(int) $row[2]
			));

			if (is_null($this->max) || $tuple[1] > $this->max[1]) {
				$this->max = $tuple;
			}

			if (is_null($this->min) || $tuple[1] < $this->min[1]) {
				$this->min = $tuple;
			}

			$this->consumption += $tuple[1] * $delta_ts;

			$tuples[] = $tuple;
		}

		return $tuples;
	}

	/**
	 * Return sql grouping expression
	 *
	 * Override Interpreter->groupExpr
	 *
	 * @author Andreas Götz <cpuidle@gmx.de>
	 * @param string $expression sql parameter
	 * @return string grouped sql expression
	 */
	public static function groupExprSQL($expression) {
		return 'AVG(' . $expression . ')';
	}
}

?>
