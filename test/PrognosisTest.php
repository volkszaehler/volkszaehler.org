<?php
/**
 * Prognosis controller tests
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
 */

namespace Tests;

class PrognosisTest extends Data
{
	function testPrognosisException() {
		$this->getJson('/prognosis.json', [], 'GET', "Unsupported period: ''");
	}

    function testDailyPrognosis() {
		self::$uuid = self::createChannel('Sensor', 'powersensor');

		$yesterday = 1000 * strtotime('yesterday');
		$today = 1000 * strtotime('today');

		// yesterday
		$this->addTuple($yesterday, 0);
		$this->addTuple($yesterday + 1 * 3.6e6, 10); // 01:00
		$this->addTuple($yesterday + 1 * 3.6e6 + 1, 10); // 01:00 (stop for reference period)
		$this->addTuple($yesterday + 2 * 3.6e6, 20); // 02:00

		// today
		$this->addTuple($today, 0); // 00:00
		$this->addTuple($today+1, 0); // 01:00 (stop for current period)
		$this->addTuple($today + 1 * 3.6e6, 5); // 01:00

		$this->getJson('/prognosis/' . static::$uuid . '.json', [
			'period' => 'day',
			'now' => strftime('%X', ($today + 1 * 3.6e6) / 1e3),
		]);

        $this->assertEquals(15, $this->json->prognosis->consumption);
        $this->assertEquals(0.5, $this->json->prognosis->factor);
        $this->assertEquals(5, $this->json->periods->current->partial_consumption);
        $this->assertEquals(10, $this->json->periods->reference->partial_consumption);
        $this->assertEquals(30, $this->json->periods->reference->consumption);
    }
}

?>
