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
 * the group class groups users, channels and groups itself
 */
class Group extends NestedDatabaseObject {
	const table = 'groups';
	
	public function getUsers($recursive = false) {	// TODO rework for nested sets
		$groups[$this->id] = $this;
		if ($recursive === true) {
			$groups += $this->getChilds();
		}
		return User::getByFilter(array('group.id' => $groups));
	}
	
	public function getChannels($recursive = false) {	// TODO rework for nested sets
		$groups[$this->id] = $this;
		if ($recursive === true) {
			$groups += $this->getChilds();
		}
		return Channel::getByFilter(array('group.id' => $groups));
	}
	
	/*
	 * data filtering
	 */
	static public function getByFilter($filters = array(), $conjunction = true) {
		$joins = array();
		foreach ($filters as $column => $value) {
			if (!key_exists('users', $joins) && preg_match('/^user\.([a-z_]+)$/', $column)) {
				$joins['users_in_groups'] = array('type' => 'left', 'table' => 'users_in_groups', 'condition' => 'users_in_groups.group_id = ' . self::table . '.id');
				$joins['users'] = array('type' => 'left', 'table' => 'users AS user', 'condition' => 'user.id = users_in_groups.user_id');
			}
			
			if (!key_exists('channels', $joins) && preg_match('/^channel\.([a-z_]+)$/', $column)) {
				$joins['channels_in_groups'] = array('type' => 'left', 'table' => 'channels_in_groups', 'condition' => 'channels_in_groups.group_id = ' . self::table . '.id');
				$joins['channels'] = array('type' => 'left', 'table' => 'channels AS channel', 'condition' => 'channels.id = channels_in_groups.channel_id');
			}
		}
		
		$result = Database::getConnection()->select(self::table, array(self::table . '.*'), $filters, $conjunction, $joins);
		
		$instances = array();
		foreach ($result as $object) {
			$instances[$object['id']] = static::factory($object);
		}
	
		return $instances;
	}
}

?>