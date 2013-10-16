<?php
/**
 * Entity tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

require_once('Middleware.php');

class ChannelTest extends Middleware
{
	protected $uuid;

	function __construct($name = NULL, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
		$this->context = self::$mw . 'channel';
	}

	public function createChannel($title, $type, $resolution) {
		$url = $this->context . '.json?operation=add&title=' . urlencode($title) . '&type=' . urlencode($type);
		if ($resolution) $url .= '&resolution=' . $resolution;
		$this->getJson($url);

		return($this->uuid = (isset($this->json->entity->uuid)) ? $this->json->entity->uuid : null);
	}

	function channelProvider() {
		return array(
			array('Power', 'power', 1000),					// Meter
			array('Electric Meter', 'electric meter', 10),	// Counter
			array('Sensor', 'powersensor', null)			// Sensor
		);
	}

	function testListChannels() {
		$url = $this->context . '.json';
		$this->getJson($url);
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
		$url = $this->context . '/' . $this->uuid . '.json';
		$this->getJson($url);

		$this->assertTrue(isset($this->json->entity), "Expected <entity> got none");
		$this->assertTrue($this->json->entity->uuid == $uuid);
		$this->assertTrue($this->json->entity->type == $type);
		$this->assertTrue($this->json->entity->title == $title);
		if ($resolution) $this->assertTrue($this->json->entity->resolution == $resolution);

		// test updating for first channel type only
		if ($title == 'Power') {
			$url = $this->context . '/' . $this->uuid . '.json?operation=edit&title='.$title.'Updated'.'&type='.'Sensor'.'&resolution='.($resolution*10);
			$this->getJson($url);

			$this->assertTrue(isset($this->json->entity), "Expected <entity> got none");
			$this->assertTrue($this->json->entity->uuid == $uuid);
			$this->assertTrue($this->json->entity->type == $type, "<type> must not be changed");
			$this->assertTrue($this->json->entity->title == $title.'Updated');
			$this->assertTrue($this->json->entity->resolution == $resolution*10);
		}

		// delete
		$url = $this->context . '/' . $uuid . '.json?operation=delete';
		$this->getJson($url);

		$url = $this->context . '/' . $this->uuid . '.json';
		$this->getJson($url, "No entity found with UUID: '" . $this->uuid . "'");
	}
}

?>
