<?php
/**
 * Meter tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

class CounterTest extends Data
{
	// channel properties
	static $resolution = 100;

	// data properties
	protected $ts1 =  3600000;
	protected $ts2 =  7200000; // +1hr
	protected $ts3 = 10800000; // +2hr
	protected $ts4 = 12600000; // +2.5

	protected $value1 = 1000;
	protected $value2 = 3000;
	protected $value3 = 7000;
	protected $value4 = 8000;

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

	function makeTuple($from, $to, $fromValue, $toValue, $count = NULL) {
		$consumption = $this->getConsumption($fromValue, $toValue);
		$average = $this->getAverage($from, $to, $consumption);

		$result = array($to, $average);
		if ($count) $result[] = $count;

		// timestamp of interval start
		return($result);
	}

	function testAddTuple() {
		$this->addTuple($this->ts1, $this->value1);

		// doesn't return any data
		$this->assertFalse(isset($this->json->data));
	}

	/**
	 * @depends testAddTuple
	 */
	function testGetTuple() {
		$this->getTuples($this->ts1-1, $this->ts2);

		// from/to expected to match actual data instead of request range
		$this->assertFromTo($this->ts1, $this->ts1);
		$this->assertHeader(0, 0, 1);

		// tuples
		$this->assertFalse(isset($this->json->data->tuples));
	}

	/**
	 * @depends testAddTuple
	 * @depends testGetTuple
	 */
	function testGetMultiple() {
		$this->addTuple($this->ts2, $this->value2);
		$this->getTuples($this->ts1, $this->ts2);

		$this->assertFromTo($this->ts1, $this->ts2);

		$consumption = $this->getConsumption($this->value1, $this->value2);
		$average = $this->getAverage($this->ts1, $this->ts2, $consumption);
		$this->assertHeader($consumption, $average, 2);

		// tuples
		$this->assertCount(1, $this->json->data->tuples);

		$this->assertTuple(0, $this->makeTuple($this->ts1, $this->ts2, $this->value1, $this->value2));
	}

	/**
	 * test if from=0 gets all tuples
	 *
	 * @depends testGetMultiple
	 */
	function testGetAllTuples() {
		$this->getTuples(0);

		$rows = 2;
		$this->assertEquals($rows, $this->json->data->rows);
		$this->assertCount($rows - 1, $this->json->data->tuples);
	}

	/**
	 * test if from=now gets exactly the last tuple
	 *
	 * @depends testGetMultiple
	 */
	function testGetLastTuple() {
		$tuples = $this->getTuplesRaw($this->ts1, $this->ts2);
		$tuplesNow = $this->getTuples("now");

		$this->assertEquals($tuples, $tuplesNow);
	}

	/**
	 * get data points outside request range
	 *
	 * @depends testGetMultiple
	 */
	function testGetEdgeDatapoints() {
		$this->getTuples($this->ts2, $this->ts2 + 1000);

		$this->assertFromTo($this->ts1, $this->ts2);

		$consumption = $this->getConsumption($this->value1, $this->value2);
		$average = $this->getAverage($this->ts1, $this->ts2, $consumption);
		$this->assertHeader($consumption, $average, 2);
	}

	/**
	 * only get data points inside request range
	 *
	 * @depends testGetEdgeDatapoints
	 */
	function testGetEdgeDatapointsRaw() {
		$this->getTuplesRaw($this->ts2 + 1, $this->ts2 + 1000);

		// from/to expected 0 if rows=0
		$this->assertFromTo(0, 0);
		$this->assertHeader(0, 0, 0);

		// tuples
		$this->assertFalse(isset($this->json->data->tuples));
	}

	/**
	 * @depends testGetMultiple
	 */
	function testMultipleAverageAndConsumption() {
		// add 3rd datapoint
		$this->addTuple($this->ts3, $this->value3);

		// get data
		$this->getTuples($this->ts1, $this->ts3);

		$this->assertFromTo($this->ts1, $this->ts3);

		$consumption = $this->getConsumption($this->value1, $this->value3);
		$average = $this->getAverage($this->ts1, $this->ts3, $consumption);
		$this->assertHeader($consumption, $average, 3);

		$this->assertMinMax(
			$this->makeTuple($this->ts1, $this->ts2, $this->value1, $this->value2),
			$this->makeTuple($this->ts2, $this->ts3, $this->value2, $this->value3));

		// tuples
		$this->assertCount(2, $this->json->data->tuples);

		$this->assertTuple(0, $this->makeTuple($this->ts1, $this->ts2, $this->value1, $this->value2));
		$this->assertTuple(1, $this->makeTuple($this->ts2, $this->ts3, $this->value2, $this->value3));
	}

	/**
	 * @depends testMultipleAverageAndConsumption
	 */
	function testMultipleGroupByHour() {
		// get data
		$this->getTuples($this->ts1, $this->ts3, "hour");

		$this->assertFromTo($this->ts1, $this->ts3);

		$consumption = $this->getConsumption($this->value1, $this->value3);
		$average = $this->getAverage($this->ts1, $this->ts3, $consumption);
		$this->assertHeader($consumption, $average, 3);

		$this->assertMinMax(
			$this->makeTuple($this->ts1, $this->ts2, $this->value1, $this->value2),
			$this->makeTuple($this->ts2, $this->ts3, $this->value2, $this->value3));

		// tuples
		$this->assertCount(2, $this->json->data->tuples);

		$this->assertTuple(0, $this->makeTuple($this->ts1, $this->ts2, $this->value1, $this->value2));
		$this->assertTuple(1, $this->makeTuple($this->ts2, $this->ts3, $this->value2, $this->value3));
	}

	/**
	 * @depends testMultipleAverageAndConsumption
	 */
	function testMultiplePackaging() {
		// get data - 1 tuple
		$this->getTuples($this->ts1, $this->ts3, "", 1);

		$this->assertFromTo($this->ts1, $this->ts3);

		// even when packaged, raw number of DB rows (3) is returned
		$consumption = $this->getConsumption($this->value1, $this->value3);
		$average = $this->getAverage($this->ts1, $this->ts3, $consumption);
		// 3 vs 1 result rows depends on if Interpreter->runSQL or DataIterator->next does iteration
		$this->assertHeader($consumption, $average/*, 3*/);

		// min/max are identical with the one tuple
		$this->assertMinMax($this->makeTuple($this->ts1, $this->ts3, $this->value1, $this->value3));

		// out of the 3 tuples, 1 has been used as starting point, the 2 remaining ones are packaged
		$this->assertCount(1, $this->json->data->tuples);

		$this->assertTuple(0, $this->makeTuple($this->ts1, $this->ts3, $this->value1, $this->value3, 2));
	}

	/**
	 * @depends testMultipleGroupByHour
	 */
	function testMultipleGroupByHour2() {
		$this->addTuple($this->ts4, $this->value4);

		// get data
		$this->getTuples($this->ts1, $this->ts4, "hour");

		$this->assertFromTo($this->ts1, $this->ts4);

		$consumption = $this->getConsumption($this->value1, $this->value4);
		$average = $this->getAverage($this->ts1, $this->ts4, $consumption);
		$this->assertHeader($consumption, $average, 3);

		// tuples
		$this->assertCount(2, $this->json->data->tuples);

		$this->assertTuple(0, $this->makeTuple($this->ts1, $this->ts2, $this->value1, $this->value2));
		$this->assertTuple(1, $this->makeTuple($this->ts2, $this->ts4, $this->value2, $this->value4));
	}
}

?>
