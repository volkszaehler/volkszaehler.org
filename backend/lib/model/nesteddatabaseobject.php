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
 */
class NestedDatabaseObject extends DatabaseObject {
	public function addChild(NestedDatabaseObject $child) {
		if (isset($child->id)) {
			throw new DatabaseException('group is already part of the tree');
		}
		
		// TODO start transaction
		$this->dbh->execute('UPDATE ' . static::table . ' SET left = left + 2 WHERE left > ' . $this->right);
		$this->dbh->execute('UPDATE ' . static::table . ' SET right = right + 2 WHERE right >= ' . $this->right);
		
		$child->left = $this->right;
		$child->right = $this->right + 	1;
		$child->insert();
	}
	
	public function getChilds() {
		$sql = 'SELECT * FROM ' . static::table . ' WHERE id != ' . $this->id . ' && left BETWEEN ' . $this->left . ' AND ' . $this->right;
		$result = $this->dbh->query($sql);
		
		$groups = array();
		foreach ($result as $group) {
			$groups[$group['id']] = static::factory($group);
		}
		
		return $groups;
	}
	
	public function delete() {
		$move = floor(($this->right - $this->left) / 2);
		$move = 2 * (1 + $move);
		
		// TODO start transaction
		
		// delete nodes
		$this->dbh->execute('DELETE FROM ' . static::table . ' WHERE AND left BETWEEN ' . $this->left . ' AND ' . $this->right);	// TODO SQL92 compilant?
		
		// move the rest of the nodes ...
		$this->dbh->execute('UPDATE ' . static::table . ' SET left = left - ' . $move . ' WHERE left > ' . $this->right);
		$this->dbh->execute('UPDATE ' . static::table . ' SET right = right - ' . $move . ' WHERE right > ' . $this->right);
		
		// TODO unset singleton instances in DatabaseObject::$instances
	}
	
	public function contains(NestedDatabaseObject $child) {	// TODO untested
		if (array_search($child, $this->getChilds(), true) === false) {
			return false;
		}
		else {
			return true;
		}
	}
	
	public function moveTo(NestedDatabaseObject $obj) {	// TODO implement
		// $this->getChilds
		// $this->delete
		// $group->addChilds
	}
}

?>