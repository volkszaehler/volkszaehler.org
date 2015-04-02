<?php
/**
 * Entity tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

class EntityTest extends Middleware
{
	static $uuid;

	function testCreateEntity() {
		// entities cannot be created - expect json exception
		$this->getJson('/entity.json', array(
			'operation' => 'add',
			'title' => 'Power',
			'type' => 'power',
			'resolution' => 1
		), 'GET', true);
	}

	function testEditEntity() {
		self::$uuid = Data::createChannel('Power', 'power', 1);

		// expect title updated
		$val = 'NewTitle';
		$this->assertEquals($val, $this->getJson('/entity/' . self::$uuid . '.json', array(
			'operation' => 'edit',
			'title' => $val
		))->entity->title);
	}

	/**
	 * @requires PHP 5.4
	 */
	function testPublicEntity() {
		// make sure the channel is NOT returned in the list of public entities
		$this->assertEquals(0, count(array_filter($this->getJson('/entity.json')->entities, function($entity) {
			return $entity->uuid == self::$uuid;
		})));

		// make entity public
		$this->assertEquals(1, $this->getJson('/entity/' . self::$uuid . '.json', array(
			'operation' => 'edit',
			'public' => 1
		))->entity->public);

		// make sure the channel is returned in the list of public entities
		$this->assertEquals(1, count(array_filter($this->getJson('/entity.json')->entities, function($entity) {
			return $entity->uuid == self::$uuid;
		})));
	}

	function testDeleteEntity() {
		// expect no exception
		$this->getJson('/entity/' . self::$uuid . '.json', array(
			'operation' => 'delete'
		));
	}
}

?>
