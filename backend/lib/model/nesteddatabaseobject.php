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

/*
 * DatabaseObject which is structured by nested sets
 * @url // TODO add url
 */
abstract class NestedDatabaseObject extends DatabaseObject {
	public function __set($key, $value) {
		if ($key == 'left' || $key == 'right') {
			throw new NestedDatabaseException('nested set fields are read only! please make use of move() or delete() instead');
		}
		
		parent::__set($key, $value);
	}
	
	public function addChild(NestedDatabaseObject $child) {
		if (isset($child->id)) {
			throw new NestedDatabaseException('Object is already part of the tree');
		}
		
		// TODO start transaction
		$this->dbh->execute('UPDATE ' . static::table . ' SET left = left + 2 WHERE left > ' . $this->right);
		$this->dbh->execute('UPDATE ' . static::table . ' SET right = right + 2 WHERE right >= ' . $this->right);
		
		$child->left = $this->right;
		$child->right = $this->right + 	1;
		
		$this->right += 2;
		
		$child->insert();
	}
	
	public function getChilds() {
		$sql = 'SELECT * FROM ' . static::table . ' WHERE left > ' . $this->left . ' && left < ' . $this->right;
		$result = $this->dbh->query($sql);
		
		$objs = array();
		foreach ($result as $obj) {
			$objs[$obj['id']] = static::factory($obj);
		}
		
		return $groups;
	}
	
	/*
	 * deletes subset under $this
	 */
	public function delete() {
		$move = floor(($this->right - $this->left) / 2);
		$move = 2 * (1 + $move);
		
		// TODO start transaction
		
		// delete nodes
		$result = $this->dbh->query('SELECT * FROM ' . static::table . ' WHERE left >= ' . $this->left . ' && left <= ' . $this->right);
		foreach ($result as $obj) {
			$obj->delete();	// TODO optimize (all in one query)
		}

		// move remaining nodes ...
		$this->dbh->execute('UPDATE ' . static::table . ' SET left = left - ' . $move . ' WHERE left > ' . $this->right);
		$this->dbh->execute('UPDATE ' . static::table . ' SET right = right - ' . $move . ' WHERE right > ' . $this->right);
	}
	
	/*
	 * checks if $child is a child of $this 
	 * @return bool
	 */
	public function contains(NestedDatabaseObject $child) {	// TODO untested
		$sql = 'SELECT * FROM ' . static::table . ' WHERE left > ' . $this->left . ' && left < ' . $this->right . ' && id = ' . $child->id;
		$result = $this->dbh->query($sql);
		
		return ($result->count() > 0) ? true : false;
	}
	
	public function moveTo(NestedDatabaseObject $destination) {	// TODO implement
		// $this->getChilds
		$obj = $this->getChilds();
		foreach ($objs as $obj) {
			$obj->right += $destination->left - $this->left;
			$obj->left = $destination->left;
		}
	
		// close whole
		$move = floor(($this->right - $this->left) / 2);
		$move = 2 * (1 + $move);
		
		$this->dbh->execute('UPDATE ' . static::table . ' SET left = left - ' . $move . ' WHERE left > ' . $this->right);
		$this->dbh->execute('UPDATE ' . static::table . ' SET right = right - ' . $move . ' WHERE right > ' . $this->right);
	
		// create hole
		$this->dbh->execute('UPDATE ' . static::table . ' SET left = left + ' . $move . ' WHERE left > ' . $this->right);
		$this->dbh->execute('UPDATE ' . static::table . ' SET right = right + ' . $move . ' WHERE right >= ' . $this->right);
	}
}

class NestedDatabaseException extends Exception {}

?>