<?php
/**
 * Virtual entity tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

class VirtualTest extends Middleware
{
	protected $uuid;

	function createChannel($title, $type, $other = []) {
		$url = '/channel.json';
		$params = array(
			'operation' => 'add',
			'title' => $title,
			'type' => $type
		);
		$params = array_merge($params, $other);

		$this->getJson($url, $params);

		return($this->uuid = (isset($this->json->entity->uuid)) ? $this->json->entity->uuid : null);
	}

	function testVirtualChannel() {
		// create
		$in1 = $this->createChannel('Sensor', 'powersensor');
		$in2 = $this->createChannel('Sensor', 'powersensor');
		$out = $this->createChannel('Virtual', 'virtualsensor', [
			'unit' => 'foo',
			'in1' => $in1,
			'in2' => $in2,
			'rule' => 'in1()-in2()'
		]);

		$this->assertTrue(isset($this->json->entity), "Expected <entity> got none.");
		$this->assertTrue(isset($this->json->entity->uuid));

		$data = [
			$in1 => [
				 100 => 100,
				1000 => 10,
				2000 => 20,
				3000 => 30,
				4000 => 40,
				5000 => 50,
			],
			$in2 => [
				 100 => 200,
			//	1000
				 900 => 1,	// use
				1150 => 2,	// skip
			//	2000
				1850 => 3,	// skip
				2100 => 4,	// use
			//	3000
				3000 => 5,
				3200 => 6,	// skip
				3400 => 7,	// skip
				3600 => 8,	// skip
				3800 => 9,	// skip
			//	4000
				4000 => 10,	// use 2x
			//	5000
			]
		];

		// rule result
		$expect = [9, 16, 25, 30, 40];

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

		// get result
		$url = '/data/' . $out . '.json?from=1&to=now&debug=1';
		$output = $this->getJson($url);
		$tuples = $output->data->tuples;

		$expectedResult = [];
		foreach (array_slice($data[$in1], 1, null, true) as $ts => $value) {
			$expectedValue = array_shift($expect);
			$expectedResult[] = [$ts, $expectedValue, 1];
		}

		$this->assertEquals($expectedResult, $tuples);

		// delete
		foreach ([$in1, $in2, $out] as $uuid) {
			$url = '/channel/' . $uuid . '.json?operation=delete';
			$this->getJson($url);
		}
	}
}

?>
