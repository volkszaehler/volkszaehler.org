<?php
/**
 * @copyright Copyright (c) 2011, The volkszaehler.org project
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

namespace Volkszaehler\Controller;

use Doctrine\DBAL\Connection;

use Volkszaehler\Router;
use Volkszaehler\Util;
use Volkszaehler\View;
use Volkszaehler\Definition;

/**
 * Capabilities controller
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Götz <cpuidle@gmx.de>
 * @package default
 */
class CapabilitiesController extends Controller {

	/**
	 * Fast MyISAM/ InnoDB table count
	 * @param Connection $conn
	 * @param string $table
	 * @return int Number of database rows
	 */
	private function sqlCount(Connection $conn, $table) {
		$explain = $conn->fetchAssoc('EXPLAIN SELECT COUNT(id) FROM ' . $table . ' USE INDEX (PRIMARY)');
		if (isset($explain['rows']))
			// estimated for InnoDB
			$rows = $conn->fetchColumn(
				'SELECT table_rows FROM information_schema.tables WHERE LOWER(table_schema) = LOWER(?) AND LOWER(table_name) = LOWER(?)',
				array(Util\Configuration::read('db.dbname'), $table)
			);
		else
			// get correct values for MyISAM
			$rows = $conn->fetchColumn('SELECT COUNT(1) FROM ' . $table);

		return $rows;
	}

	/**
	 * Estimated table disk space
	 * @param Connection $conn
	 * @param string $table
	 * @return mixed
	 */
	private function dbSize(Connection $conn, $table = null) {
		$sql = 'SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE LOWER(table_schema) = LOWER(?)';
		$params = array(Util\Configuration::read('db.dbname'));

		if ($table) {
			 $sql .= ' AND LOWER(table_name) = LOWER(?)';
			 $params[] = $table;
		}

		return $conn->fetchColumn($sql, $params);
	}

	/**
	 * @param string $section select specific sub section for output
	 * @return array
	 */
	public function get($section = NULL) {
		$capabilities = array();

		if (is_null($section) || $section == 'configuration') {
			$configuration = array(
				'precision' => View\View::PRECISION,
				'database' => Util\Configuration::read('db.driver'),
				'debug' => Util\Configuration::read('debug'),
				'devmode' => Util\Configuration::read('devmode')
			);

			if ($commit = Util\Debug::getCurrentCommit()) {
				$configuration['commit'] = $commit;
			}

			$capabilities['configuration'] = $configuration;
		}

		// db statistics - only if specifically requested
		if ($section == 'database') {
			$conn = $this->em->getConnection(); // get DBAL connection from EntityManager

			// estimate InnoDB tables to avoid performance penalty
			$rows = $this->sqlCount($conn, 'data');
			$size = $this->dbSize($conn, 'data');

			$capabilities['database'] = array(
				'data' => array(
					'rows' => $rows,
					'size' => $size
				)
			);

			// aggregation table size
			if (Util\Configuration::read('aggregation')) {
				$agg_rows = $this->sqlCount($conn, 'aggregate');
				$agg_size = $this->dbSize($conn, 'aggregate');

				$capabilities['database']['aggregation'] = array(
					'rows' => $agg_rows,
					'size' => $agg_size,
					'ratio' => ($agg_rows) ? $rows/$agg_rows : 0
				);
			}
		}

		if (is_null($section) || $section == 'formats') {
			$capabilities['formats'] = array_keys(Router::$viewMapping);
		}

		if (is_null($section) || $section == 'contexts') {
			$capabilities['contexts'] = array_keys(Router::$controllerMapping);
		}

		if (is_null($section) || $section == 'definitions') {
			// unresolved artifact from Symfony migration
			if (!is_null($section)) { // only caching when we don't request dynamic informations
				$this->view->setCaching('expires', time()+2*7*24*60*60); // cache for 2 weeks
			}

			$capabilities['definitions']['entities'] = Definition\EntityDefinition::get();
			$capabilities['definitions']['properties'] = Definition\PropertyDefinition::get();
		}

		if (count($capabilities) == 0) {
			throw new \Exception('Invalid capability identifier: \'' . $section . '\'');
		}

		return array('capabilities' => $capabilities);
	}
}

?>
