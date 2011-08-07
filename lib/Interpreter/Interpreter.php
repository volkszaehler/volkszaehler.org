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
use Doctrine\ORM;

/**
 * Interpreter superclass for all interpreters
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
abstract class Interpreter {
	protected $channel;

	/**
	 * @var Database connection
	 */
	protected $conn;	// PDO connection handle

	protected $from;	// request parameters
	protected $to;		// can be NULL!
	protected $groupBy;	// user from/to from DataIterator for exact calculations!
	
	protected $rowCount;	// number of rows in the database
	protected $tupleCount;	// number of requested tuples
	protected $rows;	// DataIterator instance for aggregating rows
	
	protected $min = NULL;
	protected $max = NULL;

	/**
	 * Constructor
	 *
	 * @param Channel $channel
	 * @param EntityManager $em
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 */
	public function __construct(Model\Channel $channel, ORM\EntityManager $em, $from, $to, $tupleCount, $groupBy) {
		$this->channel = $channel;
		$this->groupBy = $groupBy;
		$this->tupleCount = $tupleCount;
		$this->from = $from;
		$this->to = $to;
		$this->conn = $em->getConnection(); // get dbal connection from EntityManager
		
		// parse interval
		if (isset($from)) {
			$this->from = self::parseDateTimeString($from, time() * 1000);
		}
		
		if (isset($to)) {
			$this->to = self::parseDateTimeString($to, (isset($this->from)) ? $this->from : time() * 1000);
		}

		if (isset($this->from) && isset($this->to) && $this->from > $this->to) {
			throw new \Exception('&from is larger than &to parameter');
		}
	}

	/**
	 * Get raw data
	 *
	 * @param string|integer $groupBy
	 * @return Volkszaehler\DataIterator
	 */
	protected function getData() {
		// get total row count for grouping
		$sqlParameters	= array($this->channel->getId());
		if ($this->groupBy && $sqlGroupFields = self::buildGroupBySQL($this->groupBy)) {
			$sqlRowCount	= 'SELECT COUNT(DISTINCT ' . $sqlGroupFields . ') FROM data WHERE channel_id = ?';
		}
		else {
			$sqlRowCount	= 'SELECT COUNT(*) FROM data WHERE channel_id = ?' . self::buildDateTimeFilterSQL($this->from, $this->to, $sqlParameters);
		}
		$this->rowCount = $this->conn->fetchColumn($sqlRowCount, $sqlParameters, 0);
		
		
		// get data
		$sqlParameters	= array();
		if ($this->groupBy && $sqlGroupFields = self::buildGroupBySQL($this->groupBy)) {
			$sql		= 'SELECT MAX(timestamp) AS timestamp, SUM(value) AS value, COUNT(timestamp) AS count
						FROM data
						WHERE channel_id = ?' .
						self::buildDateTimeFilterSQL($this->from, $this->to, $sqlParameters) .
						' GROUP BY ' . $sqlGroupFields;
		}
		else {
			$sql		= '';
			
			$sqlComon	= 'SELECT timestamp, value, 1 AS count FROM data WHERE channel_id = ?';
			$threshold	= 60 * 1000; // 1 minute
			
			if (isset($this->from)) {
				$sql .= '(' . $sqlComon  . ' AND timestamp BETWEEN ? AND ? ORDER BY timestamp DESC LIMIT 2) UNION ALL ';
				array_push($sqlParameters, $this->channel->getId(), $this->from - $threshold, $this->from);
			}

			$sqlParameters[] = $this->channel->getId();
			$sql		.= '(' . $sqlComon . self::buildDateTimeFilterSQL($this->from, $this->to, $sqlParameters) . ')';
			
			if (isset($this->to)) {
				$sql .=' UNION ALL (' . $sqlComon . ' AND timestamp BETWEEN  ? AND ? ORDER BY timestamp ASC LIMIT 2)';
				array_push($sqlParameters, $this->channel->getId(), $this->to, $this->to + $threshold);
			}
			
			$sql 		.= ' ORDER BY timestamp ASC';
		}
		
		if ($this->rowCount > 0) {
			$stmt = $this->conn->executeQuery($sql, $sqlParameters); // query for data

			return new DataIterator($stmt, $this->rowCount, $this->tupleCount);
		}
		else { // no data available
			return new \EmptyIterator();
		}
	}

	/**
	 * Builds sql query part for grouping data by date functions
	 *
	 * @param string $groupBy
	 * @return string the sql part
	 * @todo make compatible with: MSSql (Transact-SQL), Sybase, Firebird/Interbase, IBM, Informix, MySQL, Oracle, DB2, PostgreSQL, SQLite
	 */
	protected static function buildGroupBySQL($groupBy) {
		$ts = 'FROM_UNIXTIME(timestamp/1000)';	// just for saving space

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
	 * Build sql query part to filter specified time interval
	 *
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 * @return string the sql part
	 */
	protected static function buildDateTimeFilterSQL($from = NULL, $to = NULL, &$parameters) {
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
	 * @param string $ts string to parse
	 * @param float $now in ms since 1970
	 * @return float
	 */
	protected static function parseDateTimeString($string, $now) {
		if (ctype_digit($string)) { // handling as ms timestamp
			return (float) $string;
		}
		elseif ($ts = strtotime($string, $now / 1000)) {
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
	public function getTupleCount() { return $this->tupleCount; }
	public function setTupleCount($count) { $this->tupleCount = $count; }
	public function getFrom() { return ($this->rowCount > 0) ? $this->rows->getFrom() : NULL; }
	public function getTo() { return ($this->rowCount > 0) ? $this->rows->getTo() : NULL; }
}

?>
