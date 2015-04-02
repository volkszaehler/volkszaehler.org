<?php
/**
 * Entity tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

class GroupTest extends Middleware
{
	static $uuid;

	function testCreateGroup() {
		// create group
		self::$uuid = $this->getJson('/group.json', array(
			'operation' => 'add',
			'title' => 'Group'
		))->entity->uuid;
	}

	function testEditGroup() {
		// edit group
		$val = 'NewValue';
		$this->assertEquals($val, $this->getJson('/group/' . self::$uuid . '.json', array(
			'operation' => 'edit',
			'title' => $val
		))->entity->title);
	}

	function testAddChildren() {
		// create child
		$child = $this->getJson('/group.json', array(
			'operation' => 'add',
			'title' => 'Child'
		))->entity->uuid;

		// add child to group
		$this->assertEquals($child, $this->getJson('/group/' . self::$uuid . '.json', array(
			'operation' => 'add',
			'uuid' => $child
		))->entity->children[0]->uuid);

		// remove child from group
		$this->assertObjectNotHasAttribute('children', $this->getJson('/group/' . self::$uuid . '.json', array(
			'operation' => 'delete',
			'uuid' => $child
		))->entity);

		// add child to group
		$this->assertEquals($child, $this->getJson('/group/' . self::$uuid . '.json', array(
			'operation' => 'add',
			'uuid' => $child
		))->entity->children[0]->uuid);

		// remove child -> vanish from group
		$this->getJson('/group/' . $child . '.json', array(
			'operation' => 'delete'
		));
		$this->assertObjectNotHasAttribute('children', $this->getJson('/group/' . self::$uuid . '.json')->entity);
	}

	function testDeleteGroup() {
		// delete group
		$this->getJson('/group/' . self::$uuid . '.json', array(
			'operation' => 'delete'
		));

		$this->getJson('/group/' . self::$uuid . '.json', array(), 'GET', true);
	}
}

?>
