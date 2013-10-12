<?php
/**
 * Entity tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

require_once('MiddlewareTest.php');

class ChannelTest extends MiddlewareTest
{
	protected $uuid;

	protected $title = 'Power';
	protected $type = 'power';
	protected $resolution = 1000;	

	protected $title2 = 'Electric Meter';
	protected $type2 = 'electric meter';
	protected $resolution2 = 10;	

	protected $title3 = 'Sensor';
	protected $type3 = 'powersensor';

	function __construct() {
		parent::__construct();
		$this->context = self::$mw . 'channel';
	}

	public function createChannel($title, $type, $resolution = NULL) {
		$url = $this->context . '.json?operation=add&title=' . $title . '&type=' . $type;
		if ($resolution) $url .= '&resolution=' . $resolution;
		$this->getJson($url);

		$this->assertTrue(isset($this->json->entity->uuid));
		return($this->uuid = $this->json->entity->uuid);
	}

	public function deleteChannel($uuid) {
		$url = $this->context . '/' . $uuid . '.json?operation=delete';
		$this->getJson($url);
	}

	function testListChannel() {
		$url = $this->context . '.json';
		$this->getJson($url);
	}

	function testCreateMeterChannel() {
		$this->createChannel($this->title, $this->type, $this->resolution);

		if ($this->assertTrue(isset($this->json->entity), "Expected <entity> got none.")) {
			$this->assertTrue($this->json->entity->type == $this->type);
			$this->assertTrue($this->json->entity->title == $this->title);
			$this->assertTrue($this->json->entity->resolution == $this->resolution);
		}
	}

	function testFindChannel() {
		$url = $this->context . '/' . $this->uuid . '.json';
		$this->getJson($url);

		if ($this->assertTrue(isset($this->json->entity), "Expected <entity> got none")) {
			$this->assertTrue($this->json->entity->uuid == $this->uuid);
			$this->assertTrue($this->json->entity->type == $this->type);
			$this->assertTrue($this->json->entity->title == $this->title);
			$this->assertTrue($this->json->entity->resolution == $this->resolution);
		}
	}

	/**
	 * @todo fix mw silently failing when changing meter type 
	 */
	function testUpdateChannel() {
		$url = $this->context . '/' . $this->uuid . '.json?operation=edit&title='.$this->title2.'&type='.$this->type2.'&resolution='.$this->resolution2;
		$this->getJson($url);

		if ($this->assertTrue(isset($this->json->entity), "Expected <entity> got none")) {
			$this->assertTrue($this->json->entity->uuid == $this->uuid);
			$this->assertTrue($this->json->entity->type == $this->type2, "<type> could not be changed");
			$this->assertTrue($this->json->entity->title == $this->title2);
			$this->assertTrue($this->json->entity->resolution == $this->resolution2);
		}
	}

	function testDeleteChannel() {
		$this->deleteChannel($this->uuid);

		$url = $this->context . '/' . $this->uuid . '.json';
		$this->getJson($url, "No entity found with UUID: '" . $this->uuid . "'");
	}

	function testCreateCounterChannel() {
		$this->createChannel($this->title2, $this->type2, $this->resolution2);

		if ($this->assertTrue(isset($this->json->entity), "Expected <entity> got none")) {
			$this->assertTrue($this->json->entity->type == $this->type2);
			$this->assertTrue($this->json->entity->title == $this->title2);
			$this->assertTrue($this->json->entity->resolution == $this->resolution2);
		}

		$this->deleteChannel($this->uuid);
	}

	function testCreateSensorChannel() {
		$this->createChannel($this->title3, $this->type3);

		if ($this->assertTrue(isset($this->json->entity), "Expected <entity> got none")) {
			$this->assertTrue($this->json->entity->type == $this->type3);
			$this->assertTrue($this->json->entity->title == $this->title3);
		}

		$this->deleteChannel($this->uuid);
	}
}

?>
