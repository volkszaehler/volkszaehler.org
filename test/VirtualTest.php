<?php
/**
 * Virtual entity tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

use Volkszaehler\Router;
use Volkszaehler\Controller\EntityController;

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

	function getValueBefore($array, $ts) {
		$idx = array_reduce(array_keys($array), function($carry, $el) use ($ts) {
			if ($el < $ts) {
				return $el;
			}
			return $carry;
		});
		return $array[$idx];
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

		// expected timestamps
		$timestamps = array();
		foreach ($data as $channel) {
			foreach ($channel as $ts => $value) {
				if (!in_array($ts, $timestamps))
					$timestamps[] = $ts;
			}
		}
		asort($timestamps);
		$from = array_shift($timestamps);

		// expected values
		$values = array();
		foreach ($timestamps as $ts) {
			$in1v = $this->getValueBefore($data[$in1], $ts);
			$in2v = $this->getValueBefore($data[$in2], $ts);
			$values[] = array($ts, $in1v - $in2v, 1);
		}

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
		// $url = '/data/' . $out . '.json?from=1&to=now&debug=1';
		// $output = $this->getJson($url);
		// $tuples = $output->data->tuples;

		$em = Router::createEntityManager();
		$entity = EntityController::factory($em, $out, true); // from cache
		$class = $entity->getDefinition()->getInterpreter();
		$vc = new $class($entity, $em, 1, 'now', null, null);

		$tuples = array();
		foreach ($vc as $tuple) {
			$tuple[0] = (int)$tuple[0];
			$tuples[] = $tuple;
		}

		// omit first 2 timestamps from assertion since VirtualInterpreter
		// has no access to very first database row consumed by DataIterator
		$this->assertEquals(array_splice($values, 2), array_splice($tuples, 2));
		$this->assertEquals($from, $vc->getFrom());

		// delete
		foreach ([$in1, $in2, $out] as $uuid) {
			$url = '/channel/' . $uuid . '.json?operation=delete';
			$this->getJson($url);
		}
	}
}

?>
