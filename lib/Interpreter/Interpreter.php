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
	protected $conn;

	protected $from;
	protected $to;
	protected $groupBy;
	
	protected $rowCount;
	protected $tupleCount;

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
		// prepare sql
		$sqlWhere	= ' WHERE channel_id = ?';

		if ($this->groupBy && $sqlGroupFields = self::buildGroupBySQL($this->groupBy)) {
			$sqlRowCount	= 'SELECT COUNT(DISTINCT ' . $sqlGroupFields . ') FROM data' . $sqlWhere;
			$sqlFields	= ' MAX(timestamp) AS timestamp, SUM(value) AS value, COUNT(timestamp) AS count';
			
			$sql		= 'SELECT' . $sqlFields . '
						FROM data' .
						$sqlWhere .
						self::buildDateTimeFilterSQL($this->from, $this->to) .
						' GROUP BY ' . $sqlGroupFields;
						
			$sqlParameters	= array($this->channel->getId());
		}
		else {
			$sqlRowCount	= 'SELECT COUNT(*) FROM data' . $sqlWhere . self::buildDateTimeFilterSQL($this->from, $this->to);
			$sqlComon	= 'SELECT timestamp, value, 1 FROM data' . $sqlWhere;
			
			$sqlFirst	= '(' . $sqlComon  . ' AND timestamp <= ' . $this->from . ' ORDER BY timestamp DESC LIMIT 1) UNION ';
			$sqlMiddle	= '(' . $sqlComon . self::buildDateTimeFilterSQL($this->from, $this->to) . ' ORDER BY timestamp ASC)';
			$sqlLast	= ' UNION (' . $sqlComon . ' AND timestamp >= ' . $this->to . ' ORDER BY timestamp ASC LIMIT 2)'; // we need 2 tuples in MeterInterpreter
			
			$sql = ((isset($this->from)) ? $sqlFirst : '') . $sqlMiddle . ((isset($this->to)) ? $sqlLast : '');
			$sqlParameters	= array_fill(0, 1 + isset($this->from) + isset($this->to), $this->channel->getId());
		}
		
		// get total row count for grouping
		$this->rowCount = $this->conn->fetchColumn($sqlRowCount, array($this->channel->getId()), 0);
		
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
	protected static function buildDateTimeFilterSQL($from = NULL, $to = NULL) {
		$sql = '';

		if (isset($from)) {
			$sql .= ' AND timestamp >= ' . $from;
		}

		if (isset($to)) {
			$sql .= ' AND timestamp <= ' . $to;
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
			throw new \Exception('Invalid time format: ' . $string);
		}
	}

	/*
	 * Getter & setter
	 */

	public function getEntity() { return $this->channel; }
	public function getRowCount() { return $this->rowCount; }
	public function getTupleCount() { return $this->tupleCount; }
	public function setTupleCount($count) { $this->tupleCount = $count; }
	public function getFrom() { return $this->from; }
	public function getTo() { return $this->to; }
}

?>
