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
 * TODO use database transactions
 */
abstract class NestedDatabaseObject extends DatabaseObject {
	public $level;	// shouldn't be altered! use move or delete instead!
	public $children;

	/*
	 * inserts or updates a tree to the database
	 */
	public function save(NestedDatabaseObject $parent = NULL) {
		if (!is_null($parent) && !isset($parent->id)) {	// checks if $parent is part of the tree
			throw new InvalidArgumentException('Parent node has to be part of the tree');
		}

		if (isset($this->id)) {
			$this->update($parent);
		}
		else {
			$this->insert($parent);
		}
	}
	
	/*
	 * updates tree in database (optionally move it)
	 */
	protected function update(NestedDatabaseObject $parent = NULL) {
		// TODO move it if parent is given
		parent::update();
	}

	/*
	 * inserts tree to database
	 */
	protected function insert(NestedDatabaseObject $parent = NULL) {
		if (is_null($parent)) {
			throw new InvalidArgumentException('We need a parent for a new child');
		}
		
		$this->dbh->execute('UPDATE ' . static::table . ' SET lft = lft + 2 WHERE lft > ' . $parent->rgt);
		$this->dbh->execute('UPDATE ' . static::table . ' SET rgt = rgt + 2 WHERE rgt >= ' . $parent->rgt);
			
		// update singleton instances
		foreach (self::$instances[static::table] as $instance) {
			if ($instance->lft > $parent->rgt) {
				$instance->lft = $instance->lft + 2;
			}

			if ($instance->rgt >= $parent->rgt) {
				$instance->rgt = $instance->rgt + 2;
			}
		}
			
		$this->lft = $parent->rgt - 2;
		$this->rgt = $parent->rgt - 1;
			
		parent::insert();
	}

	/*public function move(NestedDatabaseObject $parent) {	// TODO finish
		$move = $this->rgt - $this->lft + 1;
	
		// exclude the tree which we want to move by turning the sign of left and rigth columns
		$sql = 'UPDATE ' . static::table . ' SET lft = lft * -1, rgt = rgt * -1 WHERE lft > ' . $this->lft . ' && rgt < ' . $this->rgt;
	
		// close hole
		$sql = 'UPDATE ' . static::table . ' SET lft = lft - x, rgt = rgt - x WHERE lft > ' . $this->lft;
	
		// open new hole
	
		// include the tree which we want to move by turning the sign of left and rigth columns and adding an offset
	
		// TODO update singletons
	}*/

	/*
	 * query database for all descending children under this node
	 */
	public function getChildren() {
		$sql = 'SELECT
					o.*,
					CAST(((o.rgt - o.lft - 1) / 2) AS UNSIGNED) AS children, 
					COUNT(p.id) AS level
				FROM
					' . static::table . ' AS n,
					' . static::table . ' AS p,
					' . static::table . ' AS o
				WHERE
					o.lft > p.lft && o.rgt < p.rgt
					&& o.lft > n.lft && o.rgt < n.rgt
					&& n.id = ' . $this->id . '
				GROUP BY
					o.lft
				ORDER BY
					o.lft';

		$result = $this->dbh->query($sql);

		$children = array();
		foreach ($result as $row) {
			$child = static::factory(array_diff_key($row, array_fill_keys(array('children', 'level'), NULL)));
			$child->children = $row['children'];
			$child->level = $row['level'];
				
			$children[$row['id']] = $child;
		}

		return $children;
	}
	
	static public function getRoot() {
		return current(static::getByFilter(array('lft' => 0)));
	}

	/*
	 * delete the node including all descending children from the database
	 */
	public function delete() {
		$move = $this->rgt - $this->lft + 1;

		// delete children
		$result = $this->dbh->execute('DELETE FROM ' . static::table . ' WHERE lft >= ' . $this->lft . ' && lft <= ' . $this->rgt);

		// move remaining children ...
		$this->dbh->execute('UPDATE ' . static::table . ' SET lft = lft - ' . $move . ' WHERE lft > ' . $this->rgt);
		$this->dbh->execute('UPDATE ' . static::table . ' SET rgt = rgt - ' . $move . ' WHERE rgt > ' . $this->rgt);

		// update singleton instances
		foreach (self::$instances[static::table] as $instance) {
			if ($instance->lft >= $this->lft && $instance->rgt <= $this->rgt) {
				$instance->unlink();
			}
			else {
				if ($instance->lft > $this->rgt) {
					$instance->lft = $instance->lft - $move;
				}
					
				if ($instance->rgt > $this->rgt) {
					$instance->rgt = $instance->rgt - $move;
				}
			}
		}
	}

	/*
	 * unlinks instance from database
	 */
	protected function unlink() {
		parent::unlink();

		unset($this->data['lft']);
		unset($this->data['rgt']);

		unset($this->level);
		unset($this->children);
	}

	/*
	 * checks if $child is a descendant of $this
	 * @return bool
	 */
	public function contains(NestedDatabaseObject $child) {
		return ($child->lft > $this->lft && $child->rgt < $this->rgt);
	}
}

class NestedDatabaseException extends Exception {}

?>