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

use Volkszaehler\Model;
use Volkszaehler\Util;
use Volkszaehler\View;
use Volkszaehler\Definition;

/**
 * Capabilities controller
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class CapabilitiesController extends Controller {

	/**
	 * @todo
	 * @param string $capabilities
	 * @param string $sub
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
			
			$capabilities['configuration'] = $configuration;
		}
/*
		// TODO fix COUNT performance on large INNODB tables
		if (is_null($section) || $section == 'database') {
			$conn = $this->em->getConnection(); // get dbal connection from EntityManager

			$sql = 'SELECT ('.
				   '	SELECT COUNT(1) '.
				   '	FROM data'.
				   ') AS rows, ('.
				   '	SELECT SUM(data_length + index_length) '.
				   '	FROM information_schema.tables '.
				   '	WHERE table_schema = ?'.
				   ') AS size';

			$res = $conn->fetchArray($sql, array(Util\Configuration::read('db.dbname')));

			$capabilities['database'] = array(
				'rows' => $res[0],
				'size' => $res[1]
			);
		}
*/				
		if (is_null($section) || $section == 'formats') {
			$capabilities['formats'] = array_keys(\Volkszaehler\Router::$viewMapping);
		}
		
		if (is_null($section) || $section == 'contexts') {
			$capabilities['contexts'] = array_keys(\Volkszaehler\Router::$controllerMapping);
		}
		
		if (is_null($section) || $section == 'definitions') {
			if (!is_null($section)) { // only caching when we doesn't request dynamic informations
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
