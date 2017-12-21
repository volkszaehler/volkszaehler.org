<?php
/**
 * Entity tests
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2017, The volkszaehler.org project
 * @license https://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace Tests;

class ChannelTest extends Middleware
{
	protected $uuid;

	function createChannel($title, $type, $resolution) {
		$url = '/channel.json';
		$params = array(
			'operation' => 'add',
			'title' => $title,
			'type' => $type
		);
		if ($resolution) {
			$params['resolution'] = $resolution;
		}
		$this->getJson($url, $params);

		return($this->uuid = (isset($this->json->entity->uuid)) ? $this->json->entity->uuid : null);
	}

	function channelProvider() {
		return array(
			array('Power', 'power', 1000),					// Meter
			array('Electric Meter', 'electric meter', 10),	// Counter
			array('Sensor', 'powersensor', null)			// Sensor
		);
	}

	function testExistence() {
		$this->assertNotNull($this->getJson('/channel.json')->channels);
		$this->assertInternalType('array', $this->getJson('/channel.json')->channels);
	}

	/**
     * @dataProvider channelProvider
     */
	function testChannelLifecycle($title, $type, $resolution) {
		// create
		$uuid = $this->createChannel($title, $type, $resolution);

		$this->assertTrue(isset($this->json->entity), "Expected <entity> got none.");
		$this->assertTrue(isset($this->json->entity->uuid));

		$this->assertTrue($this->json->entity->type == $type);
		$this->assertTrue($this->json->entity->title == $title);
		if ($resolution) $this->assertTrue($this->json->entity->resolution == $resolution);

		// search
		$url = '/channel/' . $this->uuid . '.json';
		$this->getJson($url);

		$this->assertTrue(isset($this->json->entity), "Expected <entity> got none");
		$this->assertTrue($this->json->entity->uuid == $uuid);
		$this->assertTrue($this->json->entity->type == $type);
		$this->assertTrue($this->json->entity->title == $title);
		if ($resolution) $this->assertTrue($this->json->entity->resolution == $resolution);

		// test updating for first channel type only
		if ($title == 'Power') {
			$url = '/channel/' . $this->uuid . '.json?operation=edit&title='.$title.'Updated'.'&type='.'Sensor'.'&resolution='.($resolution*10);
			$this->getJson($url);

			$this->assertTrue(isset($this->json->entity), "Expected <entity> got none");
			$this->assertTrue($this->json->entity->uuid == $uuid);
			$this->assertTrue($this->json->entity->type == $type, "<type> must not be changed");
			$this->assertTrue($this->json->entity->title == $title.'Updated');
			$this->assertTrue($this->json->entity->resolution == $resolution*10);
		}

		// delete
		$url = '/channel/' . $uuid . '.json?operation=delete';
		$this->getJson($url);
	}
}

?>
