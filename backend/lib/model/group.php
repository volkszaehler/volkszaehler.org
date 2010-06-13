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
class Group extends NestedDatabaseObject {
	const table = 'groups';
	
	public function getUsers($recursive = false) {	// TODO rework for nested sets
		$groups[$this->id] = $this;
		if ($recursive === true) {
			$groups += $this->getChilds();
		}
		return User::getByFilter(array('group' => $groups));
	}
	
	public function getChannels($recursive = false) {	// TODO rework for nested sets
		$groups[$this->id] = $this;
		if ($recursive === true) {
			$groups += $this->getChilds();
		}
		return Channel::getByFilter(array('group' => $groups));
	}
	
	static protected function buildFilterQuery($filters, $conjunction, $columns = array('id')) {	// TODO rework for nested sets
		$sql = 'SELECT ' . self::table . '.* FROM ' . self::table;
		
		// join users
		if (key_exists('user', $filters)) {
			$sql .= ' LEFT JOIN users_in_groups ON users_in_groups.group_id = ' . self::table . '.id';
			$filters['users_in_groups.user_id'] = $filters['user'];
			unset($filters['user']);
		}
		
		// join channels
		if (key_exists('channel', $filters)) {
			$sql .= ' LEFT JOIN channels_in_groups ON channels_in_groups.group_id = ' . self::table . '.id';
			$filters['channels_in_groups.channel_id'] = $filters['channel'];
			unset($filters['channel']);
		}

		$sql .= static::buildFilterCondition($filters, $conjunction);
		return $sql;
	}
}

?>