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
class MySqlException extends DatabaseException {
	function __construct($message = NULL, $code = 0) {
		$message = sprintf('%04d: %s', mysql_errno(), mysql_error());
		parent::__construct($message, mysql_errno());
	}
}

/**
 * @brief resultset of a mysql query
 */
class MySqlResultSet extends DatabaseResultSet {
	/**
	 * @param resource $resource mysql resultset
	 */
	function __construct($resource) {
		while ($row = mysql_fetch_assoc($resource)) {
			$this->_rows[] = $row;
			++$this->_num_rows;
		}
	}
}

/**
 * @brief mysql layer
 */
class MySql extends Database {	// TODO replace by mysqli
	/**
	 * @param string $host IP or domain of the database host
	 * @param string $name database name
	 * @param string $user user
	 * @param string $passwd password
	 */
	function __construct($config) {
		$this->connect($config['host'], $config['user'], $config['password']);
		$this->selectDatabase($config['database']);
	}

	function __destruct() {
		$this->close();
	}

	/**
	 * @brief create database connection
	 * @param string $host IP or domain of the database host
	 * @param string $user user
	 * @param string $passwd password
	 */
	public function connect($host, $user, $pw) {
		$this->close();
		$__er = error_reporting(E_ERROR);
		
		if (!$this->resource = mysql_connect($host, $user, rawurlencode($pw))) {
			error_reporting($__er);
			throw new MySqlException();
		}
			
		error_reporting($__er);
	}

	/**
	 * @brief close database connection
	 */
	public function close() {
		if (!$this->resource)
			return;
		
		mysql_close($this->resource);
		$this->resource = false;
	}

	/**
	 * @brief select database
	 * @param string $name database name
	 */
	public function selectDatabase($db) {
		if (!mysql_select_db($db, $this->resource))
			throw new MySqlException();
			
		$this->database = $db;
	}

	/**
	 * @brief execute query
	 * @param string $sql query
	 * @return mixed
	 */
	public function execute($sql) {
		if (!($result = mysql_unbuffered_query($sql, $this->resource)))
			throw new MySqlException();
			
		$this->statements[] = $sql;
			
		return $result;
	}

	/**
	 * @brief mysql escape string
	 * @param string $string query
	 */
	public function escapeString($string) {
		return mysql_real_escape_string($string, $this->resource);
	}
	
	/**
	 * @brief count affected rows during last query or execute
	 */
	public function affectedRows() {
		return mysql_affected_rows($this->resource);
	}
	
	public function lastInsertId() {
		return mysql_insert_id($this->resource);
	}
}

?>