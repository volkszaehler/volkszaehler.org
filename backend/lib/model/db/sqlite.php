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
 * @brief base exception for mysql queries
 */
class SqLiteException extends DatabaseException
{
	function __construct($message = null, $code = 0) {
		$message = sprintf('%04d: %s', sqlite_error_string(sqlite_last_error()), sqlite_last_error());
		parent::__construct($message, sqlite_last_error());
	}
}

/**
 * @brief resultset of a mysql query
 */
class SqLiteResultSet extends DatabaseResultSet
{
	/**
	 * @param resource $resource mysql resultset
	 */
	function __construct($resource) {
		while ($row = sqlite_fetch_array($resource, SQLITE_ASSOC)) {
			$this->_rows[] = $row;
			++$this->_num_rows;
		}
	}
}

/**
 * @brief mysql layer
 */
class SqLite extends Database {
	/**
	 * @param string $host IP or domain of the database host
	 * @param string $name database name
	 * @param string $user user
	 * @param string $passwd password
	 */
	function __construct($config) {
		$this->select($config['filename']);
	}

	function __destruct() {
		$this->close();
	}

	/**
	 * @brief close database connection
	 */
	public function close() {
		if (!$this->resource)
			return;
			
		sqlite_close($this->resource);
		$this->resource = false;
	}

	/**
	 * @brief select database
	 * @param string $name database name
	 */
	public function select($filename) {
		if (!mysql_select_db($db, $this->resource))
			throw new SqLiteException();
		
		$this->database = $db;
	}

	/**
	 * @brief execute query
	 * @param string $sql query
	 * @return mixed
	 */
	public function execute($sql) {
		if (!($result = sqlite_query($this->resource, $sql)))
			throw new SqLiteException();
		
		$this->statements[] = $sql;
		
		return new SqLiteResultSet($result);
	}

	/**
	 * @brief sqlite escape string
	 * @param string $string query
	 */
	public function escapeString($string) {
		return sqlite_escape_string($string);
	}
	
	public function lastInsertId() {
		return sqlite_last_insert_rowid();
	}
}

?>