<?php
/**
 * Aggregation tests
 *
 * NOTE: these tests should be DST-ready
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

use Volkszaehler\Util;
use Volkszaehler\Model;
use Doctrine\DBAL;

class SetupPerformanceData extends DataContext
{
	static $conn;
	static $em;

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

		self::$em = \Volkszaehler\Router::createEntityManager();
		self::$conn = self::$em->getConnection();

		self::$testSize = round(self::TEST_DAYS * 24 * 3600 / self::TEST_SPACING);

		if (!self::$uuid)
			self::$uuid = self::createChannel('Performance', 'power', 100);
	}

	static function getChannelByUUID($uuid) {
		$dql = 'SELECT a, p
			FROM Volkszaehler\Model\Entity a
			LEFT JOIN a.properties p
			WHERE a.uuid = :uuid';

		$q = self::$em->createQuery($dql);
		$q->setParameter('uuid', $uuid);

		return $q->getSingleResult();
	}

	/**
	 * Cleanup aggregation
	 */
	static function tearDownAfterClass() {
		// prevent channel deletion
	}

	protected function countAggregationRows($uuid = null) {
		return self::$conn->fetchColumn(
			'SELECT COUNT(1) FROM aggregate ' .
			'INNER JOIN entities ON aggregate.channel_id = entities.id ' .
			'WHERE entities.uuid = ?', array(($uuid) ?: self::$uuid)
		);
	}

	private function formatMsg($msg) {
		$msg = "\n" . $msg . ' ';
		while (strlen($msg) < self::MSG_WIDTH) {
			$msg .= '.';
		}
		return $msg  . ' ';
	}

	private function formatVal($val) {
		if (is_float($val)) {
			return(sprintf(($val >= 1000) ? "%.0f" : "%.2f", $val));
		}
		return $val;
	}

	private function msg($msg, $val = null) {
		echo($this->formatMsg($msg));
		echo($val . " ");
	}

	private function timer($time, $msg = null) {
		if ($msg) {
			echo($this->formatMsg($msg));
		}
		echo($this->formatVal($time) . " s ");
	}

	/**
	 * @group slow
	 */
	function testPrepareData() {
		$this->msg('TestSize', self::$testSize);
		$channel_id = self::getChannelByUUID(self::$uuid)->getId();
		$this->msg('Channel', self::$uuid);
		$this->msg('Channel ID', $channel_id);
		$base = strtotime(self::TEST_START) * 1000;

		self::$em->getConnection()->beginTransaction();
		$time = microtime(true);
		for ($i=0; $i<self::$testSize; $i++) {
			$ts = $base + $i * 1000 * self::TEST_SPACING;
			$val = rand(1, 100);

			self::$conn->executeQuery(
				'INSERT DELAYED INTO data (channel_id, timestamp, value) VALUES (?, ?, ?)',
				array($channel_id, $ts, $val)
			);
		}
		$time = microtime(true) - $time;
		self::$em->getConnection()->commit();

		$this->msg($time, "AddTime");
	}
}

?>
