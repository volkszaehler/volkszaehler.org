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
 * @brief base exception for pgsql queries
 */
class PgSqlException extends DatabaseException
{
	function __construct($message = null, $code = 0) {
		parent::__construct(pg_last_error());
	}
}

/**
 * @brief resultset of a pgsql query
 */
class PgSqlResultSet extends DatabaseResultSet
{
	/**
	 * @param resource $resource pgsql resultset
	 */
	function __construct($resource) {
		while ($row = pg_fetch_assoc($ressource)) {
			$this->_rows[] = $row;
			++$this->_num_rows;
		}
	}

}

/**
 * @brief mysql layer
 */
class PgSql extends Database {
	/**
	 * @param string $host IP or domain of the database host
	 * @param string $name database name
	 * @param string $user user
	 * @param string $passwd password
	 */
	function __construct($config) {
		$this->connect($config['host'], $config['user'], $config['password']);
		$this->select($config['database']);
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
		
		if (!$this->resource = pg_connect('dbname=' . $db . ' host=' . $host . ' user=' . $user . ' password=' . $pw)) {
			error_reporting($__er);
			throw new PgSqlException();
		}
			
		error_reporting($__er);
	}

	/**
	 * @brief close database connection
	 */
	public function close() {
		if (!$this->resource)
			return;
		
		pg_close($this->resource);
		$this->resource = false;
	}

	/**
	 * @brief select database
	 * @param string $name database name
	 */
	public function select($db) {
		if (!pgsql_select_db($db, $this->resource))
			throw new PgSqlException();
		
		$this->database = $db;
	}

	/**
	 * @brief execute query
	 * @param string $sql query
	 * @return mixed
	 */
	public function execute($sql) {
		if (!($result = pg_query($sql, $this->resource)))
			throw new PgSqlException();
			
		$this->statements[] = $sql;
		
		return new PgSqlResultSet($result);
	}

	/**
	 * @brief pgsql escape string
	 * @param string $string query
	 */
	public function escapeString($string) {
		return pg_escape_string($this->resource, $string);
	}
	
	public function getLastInsertId() {
		throw new Exception('PgSql::getLastInsertId() hasn\'t implemented yet!');	// TODO find solution, use PDO?
	}
}

?>