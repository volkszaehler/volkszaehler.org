<?php
/**
 * Entity tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

class Entity extends Middleware
{
	static $uuid;

	// channel properties
	static $resolution = 100;

	/**
	 * Initialize context
	 */
	static function setupBeforeClass() {
		parent::setupBeforeClass();
		static::$context = static::$mw . 'entity';

		self::$uuid = self::createChannel('Meter', 'power', self::$resolution);
	}

	/**
	 * Remove channel if initialized
	 */
	static function tearDownAfterClass() {
		if (self::$uuid) {
			self::deleteChannel(self::$uuid);
			self::$uuid = null;
		}
		parent::tearDownAfterClass();
 	}

	static function createChannel($title, $type, $resolution = null) {
		$url = static::$mw . 'channel.json?operation=add&title=' . urlencode($title) . '&type=' . urlencode($type);
		if ($resolution) $url .= '&resolution=' . $resolution;
		$json = static::getJsonRaw($url);

		return ((isset($json->entity->uuid)) ? $json->entity->uuid : null);
	}

	static function deleteChannel($uuid) {
		$url = static::$mw . 'channel/' . $uuid . '.json?operation=delete';
		$json = static::getJsonRaw($url);
	}

	/**
	 * Helper assertion to validate correct UUID
	 */
	protected function assertUUID() {
		$this->assertEquals(self::$uuid, (isset($this->json->entity->uuid) ? $this->json->entity->uuid : null),
			"Wrong UUID. Expected " . self::$uuid . ", got " . $this->json->entity->uuid);
	}

	protected function getEntity($uuid = null) {
		$url = static::$context . '/' . (($uuid) ?: static::$uuid) . '.json';
		return $this->getJson($url);
	}

	protected function editProperty($property, $value, $uuid = null) {
		$url = static::$context . '/' . (($uuid) ?: static::$uuid) .
			   '.json?operation=edit&' . $property . '=' . $value;
		return $this->getJson($url);
	}

	function testGetEntity() {
		$this->getEntity();
		$this->assertUUID();
	}

	function testSetBoolPropertyTrue() {
		$this->editProperty('public', '1');
		// $this->getEntity();
		$this->assertSame(true, $this->json->entity->public);

		// ensure this works twice
		$this->editProperty('public', '1');
		// $this->getEntity();
		$this->assertSame(true, $this->json->entity->public);
	}

	function testRemoveProperty() {
		$this->editProperty('public', null);
		// $this->getEntity();
		$this->assertObjectNotHasAttribute('public', $this->json->entity);
	}

	function testSetBoolPropertyFalse() {
		$this->editProperty('public', '0');
		// $this->getEntity();
		$this->assertSame(false, $this->json->entity->public);

		// ensure this works twice
		$this->editProperty('public', '0');
		// $this->getEntity();
		$this->assertSame(false, $this->json->entity->public);
	}


	function testAlterBoolProperty() {
		$this->editProperty('public', '1');
		// $this->getEntity();
		$this->assertSame(true, $this->json->entity->public);

		// ensure this works both ways
		$this->editProperty('public', '0');
		// $this->getEntity();
		$this->assertSame(false, $this->json->entity->public);
	}

}

?>
