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

use Volkszaehler\Util;
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
abstract class Interpreter implements \Iterator {

	/**
	 * @var Database connection
	 */
	protected $conn;		// PDO connection handle

	protected $optimizer;	// db-specific SQL implementation and optmization

	protected $from;		// request parameters
	protected $to;			// can be NULL!
	protected $groupBy;		// user from/to from DataIterator for exact calculations!
	protected $options;  	// additional non-standard options
	protected $raw;  		// raw database values requested

	protected $channel;		// Channel entity

	protected $rowCount;	// number of rows in the database
	protected $tupleCount;	// number of requested tuples
	protected $rows;		// DataIterator instance for aggregating rows

	protected $key; 		// result interator index

	protected $min = NULL;
	protected $max = NULL;

	protected $scale;		// unit scale from entity definition
	protected $resolution;	// interpreter resolution from entity definition

	/**
	 * Constructor
	 *
	 * @param Channel $channel
	 * @param EntityManager $em
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 */
	public function __construct(Model\Channel $channel, ORM\EntityManager $em, $from, $to, $tupleCount = null, $groupBy = null, $options = array()) {
		$this->channel = $channel;
		$this->groupBy = $groupBy;
		$this->tupleCount = $tupleCount;
		$this->options = $options;

		// client wants raw data?
		$this->raw = $this->hasOption('raw');

		// get dbal connection from EntityManager
		$this->conn = $em->getConnection();

		// store channel scale and resolution locally for performance
		$this->scale = $this->channel->getDefinition()->scale;
		$this->resolution = ($this->channel->hasProperty('resolution')) ? $this->channel->getProperty('resolution') : 1;

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
	}

	/**
	 * Convert raw meter readings.
	 * This function will have side effects on internal member variables of the interpreter.
	 */
	abstract public function convertRawTuple($row);

	/**
	 * Iterator functions
	 */

	abstract public function rewind();

	abstract public function current();

	public function next() {
		$this->key++;
		$this->rows->next();
	}

	public function valid() {
		return $this->rows->valid();
	}

	public function key() {
		return $this->key;
	}

	/**
	 * Check if option is specified
	 *
	 * @param  string  $str option name
	 */
	protected function hasOption($str) {
		return in_array($str, $this->options);
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
	 * Get raw data
	 *
	 * @param string|integer $groupBy
	 * @return Volkszaehler\DataIterator
	 */
	protected function getData() {
		if (!$this->hasOption('exact')) {
			// get timestamps of preceding and following data points as a graciousness
			// for the frontend to be able to draw graphs to the left and right borders
			if (isset($this->from)) {
				$sql = 'SELECT MAX(timestamp) FROM data WHERE channel_id=? AND timestamp < (SELECT MAX(timestamp) FROM data WHERE channel_id=? AND timestamp<?)';
				// $sql = 'SELECT IFNULL(' .
				// 			'SELECT MAX(timestamp) FROM data WHERE channel_id=? AND timestamp < (SELECT MAX(timestamp) FROM data WHERE channel_id=? AND timestamp<?), ' .
				// 			'SELECT MAX(timestamp) FROM data WHERE channel_id=? AND timestamp<?' .
				// 	   ')';

				// if not second-highest timestamp take highest before $this->from
				if (null === $from = $this->conn->fetchColumn($sql, array($this->channel->getId(), $this->channel->getId(), $this->from), 0)) {
					$sql = 'SELECT MAX(timestamp) FROM data WHERE channel_id=? AND timestamp<?';
					$from = $this->conn->fetchColumn($sql, array($this->channel->getId(), $this->from), 0);
				}

				if ($from)
					$this->from = (double)$from; // bigint conversion
			}
			if (isset($this->to)) {
				$sql = 'SELECT MIN(timestamp) FROM data WHERE channel_id=? AND timestamp>?';
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
	 * @todo add millisecond resolution
	 *
	 * @param mixed $string int, float or string to parse
	 * @param float $now in ms since 1970
	 * @return float
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
