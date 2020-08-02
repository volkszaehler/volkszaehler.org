<?php
/**
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2020, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
 */

namespace Tests;

use Volkszaehler\Util;
use Doctrine\DBAL\Connection;

trait AggregationTrait {

    /** @var Util\Aggregation */
    public $agg;

	function getConnection(): Connection {
		$em = \Volkszaehler\Router::createEntityManager();
		return $em->getConnection();
	}

	function aggSupported(Connection $conn): bool {
		return $conn->getDatabasePlatform()->getName() == 'mysql';
	}

	/**
	 * Aggregation data provider
	 */
	function aggProvider(): array {
		$conn = $this->getConnection();
		$modes = array_unique([false, $this->aggSupported($conn)]);
		return array_map(function ($mode) {
			return [$mode];
		}, $modes);
	}

	/**
	 * Run aggregation for channel
	 *
	 * @param string $uuid
	 * @param string $mode
	 */
	function aggregate(string $uuid, string $mode) {
		$conn = $this->getConnection();
		$this->agg = new Util\Aggregation($conn);
		$this->agg->aggregate($uuid, $mode);
	}
}
