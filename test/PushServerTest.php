<?php
/**
 * Meter tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

use Volkszaehler\Util;

class PushServerTest extends Data
{
	// channel properties
	static $resolution = 100;

	/**
	 * Create channel
	 */
	static function setupBeforeClass() {
		parent::setupBeforeClass();
		self::$uuid = self::createChannel('Counter', 'electric meter', self::$resolution);
	}

	/**
	 * @group pushserver
	 */
	function testPushMessage() {
		$this->assertTrue(Util\Configuration::read('push.enabled'), 'Push server disabled');

		$exitCode = null;
		$port = Util\Configuration::read('push.server');
		$curl = "curl %s -s -m 3 -X POST -d '{\"data\":[{\"uuid\":\"%s\",\"tuples\":[[1,1,1]]}]}' localhost:%d 2>&1";

		// run and test for failure
		$cmd = sprintf($curl, '-f', self::$uuid, $port);
		passthru($cmd, $exitCode);

		// run to get output
		if ($exitCode !== 0) {
			$cmd = sprintf($curl, '-i', self::$uuid, $port);
			passthru($cmd);
		}

		$this->assertTrue($exitCode === 0, sprintf('Curl failed with exit code %d', $exitCode));
	}
}

?>
