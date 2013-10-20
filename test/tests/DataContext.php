<?php
/**
 * Entity tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

require_once('Middleware.php');

abstract class DataContext extends Middleware
{
	static $uuid;

	/**
	 * Initialize context
	 */
	static function setupBeforeClass() {
		parent::setupBeforeClass();
		self::$context = self::$mw . 'data';
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
		$url = self::$mw . 'channel.json?operation=add&title=' . urlencode($title) . '&type=' . urlencode($type);
		if ($resolution) $url .= '&resolution=' . $resolution;
		$json = self::_getJson($url);

		return ((isset($json->entity->uuid)) ? $json->entity->uuid : null);
	}

	static function deleteChannel($uuid) {
		$url = self::$mw . 'channel/' . $uuid . '.json?operation=delete';
		$json = self::_getJson($url);
	}

	protected function addDatapoint($ts, $value, $uuid = null) {
		$url = self::$context . '/' . (($uuid) ?: self::$uuid) . 
			   '.json?operation=add&ts=' . $ts . '&value=' . $value;
		$this->getJson($url);
	}

	protected function _getDatapoints($url, $from = null, $to = null, $group = null, $tuples = null) {
		if ($from)  $url .= 'from=' . $from . '&';
		if ($to) 	$url .= 'to=' . $to . '&';
		if ($group) $url .= 'group=' . $group . '&';
		if ($tuples) $url .= 'tuples=' . $tuples . '&';

		$this->getJson($url);
		$this->assertUUID();
	}

	protected function getDatapoints($from = null, $to = null, $group = null, $tuples = null) {
		$url = self::$context . '/' . self::$uuid . '.json?';
		$this->_getDatapoints($url, $from, $to, $group, $tuples);
	}

	protected function getDatapointsRaw($from = null, $to = null, $group = null, $tuples = null) {
		$url = self::$context . '/' . self::$uuid . '.json?client=raw&';
		$this->_getDatapoints($url, $from, $to, $group, $tuples);
	}

	protected function debug() {
		echo('url: ' . $this->url . "<br/>\n" . print_r($this->json,1) . "<br/>\n");
	}

	/**
	 * Helper assertion to validate correct UUID
	 */
	protected function assertUUID() {
		$this->assertEquals((isset($this->json->data->uuid) ? $this->json->data->uuid : null), self::$uuid, 
			"Wrong UUID. Expected " . self::$uuid . ", got " . $this->json->data->uuid);
	}

	/**
	 * Helper assertion to validate header fields
	 */
	protected function assertHeader($consumption, $average, $rows) {
		$this->assertEquals($consumption, $this->json->data->consumption);
		$this->assertEquals($average, $this->json->data->average);
		$this->assertEquals($rows, $this->json->data->rows);
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
				$this->assertEquals($realTuple[$i], $tuple[$i], 
					$msg . ". Got " . print_r(array_slice($realTuple,0,sizeof($tuple)),1) . 
					", expected " . print_r($tuple,1));
			}
		}
		else $this->assertEquals($realTuple[1], $tuple, 
					$msg . ". Got value " . print_r($realTuple[1],1) . 
					", expected " . $tuple);
	}
}

?>
