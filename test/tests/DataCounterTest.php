<?php
/**
 * Meter tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

require_once('DataContext.php');

class DataCounterTest extends DataContext
{
	static $resolution = 100;

	protected $ts1 = 100000000;
	protected $ts2 = 103600000;
	protected $ts3 = 107200000;

	protected $value1 = 1000;
	protected $value2 = 3000;
	protected $value3 = 7000;

	/**
	 * Create channel
	 */
	static function setupBeforeClass() {
		parent::setupBeforeClass();
		self::$uuid = self::createChannel('Counter', 'electric meter', self::$resolution);
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

	function testAddDatapoint() {
		$this->addDatapoint($this->ts1, $this->value1);

		// doesn't return any data
		$this->assertFalse(isset($this->json->data));
	}

	/**
	 * @depends testAddDatapoint
	 */
	function testGetOneDatapoint() {
		$this->getDatapoints($this->ts1, $this->ts2);

		// from/to expected to match single datapoint
		$this->assertEquals($this->ts1, $this->json->data->from, "<from> doesn't match request");
		$this->assertEquals($this->ts1, $this->json->data->to, "<to> doesn't match request");
		$this->assertHeader(0, 0, 1);
	}

	/**
	 * @depends testAddDatapoint
	 */
	function testAddAnotherDatapoint() {
		$this->addDatapoint($this->ts2, $this->value2);
	}

	/**
	 * @depends testAddDatapoint
	 * @depends testAddAnotherDatapoint
	 *
	 * @todo getting interval start timestamp seems odd as the value is discarded?
	 */
	function testGetTwoDatapoints() {
		$this->getDatapoints($this->ts1, $this->ts2);

		$this->assertEquals($this->ts1, $this->json->data->from, "<from> doesn't match request");
		$this->assertEquals($this->ts2, $this->json->data->to, "<to> doesn't match request");

		$consumption = $this->getConsumption($this->value1, $this->value2);
		$average = $this->getAverage($this->ts1, $this->ts2, $consumption);
		$this->assertHeader($consumption, $average, 2);

		// number of rows
		$this->assertCount(1, $this->json->data->tuples);

		// timestamp of interval start
		$this->assertTuple(0, $this->getTuple($this->ts1, $this->ts2, $this->value1, $this->value2));
	}

	/**
	 * get data points outside request range
	 *
	 * @depends testAddDatapoint
	 * @depends testAddAnotherDatapoint
	 */
	function testGetEdgeDatapoints() {
		$this->getDatapoints($this->ts2 + 1, $this->ts2 + 1000);

		$this->assertEquals($this->ts1, $this->json->data->from, "<from> doesn't match request");
		$this->assertEquals($this->ts2, $this->json->data->to, "<to> doesn't match request");

		$consumption = $this->getConsumption($this->value1, $this->value2);
		$average = $this->getAverage($this->ts1, $this->ts2, $consumption);
		$this->assertHeader($consumption, $average, 2);
	}

	/**
	 * only get data points inside request range
	 *
	 * @depends testAddDatapoint
	 * @depends testAddAnotherDatapoint
	 */
	function testGetEdgeDatapointsRaw() {
		$this->getDatapointsRaw($this->ts2 + 1, $this->ts2 + 1000);

		// from/to expected 0 if rows=0
		$this->assertEquals(0, $this->json->data->from, "<from> doesn't match request");
		$this->assertEquals(0, $this->json->data->to, "<to> doesn't match request");
		$this->assertHeader(0, 0, 0);
	}

	/**
	 * @todo getting interval start timestamp seems odd as the value is discarded?
	 *
	 * @depends testAddDatapoint
	 * @depends testAddAnotherDatapoint
	 */
	function testThreeDatapointsAverageAndConsumption() {
		// add 3rd datapoint
		$this->addDatapoint($this->ts3, $this->value3);

		// get data
		$this->getDatapoints($this->ts1, $this->ts3);

		$this->assertEquals($this->ts1, $this->json->data->from, "<from> doesn't match request");
		$this->assertEquals($this->ts3, $this->json->data->to, "<to> doesn't match request");

		$consumption = $this->getConsumption($this->value1, $this->value3);
		$average = $this->getAverage($this->ts1, $this->ts3, $consumption);
		$this->assertHeader($consumption, $average, 3);

		// timestamp of interval start
		$this->assertTuple(0, $this->getTuple($this->ts1, $this->ts2, $this->value1, $this->value2));
		$this->assertTuple(1, $this->getTuple($this->ts2, $this->ts3, $this->value2, $this->value3));

		$this->assertTuple($this->getTuple($this->ts1, $this->ts2, $this->value1, $this->value2), $this->json->data->min, "<min> tuple mismatch");
		$this->assertTuple($this->getTuple($this->ts2, $this->ts3, $this->value2, $this->value3), $this->json->data->max, "<max> tuple mismatch");
	}
	
	/**
	 * @todo getting interval start timestamp seems odd as the value is discarded?
	 *
	 * @depends testThreeDatapointsAverageAndConsumption
	 */
	function testThreeDatapointsAggregationByHour() {
		// get data
		$this->getDatapoints($this->ts1, $this->ts3, "hour");

		$this->assertEquals($this->ts1, $this->json->data->from, "<from> doesn't match request");
		$this->assertEquals($this->ts3, $this->json->data->to, "<to> doesn't match request");

		$consumption = $this->getConsumption($this->value1, $this->value3);
		$average = $this->getAverage($this->ts1, $this->ts3, $consumption);
		$this->assertHeader($consumption, $average, 3);

		// timestamp of interval start
		$this->assertTuple(0, $this->getTuple($this->ts1, $this->ts2, $this->value1, $this->value2));
		$this->assertTuple(1, $this->getTuple($this->ts2, $this->ts3, $this->value2, $this->value3));

		$this->assertTuple($this->getTuple($this->ts1, $this->ts2, $this->value1, $this->value2), $this->json->data->min, "<min> tuple mismatch");
		$this->assertTuple($this->getTuple($this->ts2, $this->ts3, $this->value2, $this->value3), $this->json->data->max, "<max> tuple mismatch");
	}
}

?>
