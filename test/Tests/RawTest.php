<?php
/**
 * Meter tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

class RawTest extends Data
{
    public function channelDataProvider()  {
        return array(
            array('power', round(rand(2, 1000))),
            array('electric meter', round(rand(2, 1000))),
            array("powersensor", round(rand(2, 1000))),
        );
    }

    /**
     * @dataProvider channelDataProvider
     */
	function testAddAndGetRawTuples($type, $resolution) {
		self::$uuid = self::createChannel('Test', $type, $resolution);

		$data = array(
			array('ts' => 1000, 'value' => 1),
			array('ts' => 2000, 'value' => 2),
			array('ts' => 3000, 'value' => 3),
		);

		foreach ($data as $tuple) {
			$this->addTuple($tuple['ts'], $tuple['value'], self::$uuid);
		}

		$url = '/data/' . self::$uuid . '.json?options=raw';

		$this->assertTrue(isset(
			$this->getTuplesByUrl($url, 0, PHP_INT_MAX)->data)
		);

		$this->assertEquals(count($data)-1, count($this->json->data->tuples));

		for ($i = 1; $i < count($data); $i++) {
			$tuple = array_slice($this->json->data->tuples[$i-1], 0, 2);
			$this->assertEquals(array_values($data[$i]), $tuple);
		}

		self::deleteChannel(self::$uuid);
	}
}

?>
