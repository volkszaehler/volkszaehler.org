<?php
/**
 * Meter tests
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
 */

namespace Tests;

use Volkszaehler\Util;

class PushServerTest extends Data
{
	// channel properties
	static $resolution = 100;

	// curl output
	protected $curl;

	/**
	 * Create channel
	 */
	static function setupBeforeClass() {
		parent::setupBeforeClass();
		self::$uuid = self::createChannel('Counter', 'electric meter', self::$resolution);
	}

	function safeCurl($data) {
		$port = Util\Configuration::read('push.server');
		$json = json_encode($data);

		$exitCode = null;
		$output = [];

		$curl = "curl %s -s -m 3 -X POST -d '%s' -H 'Content-Type: application/json' localhost:%d 2>&1";

		// run and test for failure
		$cmd = sprintf($curl, '-f', $json, $port);
		exec($cmd, $output, $exitCode);

		$this->curl = join($output);

		// run to get output
		if ($exitCode !== 0) {
			$cmd = sprintf($curl, '-i', $json, $port);
			passthru($cmd);
		}

		return $exitCode;
	}

	/**
	 * @group pushserver
	 */
	function testPushMessage() {
		$this->assertTrue(Util\Configuration::read('push.enabled'), 'Push server disabled');

		// first message - no result tuples
		$exitCode = $this->safeCurl([
			'data' => [
				[
					'uuid' => self::$uuid,
					'tuples' => [[1,1,1]]
				]
			]
		]);

		$this->assertEquals(0, $exitCode, sprintf('Curl failed with exit code %d', $exitCode));

		$json = json_decode($this->curl);
		$this->assertTrue(isset($json->data) && is_array($json->data) && isset($json->data[0]->tuples), 'Invalid json response');
		$this->assertEquals(0, count($json->data[0]->tuples), 'Unexpected json response tuples');

		// second message
		$exitCode = $this->safeCurl([
			'data' => [
				[
					'uuid' => self::$uuid,
					'tuples' => [[2,2,1]]
				]
			]
		]);

		$this->assertEquals(0, $exitCode, sprintf('Curl failed with exit code %d', $exitCode));

		$json = json_decode($this->curl);
		$this->assertTrue(isset($json->data) && is_array($json->data) && isset($json->data[0]->tuples), 'Invalid json response');
		$this->assertEquals(1, count($json->data[0]->tuples), 'Unexpected json response tuples');
		$this->assertEquals([2,36000000,1], $json->data[0]->tuples[0], 'Unexpected json response tuples');
	}
}

?>
