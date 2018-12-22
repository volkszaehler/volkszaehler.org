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
 * SQLOptimizer is the base class for DB-specific optimizations
 */
abstract class SQLOptimizer {

	/** @var Interpreter\Interpreter */
	protected $interpreter;
	/** @var Model\Channel */
	protected $channel;
	/** @var DBAL\Connection */
	protected $conn;

	protected $from;
	protected $to;
	protected $tupleCount;
	protected $groupBy;

	/**
	 * Static factory method
	 *
	 * @return string SQL optimizer class name for DBMS
	 */
	public static function staticFactory() {
		// optimizer defined in config file
		if (null !== ($class = Util\Configuration::read('db.optimizer'))) {
			return $class;
		}

		switch (Util\Configuration::read('db.driver')) {
			case 'pdo_mysql':
			case 'mysqli':
				if (Util\Configuration::read('aggregation')) {
					$class = MySQLAggregateOptimizer::class;
				}
				else {
					$class = MySQLOptimizer::class;
				}
				break;
			case 'pdo_sqlite':
				$class = SQLiteOptimizer::class;
				break;
			case 'pdo_pgsql':
				$class = PostgreSQLOptimizer::class;
				break;
			default:
				$class = __CLASS__;
		}
		return $class;
	}

	/**
	 * Constructor
	 *
	 * @param  Interpreter      $interpreter
	 */
	public function __construct(Interpreter\Interpreter $interpreter) {
		$this->interpreter = $interpreter;

		// get interpreter properties
		$this->channel = $interpreter->getEntity();
		$this->conn = $interpreter->getConnection();
		$this->from = $interpreter->getOriginalFrom();
		$this->to = $interpreter->getOriginalTo();
		$this->tupleCount = $interpreter->getTupleCount();
		$this->groupBy = $interpreter->getGroupBy();
	}

	/**
	 * DB-specific data grouping by date functions.
	 * Static call is delegated to implementing classes.
	 * Called by Interpreter->buildGroupBySQL
	 *
	 * @param string $groupBy
	 * @return string the sql part
	 */
	abstract public static function buildGroupBySQL($groupBy);

	/**
	 * DB-specific cross-database join table delete statements
	 *
	 * Except MySQL: DELETE FROM aggregate WHERE id in (SELECT id FROM aggregate)
	 *
	 * @param string $table table name
	 * @param string $join join table name
	 * @param string $id id column name
	 * @return string the sql part
	 */
	public static function buildDeleteFromJoinSQL($table, $join, $id = 'id') {
		$sql = sprintf("DELETE FROM %s WHERE %s.%s in (SELECT %s FROM %s)", [
			$table,
			$table,
			$id,
			$id,
			$join
		]);
		return $sql;
	}

	/**
	 * Build sql query part to filter specified time interval
	 *
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 * @param boolean $sequential use < operator instead of <= for time comparison at end of period
	 * @param string  $op initial concatenation operator
	 * @return string sql part including leading operator (' AND ')
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

	/**
	 * Combine operation => value array into SQL filter
	 *
	 * @param array $filter associative array of filters
	 * @param array $params output prarameter array
	 * @param string $valueParam name of value column
	 * @return string sql part including leading operator (' AND ')
	 */
	public static function buildValueFilterSQL(array $filters, &$params, $valueParam = 'value') {
		$sql = '';

		foreach ($filters as $op => $value) {
			if ($sql) $sql .= ' AND ';
			$sql .= $valueParam . $op . '?';
			$params[] = $value;
		}

		return ($sql) ? ' AND ' . $sql : '';
	}

	/**
	 * Called by interpreter before counting result rows
	 *
	 * @param  string $sqlRowCount   initial SQL query
	 * @param  string $sqlParameters initial SQL parameters
	 * @return boolean               optimization result
	 */
	public function optimizeRowCountSQL(&$sqlRowCount, &$sqlParameters) {
		// not implemented
		return false;
	}

	/**
	 * Calculate if binary tuple packaging can be used
	 * Updates tupleCount to prevent double packaging
	 *
	 * @param  string $rowCount 	 actual number of rows expected from count sql
	 * @return array [$bitshift,$timestampOffset] parameters for binary tuple packaging
	 */
	protected function applyBinaryTuplePackaging($rowCount) {
		if ($this->tupleCount && ($rowCount > $this->tupleCount)) {
			// use power of 2 instead of division for performance
			$bitShift = (int) floor(log(($this->to - $this->from) / $this->tupleCount, 2));

			if ($bitShift > 0) { // worth doing -> go
				// ensure first tuple consumes only record
				$packageSize = 1 << $bitShift;
				$timestampOffset = $this->from - $packageSize + 1;

				// prevent DataIterator from further packaging
				// unless exactly one tuple is requested
				if ($this->tupleCount != 1) $this->interpreter->setTupleCount(null);

				return array($bitShift, $timestampOffset);
			}
		}

		return false;
	}

	/**
	 * Called by interpreter before retrieving result rows
	 *
	 * @param  string $sql  		 initial SQL query
	 * @param  string $sqlParameters initial SQL parameters
	 * @param  string $rowCount 	 actual number of rows expected from count sql
	 * @return boolean               optimization result
	 */
	public function optimizeDataSQL(&$sql, &$sqlParameters, $rowCount) {
		// potential to reduce result set - can't do this for already grouped SQL
		if ($this->groupBy)
			return false;

		// perform tuple packaging in SQL
		if (list($bitShift, $timestampOffset) = $this->applyBinaryTuplePackaging($rowCount)) {
			// optimize packaging statement
			$foo = array();
			$sqlTimeFilter = self::buildDateTimeFilterSQL($this->from, $this->to, $foo);

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

			return true;
		}

		return false;
	}

	/**
	 * Disable SQL statement caching
	 */
	public function disableCache() {
		throw new \RuntimeException('Disabling caching not implemented for current DBMS');
	}
}

?>
