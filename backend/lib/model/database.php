<?php
/*
 * Copyright (c) 2010 by Justin Otherguy <justin@justinotherguy.org>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License (either version 2 or
 * version 3) as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * For more information on the GPL, please go to:
 * http://www.gnu.org/copyleft/gpl.html
 */

/**
 * @brief base exception for database queries
 */
class DatabaseException extends CustomException {}

/**
 * @brief abstract resultset definition
 */
abstract class DatabaseResultSet implements Iterator {
	/**
	 * @brief rowcount of the result
	 * @var int
	 */
	protected $_num_rows = 0;

	/**
	 * @brief result
	 * @var array
	 */
	protected $_rows = array();

	/**
	 * @param resource $resource database resource
	 */
	abstract function __construct($resource);

	/**
	 * @brief current element (iterator)
	 * @return array
	 */
	public function current() {
		return current($this->_rows);
	}

	/**
	 * @brief next element (iterator)
	 * @return array
	 */
	public function next() {
		return next($this->_rows);
	}

	/**
	 * @brief previous element (iterator)
	 * @return array
	 */
	public function prev() {
		return prev($this->_rows);
	}

	/**
	 * @brief index of current element (iterator)
	 * @return array
	 */
	public function key() {
		return key($this->_rows);
	}

	/**
	 * @brief first element (pointer reset, iterator)
	 * @return array
	 */
	public function rewind() {
		return reset($this->_rows);
	}

	/**
	 * @brief last element (iterator)
	 * @return array
	 */
	public function end() {
		return end($this->_rows);
	}

	/**
	 * @brief check current element (iterator)
	 * @return bool
	 */
	public function valid() {
		return (bool) is_array($this->current());
	}

	/**
	 * @brief count rows of the result set
	 * @return integer
	 */
	public function count() {
		return $this->_num_rows;
	}
}

/**
 * @brief interface database definition
 */
interface DatabaseInterface {
	/**
	 * @brief create database connection
	 * @param array connection info
	 */
	public function __construct($config);

	/**
	 * @brief create database connection
	 * @param string $host IP or domain of the database host
	 * @param string $user user
	 * @param string $passwd password
	 */
	public function connect($host, $user, $pw);

	/**
	 * @brief close database connection
	 */
	public function close();

	/**
	 * @brief select database
	 * @param string $name name of database
	 */
	public function select($db);

	/**
	 * @brief execute query
	 * @param string $sql query
	 * @return mixed
	 */
	public function execute($sql);

	/**
	 * @brief query
	 * @param string $sql
	 * @param int $offset
	 * @param int $limit
	 * @return TDatabaseResultSet
	 */
	public function query($sql, $limit = NULL, $offset = NULL);

	public function escapeString($string);

	public function escape($value);
	
	public function lastInsertId();
}

/**
 * @brief abstract database layer definition
 */
abstract class Database implements DatabaseInterface {

	static private $connection = NULL;

	/**
	 * @brief current database
	 * @var string
	 */
	protected $database = '';

	/**
	 * @brief database handle
	 * @var resource
	 */
	protected $resource = false;

	/**
	 * @brief container with exectuted queries
	 * @var array
	 */
	protected $statements = array();

	/*
	 * @return singleton instance
	 */
	static public function getConnection() {
		if (is_null(self::$connection)) {
			$config = Registry::get('config');
				
			$rc = new ReflectionClass($config['db']['backend']);
			if (!$rc->isSubclassOf('Database')) {
				throw new InvalidArgumentException('\'' . $rc->getName() . '\' is not a valid database backend');
			}
				
			self::$connection = $rc->newInstanceArgs(array($config['db']));
		}

		return self::$connection;
	}

	public function escape($value) {
		if (is_numeric($value)) {
			return $value;
		}
		elseif ($value instanceof DatabaseObject) {
			return (int) $value->id;
		}
		else {
			$value = '\'' . $this->escapeString($value) . '\'';
		}
		
		return $value;
	}
}

?>