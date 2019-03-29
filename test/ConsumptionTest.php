<?php
/**
 * Consumption mode tests
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
 */

namespace Tests;

use Volkszaehler\Util;

class ConsumptionTest extends Data
{
	use AggregationTrait;

	private $base = 24 * 3.6e6; // 1 day in ms
	private $periods = 24;		// 1 day
	private $value = 10;		// 10 W

	/**
	 * Create channel
	 */
	static function setupBeforeClass() {
		parent::setupBeforeClass();
		self::$uuid = self::createChannel('Sensor', 'powersensor', 1);
	}

	function testAddTuples() {
		for ($i = -1; $i < $this->periods; $i++) {
			$ts = $this->base + $i * 3.6e6; // each hour
			$this->addTuple($ts, $this->value);
		}

		$this->getTuples($this->base, 'now');

		$this->assertEquals(24, count($this->json->data->tuples));
		$this->assertEquals(25, $this->json->data->rows);

		$this->assertEquals($this->value * $this->periods, $this->json->data->consumption, 'Consumption mismatch');
		$this->assertEquals($this->value, $this->json->data->average, 'Average mismatch');
	}

	/**
	 * @depends testAddTuples
	 * @dataProvider aggProvider
	 */
	function testConsumption(bool $aggregate) {
		if ($aggregate) $this->aggregate(self::$uuid, 'hour');

		$this->getTuples($this->base, 'now', 'hour', null, 'consumption');
		// print_r($this->json->data);

		$this->assertEquals(24, count($this->json->data->tuples));
		$this->assertEquals(25, $this->json->data->rows);

		for ($i = 0; $i < $this->periods; $i++) {
			$ts = $this->base + $i * 3.6e6; // each hour
			$this->assertEquals($ts, $this->json->data->tuples[$i][0], 'Tuple timestamp mismatch');
			$this->assertEquals($this->value, $this->json->data->tuples[$i][1], 'Tuple value mismatch');
		}

		$this->assertEquals($this->value * $this->periods, $this->json->data->consumption, 'Consumption mismatch');
		// $this->assertEquals($this->value, $this->json->data->average, 'Average mismatch');
	}
}
