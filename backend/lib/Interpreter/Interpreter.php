<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package data
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
 */
/*
 * This file is part of volkzaehler.org
 *
 * volkzaehler.org is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * volkzaehler.org is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Volkszaehler\Interpreter;

/**
 *
 * @package data
 * @author Steffen Vogel <info@steffenvogel.de>
 *
 */
interface InterpreterInterface {
	public function getValues($from = NULL, $to = NULL, $groupBy = NULL);
	public function getMin($from = NULL, $to = NULL);
	public function getMax($from = NULL, $to = NULL);
	public function getAverage($from = NULL, $to = NULL);
}

/**
 * interpreter superclass for all interpreters
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package data
 *
 */
abstract class Interpreter implements InterpreterInterface {
	protected $channel;
	protected $em;

	/**
	 *
	 * @param $channel
	 * @param $em
	 */
	public function __construct(\Volkszaehler\Model\Channel $channel, \Doctrine\ORM\EntityManager $em) {
		$this->channel = $channel;
		$this->em = $em;
	}

	/**
	 *
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 * @param mixed $groupBy
	 * @todo split in two functions
	 */
	protected function getData($from = NULL, $to = NULL, $groupBy = NULL) {
		$ts = 'FROM_UNIXTIME(timestamp/1000)';	// just for saving space
		switch ($groupBy) {
			case 'year':
				$sqlGroupBy = 'YEAR(' . $ts . ')';
				break;

			case 'month':
				$sqlGroupBy = 'YEAR(' . $ts . '), MONTH(' . $ts . ')';
				break;

			case 'week':
				$sqlGroupBy = 'YEAR(' . $ts . '), WEEKOFYEAR(' . $ts . ')';
				break;

			case 'day':
				$sqlGroupBy = 'YEAR(' . $ts . '), DAYOFYEAR(' . $ts . ')';
				break;

			case 'hour':
				$sqlGroupBy = 'YEAR(' . $ts . '), DAYOFYEAR(' . $ts . '), HOUR(' . $ts . ')';
				break;

			case 'minute':
				$sqlGroupBy = 'YEAR(' . $ts . '), DAYOFYEAR(' . $ts . '), HOUR(' . $ts . '), MINUTE(' . $ts . ')';
				break;

			case 'second':
				$sqlGroupBy = 'YEAR(' . $ts . '), DAYOFYEAR(' . $ts . '), HOUR(' . $ts . '), MINUTE(' . $ts . '), SECOND(' . $ts . ')';
				break;

			default:
				if (is_numeric($groupBy)) {		// lets agrregate it with php
					$groupBy = (int) $groupBy;
					$sqlGroupBy = FALSE;
				}
				else {
					throw new \InvalidArgumentException('\'' . $groupBy . '\' is not an unknown grouping mode');
				}
		}

		$sql = 'SELECT';
		$sql .= ($sqlGroupBy === FALSE) ? ' timestamp, value' : ' MAX(timestamp) AS timestamp, SUM(value) AS value, COUNT(timestamp) AS count';
		$sql .= ' FROM data WHERE channel_id = ' . (int) $this->channel->getId();

		if (isset($from)) {
			$sql .= ' && timestamp > ' . $from;
		}

		if (isset($to)) {
			$sql .= ' && timestamp < ' . $to;
		}

		if ($sqlGroupBy !== FALSE) {
			$sql .= ' GROUP BY ' . $sqlGroupBy;
		}

		$sql .= ' ORDER BY timestamp DESC';

		$rsm = new \Doctrine\ORM\Query\ResultsetMapping;
		$rsm->addScalarResult('timestamp', 'timestamp');
		$rsm->addScalarResult('value', 'value');

		if ($sqlGroupBy) {
			$rsm->addScalarResult('count', 'count');
		}

		$query = $this->em->createNativeQuery($sql, $rsm);
		$result = $query->getResult();
		$totalCount = count($result);

		if (is_int($groupBy) && $groupBy < $totalCount) {	// return $groupBy values
			$packageSize = floor($totalCount / $groupBy);
			$packageCount = $groupBy;
		}
		else {												// return all values or grouped by year, month, week...
			$packageSize = 1;
			$packageCount = $totalCount;
		}

		$packages = array();
		$reading = reset($result);
		for ($i = 1; $i <= $packageCount; $i++) {
			$package = array('timestamp' => (int) $reading['timestamp'],	// last timestamp in package
								'value' => (float) $reading['value'],		// sum of values
								'count' => ($sqlGroupBy === FALSE) ? 1 : $reading['count']);						// total count of values or pulses in the package

			while ($package['count'] < $packageSize) {
				$reading = next($result);

				$package['value'] += $reading['value'];
				$package['count']++;
			}

			$packages[] = $package;
			$reading = next($result);
		}

		return array_reverse($packages);	// start with oldest ts and ends with newest ts (reverse array order due to descending order in sql statement)
	}
}

?>