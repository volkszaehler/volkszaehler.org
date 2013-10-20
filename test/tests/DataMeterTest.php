<?php
/**
 * Meter tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

require_once('DataContext.php');

class DataMeterTest extends DataContext
{
	// channel properties
	static $resolution = 100;

	// data properties
	protected $ts1 = 100000000;
	protected $ts2 = 107200000;	// +2hr
	protected $ts3 = 110800000; // +3hr

	protected $value1 = 1000;
	protected $value2 = 1000;
	protected $value3 = 2000;

	/**
	 * Create channel
	 */
	static function setupBeforeClass() {
		parent::setupBeforeClass();
		self::$uuid = self::createChannel('Meter', 'power', self::$resolution);
	}

	function getConsumption($rawValue) {
		return($rawValue / self::$resolution * 1000);
	}

	function getAverage($from, $to, $periodValue) {
		return($periodValue * 3600000 / ($to - $from));
	}

	function getTuple($from, $to, $rawValue, $count = NULL) {
		$consumption = $this->getConsumption($rawValue);
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

		$this->assertFalse(isset($this->json->data->tuples));
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

		$consumption = $this->getConsumption($this->value2);
		$average = $this->getAverage($this->ts1, $this->ts2, $consumption);
		$this->assertHeader($consumption, $average, 2);

		// number of rows
		$this->assertCount(1, $this->json->data->tuples);

		// equivalent
		$this->assertTuple(0, $this->getTuple($this->ts1, $this->ts2, $this->value2));
	}

	/**
	 * get data points outside request range
	 *
	 * @depends testAddDatapoint
	 * @depends testAddAnotherDatapoint
	 */
	function testGetEdgeDatapoints() {
		$this->getDatapoints($this->ts2, $this->ts2 + 1000);

		$this->assertEquals($this->ts1, $this->json->data->from, "<from> doesn't match request");
		$this->assertEquals($this->ts2, $this->json->data->to, "<to> doesn't match request");

		$consumption = $this->getConsumption($this->value2);
		$average = $this->getAverage($this->ts1, $this->ts2, $consumption);
		$this->assertHeader($consumption, $average, 2);
	}

	/**
	 * only get data points inside request range - shouldn't find anything
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

		$consumption = $this->getConsumption($this->value2 + $this->value3);
		$average = $this->getAverage($this->ts1, $this->ts3, $consumption);
		$this->assertHeader($consumption, $average, 3);

		// timestamp of interval start
		$this->assertCount(2, $this->json->data->tuples);

		$this->assertTuple(0, $this->getTuple($this->ts1, $this->ts2, $this->value2));
		$this->assertTuple(1, $this->getTuple($this->ts2, $this->ts3, $this->value3));

		$this->assertTuple($this->getTuple($this->ts1, $this->ts2, $this->value2), $this->json->data->min, "<min> tuple mismatch");
		$this->assertTuple($this->getTuple($this->ts2, $this->ts3, $this->value3), $this->json->data->max, "<max> tuple mismatch");
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

		$consumption = $this->getConsumption($this->value2 + $this->value3);
		$average = $this->getAverage($this->ts1, $this->ts3, $consumption);
		$this->assertHeader($consumption, $average, 3);

		// timestamp of interval start
		$this->assertCount(2, $this->json->data->tuples);

		$this->assertTuple(0, $this->getTuple($this->ts1, $this->ts2, $this->value2));
		$this->assertTuple(1, $this->getTuple($this->ts2, $this->ts3, $this->value3));

		$this->assertTuple($this->getTuple($this->ts1, $this->ts2, $this->value2), $this->json->data->min, "<min> tuple mismatch");
		$this->assertTuple($this->getTuple($this->ts2, $this->ts3, $this->value3), $this->json->data->max, "<max> tuple mismatch");
	}
}

?>
