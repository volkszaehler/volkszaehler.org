<?php
/**
 * Aggregation tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

use Volkszaehler\Util;
use Doctrine\DBAL;

class AggregationTest extends DataContextPerformance
{
	/**
	 * Create DB connection and setup channel
	 */
	static function setupBeforeClass() {
		parent::setupBeforeClass();

		if (!self::$uuid)
			self::$uuid = self::createChannel('Aggregation', 'power', 100);
	}

	/**
	 * Cleanup aggregation
	 */
	static function tearDownAfterClass() {
		if (self::$conn && self::$uuid && Util\Configuration::read('aggregation')) {
			$agg = new Util\Aggregation(self::$conn);
			$agg->clear(self::$uuid);
		}
		parent::tearDownAfterClass();
	}

	/**
	 * All tests depend on aggreation being enabled
	 * @group aggregation
	 */
	function testConfiguration() {
		$this->assertTrue(Util\Configuration::read('aggregation'), 'data aggregation not enabled in config file, set $config[\'aggregation\'] = true');
	}

	/**
	 * @depends testConfiguration
	 * @group aggregation
	 */
	function testClearAggregation() {
		$agg = new Util\Aggregation(self::$conn);
		$agg->clear(self::$uuid);

		$rows = $this->countAggregationRows();
		$this->assertEquals(0, $rows, 'aggregate table cannot be cleared');
	}

	/**
	 * @depends testClearAggregation
	 * @group aggregation
	 */
	function testDeltaAggregation() {
		$agg = new Util\Aggregation(self::$conn);

		// 0:00 today current timezone - must not be aggregated
		$this->addTuple(strtotime('today 0:00') * 1000, 50);
		$agg->aggregate(self::$uuid, 'day', 'delta', 2);

		$rows = $this->countAggregationRows();
		$this->assertEquals(0, $rows, 'current period wrongly appears in aggreate table');

		// 0:00 last two days - must be aggregated
		$this->addTuple(strtotime('1 days ago 0:00') * 1000, 100);
		$this->addTuple(strtotime('1 days ago 12:00') * 1000, 100);
		$this->addTuple(strtotime('2 days ago 0:00') * 1000, 100);
		$this->addTuple(strtotime('2 days ago 12:00') * 1000, 100);
		$agg->aggregate(self::$uuid, 'day', 'delta', 2);

		$rows = $this->countAggregationRows();
		$this->assertEquals(2, $rows, 'last period missing from aggreate table');

		// 0:00 three days ago - must not be aggregated
		$this->addTuple(strtotime('3 days ago 0:00') * 1000, 50);
		$agg->aggregate(self::$uuid, 'day', 'delta', 2);

		$rows = $this->countAggregationRows();
		$this->assertEquals(2, $rows, 'period before last wrongly appears in aggreate table');
	}

	/**
	 * @depends testDeltaAggregation
	 * @group aggregation
	 */
	function testDeltaAggregationSecondChannel() {
		$agg = new Util\Aggregation(self::$conn);

		// create 2nd channel
		$uuid2 = self::createChannel('AggregationSecondChannel', 'power', 100);

		// 0:00 last yesterday - must be aggregated
		$this->addTuple(strtotime('1 days ago 0:00') * 1000, 100, $uuid2);
		$agg->aggregate($uuid2, 'day', 'delta', 2);

		$rows = $this->countAggregationRows($uuid2);
		$this->assertEquals(1, $rows, 'repeated delta aggregation failed');

		// cleanup 2nd channel
		self::deleteChannel($uuid2);
	}

	/**
	 * @depends testClearAggregation
	 * @depends testDeltaAggregation
	 * @depends testConfiguration
	 * @group aggregation
	 */
	function testGetBaseline() {
		$agg = new Util\Aggregation(self::$conn);
		$agg->clear(self::$uuid);

		// unaggregated datapoints - 6 rows
		$this->getTuplesRaw(strtotime('3 days ago 0:00') * 1000);
		$this->assertEquals(6, $this->json->data->rows);

		// unaggregated datapoints grouped - 4 rows for comparison
		$this->getTuplesRaw(strtotime('3 days ago 0:00') * 1000, null, 'day');
		$this->assertEquals(4, $this->json->data->rows);

		// save baseline, then aggregate
		$tuples = $this->json->data->tuples;
		$agg->aggregate(self::$uuid, 'day', 'delta', 2);

		// aggregated datapoints grouped - 4 rows for comparison
		$this->getTuplesRaw(strtotime('3 days ago 0:00') * 1000, null, 'day');
		$this->assertEquals(4, $this->json->data->rows);

		foreach($this->json->data->tuples as $tuple) {
			$t = array_shift($tuples);
			$this->assertTuple($t, $tuple);
		}
	}

	/**
	 * @depends testGetBaseline
	 * @group aggregation
	 */
	function testAggregateRetrievalFrom() {
		// 1 data
		$this->getTuplesRaw(strtotime('today 0:00') * 1000, null, 'day');
		$this->assertEquals(1, $this->json->data->rows);

		// 1 agg + 1 data
		$this->getTuplesRaw(strtotime('1 days ago 0:00') * 1000, null, 'day');
		$this->assertEquals(2, $this->json->data->rows);

		//  1 agg + 1 agg + 1 data
		$this->getTuplesRaw(strtotime('2 days ago 0:00') * 1000, null, 'day');
		$this->assertEquals(3, $this->json->data->rows);

		// 1 data + 1 agg + 1 agg + 1 data
		$this->getTuplesRaw(strtotime('3 days ago 0:00') * 1000, null, 'day');
		$this->assertEquals(4, $this->json->data->rows);
	}

	/**
	 * @depends testGetBaseline
	 * @group aggregation
	 */
	function testAggregateRetrievalTo() {
		// 1 data + 1 agg + 1 agg + 1 data
		$this->getTuplesRaw(strtotime('3 days ago 0:00') * 1000, strtotime('today 18:00') * 1000, 'day');
		$this->assertEquals(4, $this->json->data->rows);

		// 1 data + 1 agg + 1 data(aggregated)
		$this->getTuplesRaw(strtotime('3 days ago 0:00') * 1000, strtotime('1 days ago 6:00') * 1000, 'day');
		$this->assertEquals(3, $this->json->data->rows);

		// 1 data + 1 data(aggregated)
		$this->getTuplesRaw(strtotime('3 days ago 0:00') * 1000, strtotime('2 days ago 6:00') * 1000, 'day');
		$this->assertEquals(2, $this->json->data->rows);

		// 1 data
		$this->getTuplesRaw(strtotime('3 days ago 0:00') * 1000, strtotime('3 days ago 18:00') * 1000, 'day');
		$this->assertEquals(1, $this->json->data->rows);
	}

	/**
	 * @depends testGetBaseline
	 * @group aggregation
	 */
	function testAggregateOptimizer() {
		// 3 data, cannot use daily aggregates for hourly request
		$this->getTuplesRaw(strtotime('2 days ago 0:00') * 1000, strtotime('1 days ago 0:00') * 1000, 'hour');
		$this->assertEquals(3, $this->json->data->rows, 'Possibly wrong aggregation level chosen by optimizer');
	}

	/**
	 * @depends testConfiguration
	 * @group aggregation
	 */
	function testFullAggregation() {
		// currently not implemented for performance reasons
		echo('not implemented');
	}
}

?>
