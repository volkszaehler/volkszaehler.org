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

use Volkszaehler\Model;
use Volkszaehler\Interpreter\SQL;
use Doctrine\ORM;

/**
 * Interpreter superclass for all interpreters
 *
 * @package default
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Götz <cpuidle@gmx.de>
 */
abstract class Interpreter implements \IteratorAggregate {

	// output types
	const ACTUAL_VALUES = 0;
	const RAW_VALUES = 1;
	const CONSUMPTION_VALUES = 2;

	/**
	 * @var Database connection
	 */
	protected $conn;		// PDO connection handle

	protected $optimizer;	// db-specific SQL implementation and optmization

	protected $from;		// request parameters
	protected $to;			// can be NULL!
	protected $groupBy;		// user from/to from DataIterator for exact calculations!
	protected $options;  	// additional non-standard options

	protected $output;		// output type: actual, raw or consumption values

	protected $channel;		// Channel entity
	protected $definition;	// entity definition for channel

	protected $rowCount;	// number of rows in the database
	protected $tupleCount;	// number of requested tuples
	protected $rows;		// DataIterator instance for aggregating rows

	protected $min = NULL;
	protected $max = NULL;

	protected $scale;		// unit scale from entity definition
	protected $resolution;	// interpreter resolution from entity definition

	/**
	 * Constructor
	 *
	 * @param Model\Channel $channel
	 * @param ORM\EntityManager $em
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 * @param null $tupleCount
	 * @param null $groupBy
	 * @param array $options
	 * @throws \Exception
	 */
	public function __construct(Model\Channel $channel, ORM\EntityManager $em, $from, $to, $tupleCount = null, $groupBy = null, $options = array()) {
		$this->channel = $channel;
		$this->groupBy = $groupBy;
		$this->tupleCount = $tupleCount;
		$this->options = $options;

		// store channel properties locally for performance
		$this->definition = $this->channel->getDefinition();
		$this->scale = $this->definition->scale;
		$this->resolution = ($this->channel->hasProperty('resolution')) ? $this->channel->getProperty('resolution') : 1;

		// output type - default is ACTUAL_VALUES
		if ($this->hasOption('raw')) {
			$this->output = self::RAW_VALUES;
		}
		if ($this->hasOption('consumption')) {
			if ($this->output == self::RAW_VALUES) {
				throw new \Exception('Cannot use `raw` and `consumption` options together');
			}
			if (!$this->definition->hasConsumption) {
				throw new \Exception('Channel does not supply consumption data');
			}
			$this->output = self::CONSUMPTION_VALUES;
		}

		// get dbal connection from EntityManager
		$this->conn = $em->getConnection();

		// parse interval
		if (isset($to))
			$this->to = self::parseDateTimeString($to);

		if (isset($from))
			$this->from = self::parseDateTimeString($from);
		else
			$this->from = ($this->to ? $this->to : time()*1000) - 24*60*60*1000; // default: "to" or now minus 24h

		if (isset($this->from) && isset($this->to) && $this->from > $this->to) {
			throw new \Exception('From is larger than to');
		}

		// add db-specific SQL optimizations
		$class = SQL\SQLOptimizer::factory();
		$this->optimizer = new $class($this, $this->conn);

		if ($this->hasOption('nocache')) {
			$this->optimizer->disableCache();
		}
	}

	/*
	 * IteratorAggregate functions
	 */

	/**
	 * Generate database tuples
	 *
	 * @todo with wide-spread availability of PHP7 consider moving the raw value iteration here:
	 * 		yield from ($this->raw) ? $this->getRawIterator() : $this->getDefaultIterator();
	 *
	 * @return \Generator
	 */
	abstract public function getIterator();

	/*
	 * Convert raw meter readings
	 *
	 * This function will have side effects on internal member variables of the interpreter
	 */
	abstract public function convertRawTuple($row);

	/**
	 * Check if option is specified
	 *
	 * @param  string $str option name
	 * @return bool
	 */
	protected function hasOption($str) {
		return in_array($str, $this->options);
	}

	/**
	 * Update min max based on current tuple
	 *
	 * @param  array  $tuple
	 */
	protected function updateMinMax($tuple) {
		if (is_null($this->max) || $tuple[1] > $this->max[1]) {
			$this->max = $tuple;
		}

		if (is_null($this->min) || $tuple[1] < $this->min[1]) {
			$this->min = $tuple;
		}
	}

	/**
	 * Get minimum
	 *
	 * @return array (0 => timestamp, 1 => value)
	 */
	public function getMin() {
		return ($this->min) ? array_map('floatval', array_slice($this->min, 0 , 2)) : NULL;
	}

	/**
	 * Get maximum
	 *
	 * @return array (0 => timestamp, 1 => value)
	 */
	public function getMax() {
		return ($this->max) ? array_map('floatval', array_slice($this->max, 0 , 2)) : NULL;
	}

	/**
	 * Get raw data from database
	 *
	 * @return DataIterator
	 * @throws \Exception
	 */
	protected function getData() {
		if (!$this->hasOption('exact')) {
			// get timestamps of preceding and following data points as a graciousness
			// for the frontend to be able to draw graphs to the left and right borders
			if (isset($this->from)) {
				$sql = 'SELECT MAX(timestamp) FROM data WHERE channel_id=? AND timestamp < (SELECT MAX(timestamp) FROM data WHERE channel_id=? AND timestamp<?)';

				// if not second-highest timestamp take highest before $this->from
				if (null === $from = $this->conn->fetchColumn($sql, array($this->channel->getId(), $this->channel->getId(), $this->from), 0)) {
					$sql = 'SELECT MAX(timestamp) FROM data WHERE channel_id=? AND timestamp<?';
					$from = $this->conn->fetchColumn($sql, array($this->channel->getId(), $this->from), 0);
				}

				if ($from)
					$this->from = (double)$from; // bigint conversion
			}

			if (isset($this->to)) {
				// avoid generating timestamps outside the requested range for consumption
				$sql = $this->hasOption('consumption')
					? 'SELECT MAX(timestamp) FROM data WHERE channel_id=? AND timestamp<?'
					: 'SELECT MIN(timestamp) FROM data WHERE channel_id=? AND timestamp>?';
				$to = $this->conn->fetchColumn($sql, array($this->channel->getId(), $this->to), 0);
				if ($to)
					$this->to = (double)$to; // bigint conversion
			}
		}

		// set parameters; repeat if modified after setting
		$this->optimizer->setParameters($this->from, $this->to, $this->tupleCount, $this->groupBy);

		// common conditions for following SQL queries
		$sqlParameters = array($this->channel->getId());
		$sqlTimeFilter = self::buildDateTimeFilterSQL($this->from, $this->to, $sqlParameters);

		if ($this->groupBy) {
			$sqlGroupFields = self::buildGroupBySQL($this->groupBy);
			if (!$sqlGroupFields)
				throw new \Exception('Unknown group');

			$sqlRowCount = 'SELECT COUNT(DISTINCT ' . $sqlGroupFields . ') FROM data WHERE channel_id = ?' . $sqlTimeFilter;
			$sql = 'SELECT MAX(timestamp) AS timestamp, ' . static::groupExprSQL('value') . ' AS value, COUNT(timestamp) AS count ' .
				   'FROM data ' .
				   'WHERE channel_id = ?' . $sqlTimeFilter . ' ' .
				   'GROUP BY ' . $sqlGroupFields . ' ' .
				   'ORDER BY timestamp ASC';
		}
		else {
			$sqlRowCount = 'SELECT COUNT(1) FROM data WHERE channel_id = ?' . $sqlTimeFilter;
			$sql = 'SELECT timestamp, value, 1 AS count FROM data WHERE channel_id=?' . $sqlTimeFilter . ' ORDER BY timestamp ASC';
		}

		// optimize sql
		$sqlParametersRowCount = $sqlParameters;
		if (!$this->hasOption('slow')) {
			$this->optimizer->optimizeRowCountSQL($sqlRowCount, $sqlParametersRowCount);
		}

		$this->rowCount = (int) $this->conn->fetchColumn($sqlRowCount, $sqlParametersRowCount, 0);

		if ($this->rowCount <= 0)
			return new \EmptyIterator();

		// optimize sql
		if (!$this->hasOption('slow')) {
			$this->optimizer->optimizeDataSQL($sql, $sqlParameters);
		}

		// run query
		$stmt = $this->conn->executeQuery($sql, $sqlParameters);

		return new DataIterator($stmt, $this->rowCount, $this->tupleCount);
	}

	/**
	 * Return sql grouping expression
	 *
	 * Child classes must implement this method
	 *
	 * @author Andreas Götz <cpuidle@gmx.de>
	 * @param string $expression sql parameter
	 * @return string grouped sql expression
	 * @throws \Exception
	 */
	public static function groupExprSQL($expression) {
		throw new \Exception('Derived classes must implement static function groupExprSQL.');
	}

	/**
	 * Builds sql query part for grouping data by date functions
	 *
	 * @param string $groupBy
	 * @return string the sql part
	 */
	public static function buildGroupBySQL($groupBy) {
		// call db-specific version
		return SQL\SQLOptimizer::buildGroupBySQL($groupBy);
	}

	/**
	 * Build sql query part to filter specified time interval
	 *
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 * @return string the sql part
	 */
	public static function buildDateTimeFilterSQL($from = NULL, $to = NULL, &$parameters) {
		$sql = '';

		if (isset($from)) {
			$sql .= ' AND timestamp >= ?';
			$parameters[] = $from;
		}

		if (isset($to)) {
			$sql .= ' AND timestamp <= ?';
			$parameters[] = $to;
		}

		return $sql;
	}

	/**
	 * Parses a timestamp
	 *
	 * @link http://de3.php.net/manual/en/datetime.formats.php
	 *
	 * @param mixed $string int, float or string to parse
	 * @return float
	 * @throws \Exception
	 */
	public static function parseDateTimeString($string) {
		if (ctype_digit((string)$string)) { // handling as ms timestamp
			return (float) $string;
		}
		elseif ($ts = strtotime($string)) {
			return $ts * 1000;
		}
		else {
			throw new \Exception('Invalid time format: \'' . $string . '\'');
		}
	}

	/**
	 * Calculates the average consumption
	 *
	 * @return float average consumption in Wh
	 */
	public function getAverageConsumption() {
		if (($consumption = $this->getConsumption()) && ($rows = $this->getRowCount()) > 0) {
			return $consumption / $rows;
		}
		return 0;
	}

	/*
	 * Getter & setter
	 */

	public function getEntity() { return $this->channel; }
	public function getRowCount() { return $this->rowCount; }
	public function setRowCount($count) { $this->rowCount = $count; }
	public function getTupleCount() { return $this->tupleCount; }
	public function setTupleCount($count) { $this->tupleCount = $count; }
	public function getFrom() { return ($this->rowCount > 0) ? $this->rows->getFrom() : NULL; }
	public function getTo() { return ($this->rowCount > 0) ? $this->rows->getTo() : NULL; }
}

?>
