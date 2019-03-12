<?php
/**
 * Entity tests
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
 */

namespace Tests;

use Webpatser\Uuid\Uuid as UUID;

class EntityTest extends Middleware
{
	static $uuid;

	function testExistence() {
		$this->assertNotNull($this->getJson('/entity.json')->entities);
		$this->assertInternalType('array', $this->getJson('/entity.json')->entities);
	}

	function testCannotCreateEntity() {
		// entities cannot be created - expect json exception
		$this->getJson('/entity.json', array(
			'operation' => 'add',
			'title' => 'Power',
			'type' => 'power',
			'resolution' => 1
		), 'GET', true);
		$this->assertStringStartsWith('Invalid context operation', $this->json->exception->message);
	}

	function testCreateAndEditEntity() {
		self::$uuid = Data::createChannel('Power', 'power', 1);

		// expect title updated
		$val = 'NewTitle';
		$this->assertEquals($val, $this->getJson('/entity/' . self::$uuid . '.json', array(
			'operation' => 'edit',
			'title' => $val
		))->entity->title);
	}

	/**
	 * @depends testCreateAndEditEntity
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

	/**
	 * @depends testCreateAndEditEntity
	 */
	function testDeleteEntity() {
		// expect no exception
		$this->getJson('/entity/' . self::$uuid . '.json', array(
			'operation' => 'delete'
		));
	}

	function testEditEntityWithInvalidProperties() {
		self::$uuid = Data::createChannel('Power', 'power', 1);
		$uri = '/entity/' . self::$uuid . '.json';

		// expect float type exception
		$this->getJson($uri, array(
			'operation' => 'edit',
			'resolution' => '42.fourtytwo'
		), 'GET', true);
		$this->assertStringStartsWith('Invalid property value', $this->json->exception->message);

		// expect boolean type exception
		$this->getJson($uri, array(
			'operation' => 'edit',
			'active' => 'wahr'
		), 'GET', true);
		$this->assertStringStartsWith('Invalid property value', $this->json->exception->message);
	}

	/**
	 * @depends testEditEntityWithInvalidProperties
	 */
	function testDeleteEntityAfterEditErrors() {
		// expect no exception
		$this->getJson('/entity/' . self::$uuid . '.json', array(
			'operation' => 'delete'
		));
	}

	function testGetInvalidEntity() {
		// malformed uuid
		$this->getJson('/entity/foo.json', array(), 'GET', true);
		$this->assertStringStartsWith('Invalid UUID', $this->json->exception->message);

		// non-existing uuid
		$uuid = UUID::generate();
		$this->getJson('/entity/' . $uuid . '.json', array(), 'GET', true);
		$this->assertStringStartsWith('No entity with UUID', $this->json->exception->message);
	}

	function testEditInvalidEntity() {
		// missing uuid
		$this->getJson('/entity.json', array(
			'operation' => 'edit'
		), 'GET', true);
		$this->assertStringStartsWith('Missing UUID', $this->json->exception->message);

		// malformed uuid
		$this->getJson('/entity/foo.json', array(
			'operation' => 'edit'
		), 'GET', true);
		$this->assertStringStartsWith('Invalid UUID', $this->json->exception->message);

		// non-existing uuid
		$uuid = UUID::generate();
		$this->getJson('/entity/' . $uuid . '.json', array(
			'operation' => 'edit'
		), 'GET', true);
		$this->assertStringStartsWith('No entity with UUID', $this->json->exception->message);
	}
}

?>
