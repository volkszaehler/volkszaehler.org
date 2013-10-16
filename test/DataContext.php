<?php
/**
 * Entity tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

require_once('MiddlewareTest.php');

abstract class DataContext extends MiddlewareTest
{
	protected $uuid;

	// abstract channel properties
	protected $title = NULL;
	protected $type = NULL;
	protected $resolution = NULL;

	function __construct() {
		parent::__construct();
		$this->context = self::$mw . 'data';

		if (!$this->uuid) // allow children to override
			$this->uuid = $this->createChannel($this->title, $this->type, $this->resolution);
	}

	function __destruct() {
		// destroy channel
 		if ($this->uuid) // allow children to override
 			$this->deleteChannel($this->uuid);
	}

	public function createChannel($title, $type, $resolution) {
		$url = self::$mw . 'channel.json?operation=add&title=' . $title . '&type=' . $type;
		if ($resolution) $url .= '&resolution=' . $resolution;
		$this->_getJson($url);

		return($this->uuid = $this->json->entity->uuid);
	}

	public function deleteChannel($uuid) {
		$url = self::$mw . 'channel/' . $uuid . '.json?operation=delete';
		$this->_getJson($url);
	}

	protected function addDatapoint($ts, $value) {
		$url = $this->context . '/' . $this->uuid . '.json?operation=add&ts=' . $ts . '&value=' . $value;
		$this->getJson($url);
	}

	protected function _getDatapoints($url, $from = null, $to = null, $group = null) {
		if ($from) $url .= 'from=' . $from . '&';
		if ($to) $url .= 'to=' . $to . '&';
		if ($group) $url .= 'group=' . $group . '&';

		$this->getJson($url);
		$this->assertUUID();
	}

	protected function getDatapoints($from = null, $to = null, $group = null) {
		$url = $this->context . '/' . $this->uuid . '.json?';
		return $this->_getDatapoints($url, $from, $to, $group);
	}

	protected function getDatapointsRaw($from = null, $to = null, $group = null) {
		$url = $this->context . '/' . $this->uuid . '.json?client=raw&';
		return $this->_getDatapoints($url, $from, $to, $group);
	}

	protected function debug() {
		echo('url: ' . $this->url . "<br/>\n" . print_r($this->json,1) . "<br/>\n");
	}

	/**
	 * Helper assertion to validate correct UUID
	 */
	protected function assertUUID() {
		$this->assertTrue($this->json->data->uuid == $this->uuid, "Wrong UUID. Expected " . $this->uuid . ", got " . $this->json->data->uuid);
	}

	/**
	 * Helper assertion to validate correct tuple- either by value only or (sub)tuple as array
	 */
	protected function assertTuple($realTuple, $tuple, $msg = "Tuple mismatch") {
		// got index? retrieve data from tuples
		if (!is_array($realTuple)) {
			$realTuple = $this->json->data->tuples[$realTuple];
		}

		if (is_array($tuple)) {
			for ($i=0; $i<sizeof($tuple); $i++) {
				$this->assertTrue($realTuple[$i] == $tuple[$i], 
					$msg . ". Got " . print_r(array_slice($realTuple,0,sizeof($tuple)),1) . 
					", expected " . print_r($tuple,1));
			}
		}
		else $this->assertTrue($realTuple[1] == $tuple, 
					$msg . ". Got value " . print_r($realTuple[1],1) . 
					", expected " . $tuple);
	}
}

?>
