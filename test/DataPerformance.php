<?php
/**
 * Performance test base functions
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 */

namespace Tests;

use Volkszaehler\Util;
use Volkszaehler\Controller;
use Volkszaehler\Model;
use Doctrine\DBAL;

class DataPerformance extends Data
{
	protected static $conn;
	protected static $em;

	protected static $time;		// timestamp for performance tests
	protected static $baseline;	// timestamp for speedup comparison tests

	const MSG_WIDTH = 30;

	/**
	 * Create DB connection and setup channel
	 */
	static function setupBeforeClass() {
		parent::setupBeforeClass();

		self::$em = \Volkszaehler\Router::createEntityManager();
		self::$conn = self::$em->getConnection();
	}

	/**
	 * Run before each test
	 */
	function setUp() {
		self::$time = microtime(true);
	}

	protected static function getChannelByUUID($uuid) {
		$ec = new Controller\EntityController(null, self::$em);
		return $ec->get($uuid);
	}

	protected static function countRows($uuid, $table = 'data') {
		return self::$conn->fetchColumn(
			'SELECT COUNT(1) FROM ' . $table . ' ' .
			'INNER JOIN entities ON ' . $table . '.channel_id = entities.id ' .
			'WHERE entities.uuid = ?', array(($uuid) ?: static::$uuid)
		);
	}

	protected static function countAggregationRows($uuid = null, $type = null) {
		$sql =
			'SELECT COUNT(1) FROM aggregate ' .
			'INNER JOIN entities ON aggregate.channel_id = entities.id ' .
			'WHERE entities.uuid = ? ';
		$sqlParameters = array(($uuid) ?: static::$uuid);

		if (isset($type)) {
			$sql .= 'AND aggregate.type = ?';
			$sqlParameters[] = $type;
		}

		return self::$conn->fetchColumn($sql, $sqlParameters);
	}

	protected static function clearCache() {
		self::$conn->executeQuery('FLUSH TABLES');
		self::$conn->executeQuery('RESET QUERY CACHE');
	}

	protected function formatMsg($msg) {
		$msg = "\n" . $msg . ' ';
		while (strlen($msg) < self::MSG_WIDTH) {
			$msg .= '.';
		}
		return $msg . ' ';
	}

	protected function perf($msg, $speedup = false) {
		$time = microtime(true) - self::$time;
		if (!$speedup) self::$baseline = $time;

		$timeStr = sprintf(($time >= 1000) ? "%.0f" : "%.2f", $time);
		echo($this->formatMsg($msg) . $timeStr . "s ");

		if ($speedup) {
			$speedup = self::$baseline / $time;
			echo('x' . sprintf("%.".max(0,2-floor(log($speedup,10)))."f", $speedup) . ' ');
		}
	}
}

?>
