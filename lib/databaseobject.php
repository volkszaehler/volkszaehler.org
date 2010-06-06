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

abstract class DatabaseObject {
	protected $dbh;				// database handle for all queries in DBObject subclasses

	private $dirty;				// do we need to update the database?
	private $values = array();

	static private $instances = array();	// singletons of objects

	final public function __construct($object) {
		$this->dbh = Database::getConnection();

		if (key_exists('id', $object)) {	// referencing existing object in database by id
			$this->values['id'] = $object['id'];
			$this->dirty = false;
		}
		else {								// create new object in database
			$this->values = $object;
			$this->dirty = true;
		}
	}

	static protected function factory($object) {
		return new static($object);
	}

	public function __get($key) {
		if (!isset($this->values[$key]) && $this->id) {
			$this->load();
		}

		return $this->values[$key];
	}

	public function __set($key, $value) {	// TODO untested
	if ($key != 'id') {
		$this->values[$key] = $value;
		$this->dirty = true;
	}
	}

	final public function __sleep() {
		$this->save();
		return array('id');
	}

	final public function __wakeup() {
		$this->dbh = Database::getConnection();
	}

	public function save() {
		if ($this->id) {	// just update
			foreach ($this->values as $column => $value) {
				$columns[] = $column . ' = ' . $this->dbh->escape($value);
			}
				
			$sql = 'UPDATE ' . static::table . ' SET ' . implode(', ', $columns) . ' WHERE id = ' . (int) $this->id;
			$this->dbh->execute($sql);
		}
		else {				// insert new row
			$sql = 'INSERT INTO ' . static::table . ' (' . implode(', ', array_keys($this->values)) . ') VALUES (' . implode(', ', array_map(array($this->dbh, 'escape'), $this->values)) . ')';
			$this->dbh->execute($sql);
			$this->id = $this->dbh->lastInsertId();
		}
		$this->dirty = false;
	}

	private function load() {
		$result = $this->dbh->query('SELECT * FROM ' . static::table . ' WHERE id = ' . (int) $this->id, 1)->current();
			
		if ($result == false) {
			unset($this->values['id']);
			return false;
		}
		else {
			$this->values = $result;
			$this->loaded = true;
			return true;
		}
	}


	public function delete() {
		$this->dbh->execute('DELETE FROM ' . static::table . ' WHERE id = ' . (int) $this->id);	// delete from database
		unset($this->values['id']);
	}

	/*
	 * @return array(static) Array with results
	 */
	static public function getByFilter($filters = array(), $conjunction = true) {
		$sql = static::buildFilterQuery($filters, $conjunction);
		$result = Database::getConnection()->query($sql);

		if (!isset(self::$instances[static::table])) {
			self::$instances[static::table] = array();
		}

		$instances = array();
		foreach ($result as $object) {
			if (!isset(self::$instances[static::table][$object['id']])) {
				self::$instances[static::table][$object['id']] = static::factory($object);
			}
			$instances[$object['id']] = self::$instances[static::table][$object['id']];
		}

		return $instances;
	}

	static protected function buildFilterQuery($filters, $conjunction, $columns = array('id')) {
		return 'SELECT id FROM ' . static::table . static::buildFilterCondition($filters, $conjunction);
	}

	static protected function buildFilterCondition($filters, $conjunction) {
		$dbh = Database::getConnection();

		$where = array();
		foreach ($filters as $column => $value) {
			if (is_array($value)) {
				$where[] = $column . ' IN (' . implode(', ', array_map(array(Database::getConnection(), 'escape'), $value)) . ')';
			}
			else {
				$where[] = $column . ' = ' . $dbh->escape($value);
			}
		}

		if (count($where) > 0) {
			return ' WHERE ' . implode(($conjunction === true) ? ' && ' : ' || ', $where);
		}
	}
}

?>