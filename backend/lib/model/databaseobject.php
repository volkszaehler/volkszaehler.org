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

	protected $data = array();
	
	const STATE_UNKNOWN		= 0;	// we don't know the current status of the object (deprecated)
	const STATE_UNLINKED	= 1;	// there is no database representation of the object
	const STATE_DIRTY		= 2;		// there is a database representation of the object which has been altered
	const STATE_CLEAN		= 4;		// there is a database representation of the object which which is in sync with the database
	
	private $state = DatabaseObject::STATE_UNKNOWN;

	static protected $instances = array();	// singletons of objects

	/*
	 * magic functions
	 */
	final public function __construct($object = array()) {
		$this->dbh = Database::getConnection();
		$this->data = $object;
	}

	public function __get($key) {
		if (!isset($this->$key) && ($this->state != DatabaseObject::STATE_UNLINKED)) {
			$this->load();
		}

		return $this->data[$key];
	}

	public function __set($key, $value) {	// TODO untested
		if ($key == 'id' || $key == 'uuid') {
			throw new Exception($key . ' will be generated automatically');
		}
		
		$this->data[$key] = $value;
		$this->state = DatabaseObject::STATE_DIRTY;
	}

	final public function __sleep() {
		$this->save();
		return array('id');
	}

	final public function __wakeup() {
		$this->state = DatabaseObject::STATE_UNKNOWN;
		$this->dbh = Database::getConnection();
	}

	final public function __isset($key) {
		return isset($this->data[$key]);
	}

	/*
	 * insert oder update the database representation of the object
	 */
	public function save() {
		if (isset($this->id)) {	// just update
			$this->update();
		}
		else {					// insert new row
			$this->insert();
		}
	}

	protected function insert() {
		$this->data['uuid'] = Uuid::mint();
		
		$this->dbh->insert(static::table, $this->data);
		$this->data['id'] = $this->dbh->lastInsertId();
		
		$this->state = DatabaseObject::STATE_CLEAN;
	}

	protected function update() {
		$this->dbh->update(static::table, $this->data, array('id' => $this->id));
		$this->dbh->execute($sql);
		
		$this->state = DatabaseObject::STATE_CLEAN;
	}

	/*
	 * loads all columns from the database and caches them in $this->data
	 */
	private function load() {
		$result = $this->dbh->select(static::table, array('*'), array('id' => $this->id))->current();
			
		if ($result == false) {
			$this->unlink();
			throw new Exception('Missing database representation');
		}

		$this->state = DatabaseObject::STATE_CLEAN;
		$this->data = $result;
	}
	
	/*
	 * unlinks instance from database
	 */
	protected function unlink() {
		unset($this->data['id']);
		unset($this->data['uuid']);
		$this->status = DatabaseObject::STATE_UNLINKED;
	}

	/*
	 * deletes database representation of this object, but leaves object members.
	 * by calling $this->save() you can easily reinsert the object with a new id
	 */
	public function delete() {
		$this->unlink();
		return $this->dbh->delete(static::table, array('id' => $this->id));
	}

	/*
	 * simple self::getByFilter() wrapper
	 */
	public static function getByUuid($uuid) {
		$obj = current(self::getByFilter(array('uuid' => $uuid)));

		if ($obj === false) {
			throw new InvalidArgumentException('No such object!');
		}

		return $obj;
	}
	
	/*
	 * simple self::getByFilter() wrapper
	 */
	public static function getById($id) {
		return static::factory(array('id' => $id));		// TODO LSB nescessary?
	}
	
	static protected function factory($object) {
		if (!isset(self::$instances[static::table])) {
			self::$instances[static::table] = array();
		}
		
		if (!isset(self::$instances[static::table][$object['id']])) {
			self::$instances[static::table][$object['id']] = new static($object);	// create singleton instance of database object
		}
		
		return self::$instances[static::table][$object['id']];	// return singleton instance of database object
	}

	/*
	 * data filtering
	 */
	static public function getByFilter($filters = array(), $conjunction = true) {
		$result = Database::getConnection()->select(static::table, array(static::table . '.*'), $filters, $conjunction);
		
		$instances = array();
		foreach ($result as $object) {
			$instances[$object['id']] = static::factory($object);
		}
	
		return $instances;
	}
	
	public function __toString() {
		return (string) $this->id;
	}
}

?>
