<?php
/**
 * Aggregation tests
 *
 * NOTE: these tests should be DST-ready
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2017, The volkszaehler.org project
 * @license https://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace Tests;

use Volkszaehler\Util;
use Volkszaehler\Model;
use Doctrine\DBAL;

class SetupPerformanceData extends DataPerformance
{
	static $testSize;

	const TEST_START = '1.1.2000';		// count
	const TEST_DAYS = 365;		// count
	const TEST_SPACING = 60;	// sec

	const MSG_WIDTH = 20;

	/**
	 * Create DB connection and setup channel
	 */
	static function setupBeforeClass() {
		parent::setupBeforeClass();

		self::$testSize = round(self::TEST_DAYS * 24 * 3600 / self::TEST_SPACING);

		if (!self::$uuid)
			self::$uuid = self::createChannel('Performance', 'power', 100);
	}

	/**
	 * Cleanup aggregation
	 */
	static function tearDownAfterClass() {
		// prevent channel deletion
	}

	private function msg($msg, $val = null) {
		echo($this->formatMsg($msg));
		echo($val . " ");
	}

	function testPrepareData() {
		$this->msg('TestSize', self::$testSize);
		$channel_id = self::getChannelByUUID(self::$uuid)->getId();
		$this->msg('Channel', self::$uuid);
		$this->msg('Channel Id', $channel_id);
		$base = strtotime(self::TEST_START) * 1000;

		$stmt = self::$conn->prepare('INSERT INTO data (channel_id, timestamp, value) VALUES (?, ?, ?)');

		self::$em->getConnection()->beginTransaction();
		for ($i=0; $i<self::$testSize; $i++) {
			$ts = $base + $i * 1000 * self::TEST_SPACING;
			$val = rand(1, 100);

			$stmt->execute(array($channel_id, $ts, $val));
		}
		self::$em->getConnection()->commit();

		$this->perf("AddTime");
	}
}

?>
