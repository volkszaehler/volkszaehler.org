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

	function testExistence() {
		// create group
		$this->assertNotNull($this->getJson('/group.json')->channels);
		$this->assertInternalType('array', $this->getJson('/group.json')->channels);
	}

	function testCreateGroup() {
		// create group
		self::$uuid = $this->getJson('/group.json', array(
			'operation' => 'add',
			'title' => 'Group'
		))->entity->uuid;
	}

	/**
	 * @depends testCreateGroup
	 */
	function testEditGroup() {
		// edit group
		$val = 'NewValue';
		$this->assertEquals($val, $this->getJson('/group/' . self::$uuid . '.json', array(
			'operation' => 'edit',
			'title' => $val
		))->entity->title);
	}

	/**
	 * @depends testCreateGroup
	 */
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

	/**
	 * @depends testCreateGroup
	 */
	function testAddDataToGroup() {
		// $this->addTuple($this->ts1, $this->value1);
		$this->json = $this->getJson('/data/' . self::$uuid . '.json', array(
			'operation' => 'add',
			'value' => 1
		), 'GET', true);

		$this->assertTrue(isset($this->json->exception));
	}

	/**
	 * @depends testCreateGroup
	 */
	function testGetDataFromGroup() {
		// $this->getTuples($this->ts1-1, $this->ts2);
		$this->json = $this->getJson('/data/' . self::$uuid . '.json', array(), 'GET', true);

		$this->assertTrue(isset($this->json->exception));
	}

	/**
	 * @depends testCreateGroup
	 */
	function testGetGroupAsChannel() {
		// $this->getTuples($this->ts1-1, $this->ts2);
		$this->json = $this->getJson('/channel/' . self::$uuid . '.json', array(), 'GET', true);

		$this->assertStringStartsWith('Entity is not a channel', $this->json->exception->message);
	}

	/**
	 * @depends testCreateGroup
	 */
	function testDeleteGroup() {
		// delete group
		$this->getJson('/group/' . self::$uuid . '.json', array(
			'operation' => 'delete'
		));

		$this->getJson('/group/' . self::$uuid . '.json', array(), 'GET', true);
	}
}

?>
