<?php
/**
 * Meter tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

require_once('DataContext.php');

class DataInterpreterTest extends DataContext
{
	static $resolution = 100;

	/**
	 * Create channel
	 */
	static function setupBeforeClass() {
		parent::setupBeforeClass();
		self::$uuid = self::createChannel('Counter', 'electric meter', self::$resolution);
	}

	static function tearDownAfterClass() {
		self::$uuid = null;
		parent::tearDownAfterClass();
	}

	function getConsumption($fromValue, $toValue) {
		return(($toValue - $fromValue) / self::$resolution * 1000);
	}

	function getAverage($from, $to, $periodValue) {
		return($periodValue * 3600000 / ($to - $from));
	}

	function getTuple($from, $to, $fromValue, $toValue, $count = NULL) {
		$consumption = $this->getConsumption($fromValue, $toValue);
		$average = $this->getAverage($from, $to, $consumption);

		$result = array($from, $average);
		if ($count) $result[] = $count;

		// timestamp of interval start
		return($result);
	}

	// @todo
	function testUnorderedDatapoints() {
		$this->addDatapoint(strtotime('1 days ago  0:00') * 1000, 0);
		$this->addDatapoint(strtotime('1 days ago 23:59') * 1000, 100);
		$this->addDatapoint(strtotime('1 days ago 18:00') * 1000, 100);
		$this->addDatapoint(strtotime('1 days ago  6:00') * 1000, 10);
		$this->addDatapoint(strtotime('1 days ago 12:00') * 1000, 10);

		$this->getDatapointsRaw(strtotime('1 days ago 0:00') * 1000, strtotime('today') * 1000, null, 2);

		print_r($this->json);
	}
}

?>
