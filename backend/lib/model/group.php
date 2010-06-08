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
 * Grouping class
 * 
 * the group class groups users, channels and groups
 */
class Group extends DatabaseObject {
	const table = 'groups';
	
	public function getGroups($recursive = false) {
		$groups = Group::getByFilter(array('group' => $this));
		if ($recursive === true) {
			foreach ($groups as $subGroup) {
				$groups += $subGroup->getGroups(true);
			}
		}
		return $groups;
	}
	
	public static function getByUgid($ugid) {
		$group = self::getByFilter(array('ugid' => $ugid));
		
		if (current($group) === false) {
			throw new InvalidArgumentException('No such group!');
		}
		
		return $group;
	}
	
	public function getUsers($recursive = false) {
		$groups[$this->id] = $this;
		if ($recursive === true) {
			$groups += $this->getGroups(true);
		}
		return User::getByFilter(array('group' => $groups));
	}
	
	public function getChannels($recursive = false) {
		$groups[$this->id] = $this;
		if ($recursive === true) {
			$groups += $this->getGroups(true);
		}
		return Channel::getByFilter(array('group' => $groups));
	}
	
	static protected function buildFilterQuery($filters, $conjunction, $columns = array('id')) {
		$sql = 'SELECT ' . static::table . '.* FROM ' . static::table;

		// join groups
		if (key_exists('group', $filters)) {
			$sql .= ' LEFT JOIN group_group AS rel ON rel.child_id = ' . static::table . '.id';
			$filters['parent_id'] = $filters['group'];
			unset($filters['group']);
		}
		
		// join users
		if (key_exists('user', $filters)) {
			$sql .= ' LEFT JOIN group_user AS rel ON rel.group_id = ' . static::table . '.id';
			$filters['user_id'] = $filters['user'];
			unset($filters['user']);
		}

		$sql .= static::buildFilterCondition($filters, $conjunction);
		return $sql;
	}
}

?>