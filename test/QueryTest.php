<?php
/**
 * Query controller tests
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
 */

namespace Tests;

class QueryTest extends Data
{
	function testQueryException() {
		$this->getJson('/query/uuid.json', [], 'GET', 'Queries cannot be performed against UUIDs');
	}

    function testAdhocQuery() {
		self::$uuid = self::createChannel('Sensor', 'powersensor');

		$this->addTuple(3600, 0);
		$this->addTuple(7200, $value = 7);

		$this->getJson('/query.json', [
			'in1' => static::$uuid,
			'rule' => '2*in1()',
		]);

        $this->assertCount(1, $this->json->data->tuples);
        $this->assertEquals(2 * $value, $this->json->data->tuples[0][1]);
    }
}

?>
