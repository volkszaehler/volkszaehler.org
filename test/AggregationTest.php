<?php
/**
 * Aggregation tests
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
 */

namespace Tests;

use Volkszaehler\Util;
use Doctrine\DBAL;

class AggregationTest extends DataPerformance
{
	/**
	 * Create DB connection and setup channel
	 */
	static function setupBeforeClass() {
		parent::setupBeforeClass();

		if (!self::$uuid) {
			self::$uuid = self::createChannel('Aggregation', 'power', 100);
		}
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
		if (!Util\Configuration::read('aggregation')) {
			$this->markTestSkipped('data aggregation not enabled');
		}
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

		// cleanup 2nd channel and test successful deletion
		$this->assertFalse(isset(
			self::deleteChannel($uuid2)->exception
		));
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
	 * Test aggregate queries of type group=<group> & tuples=NULL
	 *
	 * @depends testGetBaseline
	 * @group aggregation
	 */
	function testAggregateOptimizer() {
		$agg = new Util\Aggregation(self::$conn);
		// at this point we have aggregates for 'hour' and 'day'
		$agg->aggregate(self::$uuid, 'hour', 'delta');

		$typeHour = Util\Aggregation::getAggregationLevelTypeValue('hour');
		$typeDay = Util\Aggregation::getAggregationLevelTypeValue('day');

		// day: 2 rows of aggregation data, day first
		$opt = $agg->getOptimalAggregationLevel(self::$uuid);
		$ref = array(
			array(
				'level' => 'day',
				'type' => $typeDay,
				'count' => $this->countAggregationRows(self::$uuid, $typeDay)),
			array(
				'level' => 'hour',
				'type' => $typeHour,
				'count' => $this->countAggregationRows(self::$uuid, $typeHour)));
		$this->assertEquals($ref, $opt);

		// hour: 1 row of aggregation data
		$opt = $agg->getOptimalAggregationLevel(self::$uuid, 'hour');
		$ref = array(array(
			'level' => 'hour',
			'type' => $typeHour,
			'count' => $this->countAggregationRows(self::$uuid, $typeHour)));
		$this->assertEquals($ref, $opt);

		// minute: no aggregation data => false
		$typeMinute = Util\Aggregation::getAggregationLevelTypeValue('minute');
		$opt = $agg->getOptimalAggregationLevel(self::$uuid, 'minute');
		$this->assertFalse($opt);

		// 3 data, cannot use daily aggregates for hourly request
		$this->getTuplesRaw(strtotime('2 days ago 0:00') * 1000, strtotime('1 days ago 0:00') * 1000, 'hour');
		$this->assertEquals(3, $this->json->data->rows, 'Possibly wrong aggregation level chosen by optimizer');
	}

	/**
	 * Test aggregate queries of type group=NULL & tuples=1
	 *
	 * @depends testAggregateOptimizer
	 * @group aggregation
	 */
	function testAggregateOptimizerUngroupedSingleTuple() {
		$from = strtotime('3 days ago 00:00') * 1000;
		$to = strtotime('today 0:00') * 1000;

		$this->getTuples(1, strtotime('today 0:00') * 1000, null, 1);

		$this->assertEquals($from, $this->json->data->from, '<from> mismatch');
		$this->assertEquals($to, $this->json->data->to, '<to> mismatch');
		$this->assertEquals(1, count($this->json->data->tuples));
		$this->assertEquals($to, $this->json->data->tuples[0][0]);
	}
}

?>
