<?php
/**
 * Virtual entity tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

use Volkszaehler\Interpreter\Virtual;

class VirtualTest extends Middleware
{
	use InterpreterTrait;

	protected $uuid;

	function getSeriesData($series) {
		$data = [
			'in1' => [
				 100 => 100,
				1000 => 10,
				2000 => 20,
				3000 => 30,
				4000 => 40,
				5000 => 50,
			],
			'in2' => [
				 100 => 200,
				 900 => 1,
				1150 => 2,
				1850 => 3,
				2100 => 4,
				3000 => 5,
				3200 => 6,
				3400 => 7,
				3600 => 8,
				3800 => 9,
				4000 => 10,
			]
		];
		return $data[$series];
	}

	function getGroupSeriesData($series) {
		$data = [
			'in1' => [
				0 => 0,
				1 => 1000,
				2 => 2000,
				3 => 3000,
				4 => 4000,
				5 => 5000,
			]
		];
		return $data[$series];
	}

	/**
	 * Extract unique, sorted timestamps from array keys
	 */
	function extractUniqueTimestamps($container) {
		if (!is_array($container)) {
			$container = [$container];
		}

		$result = [];
		foreach ($container as $ary) {
			$result = array_merge($result, array_keys($ary));
		}

		$result = array_unique($result, SORT_NUMERIC);
		sort($result);

		return $result;
	}

	/**
	 * Get array value before timestamp
	 */
	function getValueBefore($array, $ts) {
		$idx = array_reduce(array_keys($array), function($carry, $el) use ($ts) {
			if ($el < $ts) {
				return $el;
			}
			return $carry;
		});
		return $array[$idx];
	}

	/**
	 * Get array value after timestamp
	 */
	function getValueAfter($array, $ts) {
		$idx = array_reduce(array_reverse(array_keys($array)), function($carry, $el) use ($ts) {
			if ($el >= $ts) {
				return $el;
			}
			return $carry;
		});

		// last element if no further values
		if ($idx === null)
			return end($array);

		return $array[$idx];
	}

	function testTimestampGenerator() {
		$in1 = $this->getSeriesData('in1');
		$in2 = $this->getSeriesData('in2');

		$tg = new Virtual\TimestampGenerator();
		$tg->add(new \ArrayIterator(array_keys($in1)));
		$tg->add(new \ArrayIterator(array_keys($in2)));

		$timestamps = $this->extractUniqueTimestamps([$in1, $in2]);

		$this->assertEquals($timestamps, iterator_to_array($tg));
	}

	function testDelayedIterator() {
		$timestamps = [0,1,2,3];
		$di = new Virtual\DelayedIterator(new \ArrayIterator($timestamps));
		$this->assertEquals($timestamps, iterator_to_array($di));
	}

	function testGroupedTimestampIterator() {
		$in1 = $this->getSeriesData('in1');
		$in2 = $this->getSeriesData('in2');

		$tg = new Virtual\TimestampGenerator();
		$tg->add(new \ArrayIterator(array_keys($in1)));
		$tg->add(new \ArrayIterator(array_keys($in2)));

		$timestamps = [];
		// group by period of 1000ms
		foreach (array_reverse($this->extractUniqueTimestamps([$in1, $in2])) as $ts) {
			if (!isset($period) || (int)($ts / 1000) !== $period) {
				$timestamps[] = $ts;
				$period = (int)($ts / 1000);
			}
		}
		$timestamps = array_reverse($timestamps);

		$gi = new Virtual\GroupedTimestampIterator($tg, 'second');
		$this->assertEquals($timestamps, array_values(iterator_to_array($gi)));
	}

	/**
	 * VirtualChannel test data
	 */
	function virtualInputProvider() {
		return array(
			// testVirtualChannelIdentity
			array(['lines', 'lines'], ['in1', 'in1']),
			// testVirtualChannelStyleStepsAndLines
			array(['steps', 'steps'], ['in1', 'in2']),
			// testVirtualChannelStyleStates
			array(['states', 'states'], ['in1', 'in2'])
		);
	}

	function addData($data) {
		// add input values
		foreach ($data as $uuid => $tuples) {
			foreach ($tuples as $ts => $value) {
				$this->getJson('/data/' . $uuid . '.json', array(
					'operation' => 'add',
					'ts' => $ts,
					'value' => $value
				));
			}
		}
	}

	/**
	 * @dataProvider virtualInputProvider
	 */
	function testVirtualChannel($styles, $inputs) {
		// create channel, style=lines forces STRATEGY_TS_AFTER
		$in1 = $this->createChannel('Sensor', 'powersensor', ['style' => $styles[0]]);
		$in2 = $this->createChannel('Sensor', 'powersensor', ['style' => $styles[1]]);
		$out = $this->createChannel('Virtual', 'virtualsensor', [
			'unit' => 'foo',
			'in1' => $in1,
			'in2' => $in2,
			'rule' => 'in1()-in2()'
		]);

		$this->assertTrue(isset($this->json->entity), "Expected <entity> got none.");
		$this->assertTrue(isset($this->json->entity->uuid));

		$data = [
			$in1 => $this->getSeriesData($inputs[0]),
			$in2 => $this->getSeriesData($inputs[1])
		];

		// expected timestamps
		$timestamps = $this->extractUniqueTimestamps($data);
		$from = array_shift($timestamps);

		// expected values
		$values = array();
		foreach ($timestamps as $ts) {
			$func = array($this, $styles[0] == 'states' ? 'getValueBefore' : 'getValueAfter');
			$in1v = $func($data[$in1], $ts);

			$func = array($this, $styles[1] == 'states' ? 'getValueBefore' : 'getValueAfter');
			$in2v = $func($data[$in2], $ts);

			$values[] = array($ts, $in1v - $in2v, 1);
		}

		// add input values
		$this->addData($data);

		// get result
		$vi = $this->createInterpreter($out, 1, 'now', null, null);
		$tuples = $this->getInterpreterResult($vi);

		// omit first 2 timestamps from assertion since VirtualInterpreter
		// has no access to very first database row consumed by DataIterator
		$this->assertEquals(array_splice($values, 2), array_splice($tuples, 2));
		$this->assertEquals($from, $vi->getFrom());

		// delete
		foreach ([$in1, $in2, $out] as $uuid) {
			// $url = '/channel/' . $uuid . '.json?operation=delete';
			// $this->getJson($url);
			$this->deleteChannel($uuid);
		}
	}

	function testVirtualChannelConsumption() {
		// create
		$in1 = $this->createChannel('Sensor', 'powersensor');
		$out = $this->createChannel('Virtual', 'virtualconsumption', [
			'unit' => 'foo',
			'in1' => $in1,
			'rule' => 'in1()'
		]);

		$this->assertTrue(isset($this->json->entity), "Expected <entity> got none.");
		$this->assertTrue(isset($this->json->entity->uuid));

		// convert hours to milliseconds
		$series = $this->getGroupSeriesData('in1');
		$data = [
			$in1 => array_combine(
				array_map(function($key) use ($series) {
					return ($key * 3600) * 1000;
				}, array_keys($series)),
				array_values($series)
			)
		];

		// add input values
		$this->addData($data);

		// get result
		$vi = $this->createInterpreter($out, 0, 'now', null, 'hour', ['consumption']);
		$tuples = $this->getInterpreterResult($vi);

		// expected values
		$values = array_map(function($key) use ($data, $in1) {
			$tuple = [$key, (float)$data[$in1][$key], 1];
			return $tuple;
		}, array_keys($data[$in1]));
		array_shift($values);

		$consumption = (array_reduce($values, function($carry, $tuple) {
			return $carry + $tuple[1];
		}));

		$this->assertEquals(array_splice($values, 0), array_splice($tuples, 0));
		$this->assertEquals($consumption, $vi->getConsumption());

		// partial consumption
		$vi = $this->createInterpreter($out, 0, 2 * 3600 * 1000, null, 'hour', ['consumption']);
		$tuples = $this->getInterpreterResult($vi);

		// delete
		foreach ([$in1, $out] as $uuid) {
			// $url = '/channel/' . $uuid . '.json?operation=delete';
			// $this->getJson($url);
			$this->deleteChannel($uuid);
		}
	}

	function testVirtualChannelConsumptionRanges() {
		// create
		$in1 = $this->createChannel('Sensor', 'powersensor');
		$out = $this->createChannel('Virtual', 'virtualconsumption', [
			'unit' => 'foo',
			'in1' => $in1,
			'rule' => 'in1()'
		]);

		$this->assertTrue(isset($this->json->entity), "Expected <entity> got none.");
		$this->assertTrue(isset($this->json->entity->uuid));

		// convert hours to milliseconds
		$series = $this->getGroupSeriesData('in1');
		$data = [
			$in1 => array_combine(
				array_map(function($key) use ($series) {
					return ($key * 60) * 1000;
				}, range(0, 2*60)),
				array_fill(0, 2*60+1, 1)
			)
		];

		// add input values
		$this->addData($data);

		// get result
		$vi = $this->createInterpreter($out, 3600 * 1000, 7200 * 1000, null, 'hour', ['consumption']);
		$tuples = $this->getInterpreterResult($vi);
		$consumption = 1.0;

		$this->assertEquals($consumption, $vi->getConsumption());

		// delete
		foreach ([$in1, $out] as $uuid) {
			// $url = '/channel/' . $uuid . '.json?operation=delete';
			// $this->getJson($url);
			$this->deleteChannel($uuid);
		}
	}
}

?>
