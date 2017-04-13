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

	protected $interpreter;
	protected $conn;

	protected $from;
	protected $to;
	protected $groupBy;

	/**
	 * Factory method
	 *
	 * @param  InterpreterInterpreter $interpreter
	 * @param  ModelChannel           $channel
	 * @param  DBALConnection         $conn
	 * @return SQL\SQLOptimizer 	  instantiated class or false
	 */
	public static function factory() {
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

	public function __construct(Interpreter\Interpreter $interpreter, DBAL\Connection $conn) {
		$this->interpreter = $interpreter;
		$this->conn = $conn;
	}

	/**
	 * Gives access to hidden interpreter properties
	 */
	public function setParameters($from, $to, $tupleCount, $groupBy) {
		$this->from = $from;
		$this->to = $to;
		$this->tupleCount = $tupleCount;
		$this->groupBy = $groupBy;
	}

	/**
	 * Proxy magic. Transparently access public interpreter properties
	 * Keeps the code portable between Interpreter and SQLOptimizer
	 */
	public function __get($property) {
		if ($property == 'channel') {
			return $this->interpreter->getEntity();
		}
		elseif ($property == 'rowCount') {
			return $this->interpreter->getRowCount();
		}
		elseif ($property == 'tupleCount') {
			return $this->interpreter->getTupleCount();
		}
 		else {
   			throw new \Exception('Invalid property access: \'' . $property . '\'');
   		}
	}

	/**
	 * Proxy magic. Transparently access public interpreter properties
	 * Keeps the code portable between Interpreter and SQLOptimizer
	 */
	public function __set($property, $value) {
		if ($property == 'rowCount') {
			return $this->interpreter->setRowCount($value);
		}
		elseif ($property == 'tupleCount') {
			return $this->interpreter->setTupleCount($value);
		}
 		else {
   			throw new \Exception('Invalid property access: \'' . $property . '\'');
   		}
	}

	/**
	 * DB-specific data grouping by date functions.
	 * Static call is delegated to implementing classes.
	 * Called by Interpreter->buildGroupBySQL
	 *
	 * @param string $groupBy
	 * @return string the sql part
	 */
	public static function buildGroupBySQL($groupBy) {
		// fall back on default implementation
		return MySQLOptimizer::buildGroupBySQL($groupBy);
	}

	/**
	 * DB-specific cross-database join table delete statements
	 *
	 * Except MySQL: DELETE FROM aggregate WHERE id in (SELECT id FROM aggregate)
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
	 * Called by interpreter before retrieving result rows
	 *
	 * @param  string $sql  		 initial SQL query
	 * @param  string $sqlParameters initial SQL parameters
	 * @return boolean               optimization result
	 */
	public function optimizeDataSQL(&$sql, &$sqlParameters) {
		// potential to reduce result set - can't do this for already grouped SQL
		if ($this->groupBy)
			return false;

		// perform tuple packaging in SQL
		if ($this->tupleCount && ($this->rowCount > $this->tupleCount)) {
			// use power of 2 instead of division for performance
			$bitShift = (int) floor(log(($this->to - $this->from) / $this->tupleCount, 2));

			if ($bitShift > 0) { // worth doing -> go
				// ensure first tuple consumes only record
				$packageSize = 1 << $bitShift;
				$timestampOffset = $this->from - $packageSize + 1;

				// prevent DataIterator from further packaging
				// unless exactly one tuple is requested
				if ($this->tupleCount !== 1) $this->tupleCount = null;

				// optimize packaging statement
				$foo = array();
				$sqlTimeFilter = $this->interpreter->buildDateTimeFilterSQL($this->from, $this->to, $foo);

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
