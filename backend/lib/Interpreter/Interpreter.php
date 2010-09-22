<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package default
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
 * @package default
 * @author Steffen Vogel <info@steffenvogel.de>
 *
 */
use Volkszaehler\Iterator;
use Volkszaehler;
use Doctrine\ORM\Query;


/**
 * Interpreter superclass for all interpreters
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 *
 */
abstract class Interpreter implements InterpreterInterface {
	protected $channel;
	protected $em;

	protected $from;
	protected $to;

	/**
	 * Constructor
	 *
	 * @param Channel $channel
	 * @param EntityManager $em
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 */
	public function __construct(\Volkszaehler\Model\Channel $channel, \Doctrine\ORM\EntityManager $em, $from = NULL, $to = NULL) {
		$this->channel = $channel;
		$this->em = $em;

		$this->from = $from;
		$this->to = $to;
	}

	/**
	 * Get raw data
	 *
	 * @param string|integer $groupBy
	 * @return Volkszaehler\DataIterator
	 */
	protected function getData($groupBy = NULL) {
		// get dbal connection from EntityManager
		$conn = $this->em->getConnection();

		// prepare sql
		$params = array(':id' => $this->channel->getId());

		$sqlFrom = ' FROM data';
		$sqlWhere = ' WHERE channel_id = :id' . self::buildTimeFilterSQL($this->from, $this->to);
		$sqlOrderBy = ' ORDER BY timestamp ASC';

		if ($sqlGroupBy = self::buildGroupBySQL($groupBy)) {
			$sqlRowCount = 'SELECT COUNT(DISTINCT ' . $sqlGroupBy . ')' . $sqlFrom . $sqlWhere;
			$sqlGroupBy = ' GROUP BY ' . $sqlGroupBy;
			$sqlFields = ' MAX(timestamp) AS timestamp, SUM(value) AS value, COUNT(timestamp) AS count';
		}
		else {
			$sqlRowCount = 'SELECT COUNT(*)' . $sqlFrom . $sqlWhere;
			$sqlFields = ' timestamp, value';
		}

		// get total row count for grouping
		$rowCount = $conn->fetchColumn($sqlRowCount, $params, 0);

		// query for data
		$stmt = $conn->executeQuery('SELECT ' . $sqlFields . $sqlFrom . $sqlWhere . $sqlGroupBy . $sqlOrderBy, $params);

		// return iterators
		if ($sqlGroupBy || is_null($groupBy)) {		// aggregation by sql or skip it
			return new Iterator\DataIterator($stmt, $rowCount);
		}
		elseif (is_numeric($groupBy) ) {			// aggregation by php
			$tuples = (int) $groupBy;
			return new Iterator\DataAggregationIterator($stmt, $rowCount, $tuples);
		}
		else {
			throw new \Exception('Invalid parameter: "groupBy"');
		}
	}

	/**
	 * Builds sql query part for grouping data by date functions
	 *
	 * @param string $groupBy
	 * @return string the sql part
	 * @todo make compatible with: MSSql (Transact-SQL), Sybase, Firebird/Interbase, IBM, Informix, MySQL, Oracle, DB2, PostgreSQL, SQLite
	 */
	protected static function buildGroupBySQL($groupBy) {
		$ts = 'FROM_UNIXTIME(timestamp/1000)';	// just for saving space

		switch ($groupBy) {
			case 'year':
				return 'YEAR(' . $ts . ')';
				break;

			case 'month':
				return 'YEAR(' . $ts . '), MONTH(' . $ts . ')';
				break;

			case 'week':
				return 'YEAR(' . $ts . '), WEEKOFYEAR(' . $ts . ')';
				break;

			case 'day':
				return 'YEAR(' . $ts . '), DAYOFYEAR(' . $ts . ')';
				break;

			case 'hour':
				return 'YEAR(' . $ts . '), DAYOFYEAR(' . $ts . '), HOUR(' . $ts . ')';
				break;

			case 'minute':
				return 'YEAR(' . $ts . '), DAYOFYEAR(' . $ts . '), HOUR(' . $ts . '), MINUTE(' . $ts . ')';
				break;

			case 'second':
				return 'YEAR(' . $ts . '), DAYOFYEAR(' . $ts . '), HOUR(' . $ts . '), MINUTE(' . $ts . '), SECOND(' . $ts . ')';
				break;

			default:
				return FALSE;
		}
	}

	/**
	 * Build sql query part to filter specified time interval
	 *
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 * @return string the sql part
	 */
	protected static function buildTimeFilterSQL($from = NULL, $to = NULL) {
		$sql = '';

		if (isset($from)) {
			$sql .= ' && timestamp > ' . $from;
		}

		if (isset($to)) {
			$sql .= ' && timestamp < ' . $to;
		}

		return $sql;
	}

	/*
	 * Getter & setter
	 */
	public function getUuid() { return $this->channel->getUuid(); }
}

?>