<?php
/**
 * Aggregation performance tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

use Volkszaehler\Util;
use Volkszaehler\Model;
use Doctrine\DBAL;

class AggregationPerformanceTest extends DataContextPerformance
{
	static $uuid = '00000000-0000-0000-0000-000000000000';
	static $to; // = '10.1.2000'; // limit data set for low performance clients
	static $base;

	/**
	 * Create DB connection and setup channel
	 */
	static function setupBeforeClass() {
		parent::setupBeforeClass();

		self::$base = self::$context . '/' . static::$uuid . '.json?';

		if (!static::$uuid) {
			echo("Failure: need UUID before test.\nRun `phpunit Tests\SetupPerformanceData` to generate.");
			die;
		}

		if (isset(self::$to)) {
			if (!is_numeric(self::$to)) {
				self::$to = strtotime(self::$to) * 1000;
			}
		}
	}

	/**
	 * Cleanup aggregation
	 */
	static function tearDownAfterClass() {
		// keep channel
	}

	/**
	 * Run before each test
	 */
	function setUp() {
		static::clearCache();
		static::$time = microtime(true);
	}

	/**
	 * @group aggregation
	 * @group slow
	 */
	function testConfiguration() {
		$this->assertTrue(Util\Configuration::read('aggregation'), 'data aggregation not enabled in config file, set $config[\'aggregation\'] = true');
	}

	/**
	 * @depends testConfiguration
	 * @group aggregation
	 * @group slow
	 */
	function testAggregation() {
		$rowsData = $this->countRows(static::$uuid);
		$this->assertGreaterThan(0, $rowsData);
		echo($this->formatMsg("DataRows") . number_format($rowsData, 0, '.', '.'));

		$agg = new Util\Aggregation(self::$conn);
		$aggLevels = $agg->getOptimalAggregationLevel(static::$uuid);

		foreach ($aggLevels as $level) {
			$rowsAgg = $this->countAggregationRows(static::$uuid, $level['type']);
			$this->assertGreaterThan(0, $rowsAgg);
			echo($this->formatMsg("AggregateRows  (" . $level['level'] . ")") . number_format($rowsAgg, 0, '.', '.'));
			echo($this->formatMsg("AggregateRatio (" . $level['level'] . ")") . "1:" . round($rowsData / $rowsAgg));
		}
	}

	// function testGetAllData() {
	// 	$this->getTuplesByUrl(self::$base, 1, '1.2.2000', null, null, 'options=slow');
	// 	$this->perf("GetAllPerf");
	// }

	// function testGetAllData2() {
	// 	$this->getTuplesByUrl(self::$base, 1, '1.2.2000', null, null);
	// 	$this->perf("GetAllPerf (opt)", true);
	// }

	/**
	 * @depends testConfiguration
	 * @group aggregation
	 * @group slow
	 */
	function testGetAllDataGrouped() {
		$this->getTuplesByUrl(self::$base, 1, self::$to, 'day', null, 'options=slow');
		$this->perf("GetGroupPerf");
	}

	/**
	 * @depends testConfiguration
	 * @group aggregation
	 * @group slow
	 */
	function testGetAllDataGrouped2() {
		$this->getTuplesByUrl(self::$base, 1, self::$to, 'day', null);
		$this->perf("GetGroupPerf (opt)", true);
	}

	/**
	 * @depends testConfiguration
	 * @group aggregation
	 * @group slow
	 */
	function testGetTotal() {
		$this->getTuplesByUrl(self::$base, 1, self::$to, null, 1, 'options=slow');
		$this->perf("GetTotalPerf");
	}

	/**
	 * @depends testConfiguration
	 * @group aggregation
	 * @group slow
	 */
	function testGetTotal2() {
		$this->getTuplesByUrl(self::$base, 1, self::$to, null, 1);
		$this->perf("GetTotalPerf (opt)", true);
	}
}

?>
