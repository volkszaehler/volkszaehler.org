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
 * User class
 */
class User extends DatabaseObject {
	const table = 'users';
	
	/*
	 * simple self::getByFilter() wrapper
	 */
	public static function getByUuid($uuid) {
		return current(self::getByFilter(array('uuid' => $uuid)));
	}
	
	public static function getByEMail($email) {
		return current(self::getByFilter(array('email' => $email)));
	}
	
	public function getChannels($recursive = false) {
		$groups = $this->getGroups($recursive);
		
		return Channel::getByFilter(array('group' => $groups));
	}
	
	public function getGroups($recursive = false) {
		$groups = Group::getByFilter(array('user' => $this));
		if ($recursive === true) {
			foreach ($groups as $subGroup) {
				$groups += $subGroup->getGroups(true);
			}
		}
		return $groups;
	}
	
	public function checkPassword($pw) {
		return ($this->password == sha1($pw)) ? true : false;
	}
	
	public function __set($key, $value) {	// special case for passwords 
		if ($key == 'password') {
			$value = sha1($value);
		}
		parent::__set($key, $value);
	}
	
	static protected function buildQuery($filters, $conjunction, $columns = array('id')) {
		$sql = 'SELECT ' . static::table . '.* FROM ' . static::table;
		// join groups
		if (key_exists('group', $filters)) {
			$sql .= ' LEFT JOIN group_user AS rel ON rel.user_id = ' . static::table . '.id';
			$filters['group_id'] = $filters['group'];
			unset($filters['group']);
		}
		$sql .= static::buildFilter($filters, $conjunction);
		return $sql;
	}
}

?>