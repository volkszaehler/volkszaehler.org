<?php
/**
 * Meter tests
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2020, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
 */

namespace Tests;

class SensorTest extends Data
{
	// channel properties
	static $resolution = 1000;

	// data properties
	protected $ts1 =  3600000;
	protected $ts2 = 10800000; // +2hr
	protected $ts3 = 14400000; // +3hr
	protected $ts4 = 16200000; // +3:30
	protected $ts5 = 17100000; // +3:45

	protected $value1 = 1000;
	protected $value2 = 1000;  // 1kW
	protected $value3 = 2000;  // 2kW
	protected $value4 = 2000;  // 2kW
	protected $value5 = 3000;  // 3kW

	/**
	 * Create channel
	 */
	static function setupBeforeClass() : void {
		parent::setupBeforeClass();
		self::$uuid = self::createChannel('Sensor', 'powersensor', self::$resolution);
	}

	function getConsumption($from, $to, $periodValue) {
		return($periodValue * ($to - $from) / 3600000 / self::$resolution);
	}

	function getAverage($from, $to, $periodValue) {
		return($periodValue * 3600000 / ($to - $from));
	}

	function makeTuple($from, $to, $rawValue, $count = NULL) {
		$consumption = $this->getConsumption($from, $to, $rawValue);
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

		// tuples not set or empty array
		$this->assertFalse(isset($this->json->data->tuples) && count($this->json->data->tuples));
	}

	/**
	 * @depends testAddTuple
	 * @depends testGetTuple
	 */
	function testGetMultiple() {
		$this->addTuple($this->ts2, $this->value2);
		$this->getTuples($this->ts1, $this->ts2);

		$this->assertFromTo($this->ts1, $this->ts2);

		$consumption = $this->getConsumption($this->ts1, $this->ts2, $this->value2);
		$average = $this->getAverage($this->ts1, $this->ts2, $consumption);
		$this->assertHeader($consumption, $average, 2);

		// tuples
		$this->assertCount(1, $this->json->data->tuples);

		$this->assertTuple(0, $this->makeTuple($this->ts1, $this->ts2, $this->value2));
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

		$consumption = $this->getConsumption($this->ts1, $this->ts2, $this->value2);
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

		// tuples not set or empty array
		$this->assertFalse(isset($this->json->data->tuples) && count($this->json->data->tuples));
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

		$consumption =
			$this->getConsumption($this->ts1, $this->ts2, $this->value2) +
			$this->getConsumption($this->ts2, $this->ts3, $this->value3);
		$average = $this->getAverage($this->ts1, $this->ts3, $consumption);
		$this->assertHeader($consumption, $average, 3);

		$this->assertMinMax(
			$this->makeTuple($this->ts1, $this->ts2, $this->value2),
			$this->makeTuple($this->ts2, $this->ts3, $this->value3));

		// tuples
		$this->assertCount(2, $this->json->data->tuples);

		$this->assertTuple(0, $this->makeTuple($this->ts1, $this->ts2, $this->value2));
		$this->assertTuple(1, $this->makeTuple($this->ts2, $this->ts3, $this->value3));
	}

	/**
	 * @depends testMultipleAverageAndConsumption
	 */
	function testMultipleGroupByHour() {
		// get data
		$this->getTuples($this->ts1, $this->ts3, "hour");

		$this->assertFromTo($this->ts1, $this->ts3);

		$consumption =
			$this->getConsumption($this->ts1, $this->ts2, $this->value2) +
			$this->getConsumption($this->ts2, $this->ts3, $this->value3);
		$average = $this->getAverage($this->ts1, $this->ts3, $consumption);
		$this->assertHeader($consumption, $average, 3);

		$this->assertMinMax(
			$this->makeTuple($this->ts1, $this->ts2, $this->value2),
			$this->makeTuple($this->ts2, $this->ts3, $this->value3));

		// tuples
		$this->assertCount(2, $this->json->data->tuples);

		$this->assertTuple(0, $this->makeTuple($this->ts1, $this->ts2, $this->value2));
		$this->assertTuple(1, $this->makeTuple($this->ts2, $this->ts3, $this->value3));
	}

	/**
	 * @depends testMultipleAverageAndConsumption
	 */
	function testMultiplePackaging() {
		// get data - 1 tuple
		$this->getTuples($this->ts1, $this->ts3, "", 1);

		$this->assertFromTo($this->ts1, $this->ts3);

		// even when packaged, raw number of DB rows (3) is returned
		$consumption =
			$this->getConsumption($this->ts1, $this->ts2, $this->value2) +
			$this->getConsumption($this->ts2, $this->ts3, $this->value3);
		$average = $this->getAverage($this->ts1, $this->ts3, $consumption);

		// relaxed precision for SensorInterpreter weighed average calculation
		static::$precision = '0.01';

		// 3 vs 1 result rows depends on if Interpreter->runSQL or DataIterator->next does iteration
		$this->assertHeader($consumption, $average); // ,3

		// min/max are identical with the one tuple; correct average to raw value
		$this->assertMinMax(
			$this->makeTuple($this->ts1, $this->ts3, $average * self::$resolution));

		// out of the 3 tuples, 1 has been used as starting point, the 2 remaining ones are packaged
		$this->assertCount(1, $this->json->data->tuples);

		// correct average to raw value
		$this->assertTuple(0, $this->makeTuple($this->ts1, $this->ts3, $average * self::$resolution, 2));
	}

	/**
	 * @depends testMultipleGroupByHour
	 */
	function testMultipleGroupByHour2() {
		// requires weighed average calculation - currently not portable across DBMSes
		$this->skipForDB(array('pdo_sqlite'));

		$this->addTuple($this->ts4, $this->value4);
		$this->addTuple($this->ts5, $this->value5);

		// get data
		$this->getTuples($this->ts1, $this->ts5, "hour");
		$this->assertFromTo($this->ts1, $this->ts5);

		$consumption =
			$this->getConsumption($this->ts1, $this->ts2, $this->value2) +
			$this->getConsumption($this->ts2, $this->ts3, $this->value3) +
			$this->getConsumption($this->ts3, $this->ts4, $this->value4) +
			$this->getConsumption($this->ts4, $this->ts5, $this->value5);

		$average = $this->getAverage($this->ts1, $this->ts5, $consumption);
		$this->assertHeader($consumption, $average, 3);

		// tuples
		$this->assertCount(2, $this->json->data->tuples);

		// avg power of last 2 tuples
		$periodValue = $this->getAverage($this->ts2, $this->ts5,			// hour 3 avg. power
			$this->getConsumption($this->ts2, $this->ts3, $this->value3) +
			$this->getConsumption($this->ts3, $this->ts4, $this->value4) +
			$this->getConsumption($this->ts4, $this->ts5, $this->value5));

		// correct periodValue to raw value
		$this->assertTuple(0, $this->makeTuple($this->ts1, $this->ts2, $this->value2));	// hour 2
		$this->assertTuple(1, $this->makeTuple($this->ts2, $this->ts5, $periodValue * self::$resolution));	// hour 3
	}

	/**
	 * @depends testMultiplePackaging
	 * @depends testMultipleGroupByHour2
	 */
	function testMultiplePackagingAlgorithms() {
		// get data - 5 rows make 4 tuples, 2 or 3 packages
		// depending on PHP or SQL packaging
		$this->getTuples($this->ts1, $this->ts5, "");
		$this->getTuples($this->ts1, $this->ts5, "", 2);

		// tuples
		$this->assertTrue(count($this->json->data->tuples) >= 2);
		$this->assertTrue(count($this->json->data->tuples) <= 3);

		// add tuple 6+7 up to exactly power of 2 after tuple 1
		$bitshift = (int)floor(log($this->ts5 - $this->ts1, 2));
		$packageSize = 1 << ($bitshift + 1);
		$ts6 = $this->ts1 + $packageSize;
		$this->addTuple($ts6-1, 1000);
		$this->addTuple($ts6, 1000);

		// get data - 7 rows make 7 tuples, 2 packages
		$this->getTuples($this->ts1, $ts6, "");
		$this->getTuples($this->ts1, $ts6, "", 2);

		// tuples
		$this->assertCount(2, $this->json->data->tuples);
	}
}

?>
