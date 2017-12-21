<?php
/**
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2017, The volkszaehler.org project
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

namespace Volkszaehler\Interpreter\SQL;

use Volkszaehler\Interpreter;
use Volkszaehler\Util;
use Volkszaehler\Model;
use Doctrine\DBAL;

/**
 * SQLOptimizer is the base class for DB-specific optimizations
 */
class SQLOptimizer {

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
					$class = __NAMESPACE__ . '\MySQLAggregateOptimizer';
				}
				else {
					$class = __NAMESPACE__ . '\MySQLOptimizer';
				}
				break;
			case 'pdo_sqlite':
				$class = __NAMESPACE__ . '\SQLiteOptimizer';
				break;
			case 'pdo_pgsql':
				$class = __NAMESPACE__ . '\PostgreSQLOptimizer';
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
		$class = self::factory();
		// fall back on default implementation if nothing else declared
		if ($class == __CLASS__) {
			$class = __NAMESPACE__ . '\MySQLOptimizer';
		}
		return $class::buildGroupBySQL($groupBy);
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
		// not implemented
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
