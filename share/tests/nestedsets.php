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

include '../../backend/init.php';

$newGroup = new Group();
$newGroup->name = 'Test';
//$newGroup->save(Group::getById(58));

$groups = array(Group::getById(31));
$groups += Group::getById(31)->getChildren();

echo '<pre>';
foreach ($groups as $child) {
	for ($i = 0; $i < $child->level; $i++) {
		echo ' ';
	}
	
	echo '[' . $child->id . '] ' . $child->name . ' {' . $child->uuid . '} ' . $child->level . ':' . $child->children . "\n";
}
echo '</pre>';

//$newGroup->delete();

/*$groups = Group::getByFilter(array('name' => 'Test'));
foreach ($groups as $group) {
	$group->delete();
}*/

/*echo '<pre>';
foreach (Group::getById(1)->getChildren() as $child) {
	for ($i = 0; $i < $child->level; $i++) {
		echo ' ';
	}
	
	echo '[' . $child->id . '] ' . $child->name . ' {' . $child->uuid . '}' . "\n";
}


//var_dump(Database::getConnection());*/

echo '</pre>';

?>