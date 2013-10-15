<?php
/**
 * Meter tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

require_once('DataBaseFunctions.php');

class DataMeterTest extends DataBaseFunctions
{
	// channel properties
	protected $title = 'Meter';
	protected $type = 'power';
	protected $resolution = 100;

	// data properties
	protected $ts1 = 100000000;
	protected $ts2 = 107200000;	// +2hr
	protected $ts3 = 110800000; // +3hr

	protected $value1 = 1000;
	protected $value2 = 1000;
	protected $value3 = 2000;

	// function __destruct() {	}

	function getConsumption($rawValue) {
		return($rawValue / $this->resolution * 1000);
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

	function testGetOneDatapoint() {
		$this->getDatapoints($this->ts1, $this->ts2);

		$this->assertTrue($this->json->data->from == $this->ts1, "<from> doesn't match request");
		$this->assertTrue($this->json->data->to == $this->ts2, "<to> doesn't match request");

		$this->assertTrue($this->json->data->consumption == 0);
		$this->assertTrue($this->json->data->average == 0);
		$this->assertTrue($this->json->data->rows == 1);

		$this->assertFalse(isset($this->json->data->tuples));
	}

	function testAddAnotherDatapoint() {
		$this->addDatapoint($this->ts2, $this->value2);
	}

	/**
	 * @todo getting interval start timestamp seems odd as the value is discarded?
	 */
	function testGetTwoDatapoints() {
		$this->getDatapoints($this->ts1, $this->ts2);

		$this->assertTrue($this->json->data->from == $this->ts1, "<from> doesn't match request");
		$this->assertTrue($this->json->data->to == $this->ts2, "<to> doesn't match request");

		$consumption = $this->getConsumption($this->value2);
		$average = $this->getAverage($this->ts1, $this->ts2, $consumption);

		$this->assertTrue($this->json->data->consumption == $consumption);
		$this->assertTrue($this->json->data->average == $average);
		$this->assertTrue($this->json->data->rows == 2);

		// timestamp of interval start
		$this->assertTrue(sizeof($this->json->data->tuples) == 1);

		// equivalent
		$this->assertTuple(0, $this->getTuple($this->ts1, $this->ts2, $this->value2));
	}

	/**
	 * get data points outside request range
	 */
	function testGetEdgeDatapoints() {
		$this->getDatapoints($this->ts2, $this->ts2 + 1000);

		$this->assertTrue($this->json->data->from == $this->ts1, "<from> doesn't match request");
		$this->assertTrue($this->json->data->to == $this->ts2, "<to> doesn't match request");

		$consumption = $this->getConsumption($this->value2);
		$average = $this->getAverage($this->ts1, $this->ts2, $consumption);

		$this->assertTrue($this->json->data->consumption == $consumption);
		$this->assertTrue($this->json->data->average == $average);
		$this->assertTrue($this->json->data->rows == 2);
	}

	/**
	 * only get data points inside request range - shouldn't find anything
	 */
	function testGetEdgeDatapointsRaw() {
		$this->getDatapointsRaw($this->ts2 + 1, $this->ts2 + 1000);

		$this->assertTrue($this->json->data->from == $this->ts2 + 1, "<from> doesn't match request");
		$this->assertTrue($this->json->data->to == $this->ts2 + 1000, "<to> doesn't match request");

		$this->assertTrue($this->json->data->consumption == 0);
		$this->assertTrue($this->json->data->average == 0);
		$this->assertTrue($this->json->data->rows == 0);
	}

	/**
	 * @todo getting interval start timestamp seems odd as the value is discarded?
	 */
	function testThreeDatapointsAverageAndConsumption() {
		// add 3rd datapoint
		$this->addDatapoint($this->ts3, $this->value3);

		// get data
		$this->getDatapoints($this->ts1, $this->ts3);

		$this->assertTrue($this->json->data->from == $this->ts1, "<from> doesn't match request");
		$this->assertTrue($this->json->data->to == $this->ts3, "<to> doesn't match request");

		$consumption = $this->getConsumption($this->value2 + $this->value3);
		$average = $this->getAverage($this->ts1, $this->ts3, $consumption);

		$this->assertTrue($this->json->data->consumption == $consumption);
		$this->assertTrue($this->json->data->average == $average);
		$this->assertTrue($this->json->data->rows == 3);

		// timestamp of interval start
		$this->assertTrue(sizeof($this->json->data->tuples) == 2);

		$this->assertTuple(0, $this->getTuple($this->ts1, $this->ts2, $this->value2));
		$this->assertTuple(1, $this->getTuple($this->ts2, $this->ts3, $this->value3));

		$this->assertTuple($this->json->data->min, $this->getTuple($this->ts1, $this->ts2, $this->value2), "<min> tuple mismatch");
		$this->assertTuple($this->json->data->max, $this->getTuple($this->ts2, $this->ts3, $this->value3), "<max> tuple mismatch");
	}

	function testThreeDatapointsAggregationByHour() {
		// get data
		$this->getDatapoints($this->ts1, $this->ts3, "hour");

		$this->assertTrue($this->json->data->from == $this->ts1, "<from> doesn't match request");
		$this->assertTrue($this->json->data->to == $this->ts3, "<to> doesn't match request");

		$consumption = $this->getConsumption($this->value2 + $this->value3);
		$average = $this->getAverage($this->ts1, $this->ts3, $consumption);

		$this->assertTrue($this->json->data->consumption == $consumption);
		$this->assertTrue($this->json->data->average == $average);
		$this->assertTrue($this->json->data->rows == 3);

		// timestamp of interval start
		$this->assertTrue(sizeof($this->json->data->tuples) == 2);

		$this->assertTuple(0, $this->getTuple($this->ts1, $this->ts2, $this->value2));
		$this->assertTuple(1, $this->getTuple($this->ts2, $this->ts3, $this->value3));

		$this->assertTuple($this->json->data->min, $this->getTuple($this->ts1, $this->ts2, $this->value2), "<min> tuple mismatch");
		$this->assertTuple($this->json->data->max, $this->getTuple($this->ts2, $this->ts3, $this->value3), "<max> tuple mismatch");
	}
}

?>
