<?php
/**
 * Meter tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

require_once('DataBaseFunctions.php');

class DataCounterTest extends DataBaseFunctions
{
	// DataBaseFunctions overridde
	protected $title = 'Counter';
	protected $type = 'electric meter';
	protected $resolution = 100;	

	protected $ts1 = 100000000;
	protected $ts2 = 103600000;
	protected $ts3 = 107200000;

	protected $value1 = 1000;
	protected $value2 = 3000;
	protected $value3 = 7000;

	function getConsumption($fromValue, $toValue) {
		return(($toValue - $fromValue) / $this->resolution * 1000);
	}

	function getAverage($from, $to, $periodValue) {
		return($periodValue * $this->hour / ($to - $from));
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
		$url = $this->context . '/' . $this->uuid . '.json?operation=add&ts=' . $this->ts1 . '&value=' . $this->value1;
		$this->getJson($url);

		// doesn't return any data
		$this->assertFalse(isset($this->json->data));
	}

	function testGetOneDatapoint() {
		$url = $this->context . '/' . $this->uuid . '.json?from=' . $this->ts1 . "&to=" . $this->ts2;
		$this->getJson($url);

		$this->assertUUID();

		$this->assertTrue($this->json->data->from == $this->ts1, "<from> doesn't match request");
		$this->assertTrue($this->json->data->to == $this->ts2, "<to> doesn't match request");

		$this->assertTrue($this->json->data->consumption == 0);
		$this->assertTrue($this->json->data->average == 0);
		$this->assertTrue($this->json->data->rows == 1);
	}

	function testAddAnotherDatapoint() {
		$url = $this->context . '/' . $this->uuid . '.json?operation=add&ts=' . $this->ts2 . '&value=' . $this->value2;
		$this->getJson($url);
	}

	/**
	 * @todo getting interval start timestamp seems odd as the value is discarded?
	 */
	function testGetTwoDatapoints() {
		$url = $this->context . '/' . $this->uuid . '.json?from=' . $this->ts1 . "&to=" . $this->ts2;
		$this->getJson($url);

		$this->assertUUID();

		$this->assertTrue($this->json->data->from == $this->ts1, "<from> doesn't match request");
		$this->assertTrue($this->json->data->to == $this->ts2, "<to> doesn't match request");

		$consumption = $this->getConsumption($this->value1, $this->value2);
		$average = $this->getAverage($this->ts1, $this->ts2, $consumption);

		$this->assertTrue($this->json->data->consumption == $consumption);
		$this->assertTrue($this->json->data->average == $average);
		$this->assertTrue($this->json->data->rows == 2);

		// timestamp of interval start
		$this->assertTuple(0, $this->getTuple($this->ts1, $this->ts2, $this->value1, $this->value2));
	}

	/**
	 * get data points outside request range
	 */
	function testGetEdgeDatapoints() {
		$url = $this->context . '/' . $this->uuid . '.json?from=' . ($this->ts2 + 1). "&to=" . ($this->ts2 + 1000);
		$this->getJson($url);

		$this->assertUUID();

		$this->assertTrue($this->json->data->from == $this->ts1, "<from> doesn't match request");
		$this->assertTrue($this->json->data->to == $this->ts2, "<to> doesn't match request");

		$consumption = $this->getConsumption($this->value1, $this->value2);
		$average = $this->getAverage($this->ts1, $this->ts2, $consumption);

		$this->assertTrue($this->json->data->consumption == $consumption);
		$this->assertTrue($this->json->data->average == $average);
		$this->assertTrue($this->json->data->rows == 2);
	}

	/**
	 * only get data points inside request range
	 */
	function testGetEdgeDatapointsRaw() {
		$url = $this->context . '/' . $this->uuid . '.json?from=' . ($this->ts2 + 1). "&to=" . ($this->ts2 + 1000) . "&client=raw";
		$this->getJson($url);

		$this->assertUUID();

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
		// add 3rd datapoint with double value
		$url = $this->context . '/' . $this->uuid . '.json?operation=add&ts=' . $this->ts3 . '&value=' . $this->value3;
		$this->getJson($url);

		// get data
		$url = $this->context . '/' . $this->uuid . '.json?from=' . $this->ts1 . "&to=" . $this->ts3;
		$this->getJson($url);

		$this->assertUUID();

		$this->assertTrue($this->json->data->from == $this->ts1, "<from> doesn't match request");
		$this->assertTrue($this->json->data->to == $this->ts3, "<to> doesn't match request");

		$consumption = $this->getConsumption($this->value1, $this->value3);
		$average = $this->getAverage($this->ts1, $this->ts3, $consumption);

		$this->assertTrue($this->json->data->consumption == $consumption);
		$this->assertTrue($this->json->data->average == $average);
		$this->assertTrue($this->json->data->rows == 3);

		// timestamp of interval start
		$this->assertTuple(0, $this->getTuple($this->ts1, $this->ts2, $this->value1, $this->value2));
		$this->assertTuple(1, $this->getTuple($this->ts2, $this->ts3, $this->value2, $this->value3));

		$this->assertTuple($this->json->data->min, $this->getTuple($this->ts1, $this->ts2, $this->value1, $this->value2), "<min> tuple mismatch");
		$this->assertTuple($this->json->data->max, $this->getTuple($this->ts2, $this->ts3, $this->value2, $this->value3), "<max> tuple mismatch");
	}

	function testThreeDatapointsAggregationByHour() {
		// get data
		$url = $this->context . '/' . $this->uuid . '.json?from=' . $this->ts1 . "&to=" . $this->ts3 . "&group=hour";
		$this->getJson($url);

		$this->assertUUID();

		$this->assertTrue($this->json->data->from == $this->ts1, "<from> doesn't match request");
		$this->assertTrue($this->json->data->to == $this->ts3, "<to> doesn't match request");

		$consumption = $this->getConsumption($this->value1, $this->value3);
		$average = $this->getAverage($this->ts1, $this->ts3, $consumption);

		$this->assertTrue($this->json->data->consumption == $consumption);
		$this->assertTrue($this->json->data->average == $average);
		$this->assertTrue($this->json->data->rows == 3);

		// timestamp of interval start
		$this->assertTuple(0, $this->getTuple($this->ts1, $this->ts2, $this->value1, $this->value2));
		$this->assertTuple(1, $this->getTuple($this->ts2, $this->ts3, $this->value2, $this->value3));

		$this->assertTuple($this->json->data->min, $this->getTuple($this->ts1, $this->ts2, $this->value1, $this->value2), "<min> tuple mismatch");
		$this->assertTuple($this->json->data->max, $this->getTuple($this->ts2, $this->ts3, $this->value2, $this->value3), "<max> tuple mismatch");
	}
}

?>
