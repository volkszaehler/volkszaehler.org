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

namespace Volkszaehler\Controller;

use Volkszaehler\Model;
use Volkszaehler\Util;
use Volkszaehler\View;

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
				'precission' => View\View::PRECISSION,
				'database' => Util\Configuration::read('db.driver'),
				'debug' => Util\Configuration::read('debug'),
				'devmode' => Util\Configuration::read('devmode')
			);
			
			$capabilities['configuration'] = $configuration;
		}
		
		if (is_null($section) || $section == 'statistics') { // TODO database statistics
			$statistics = array();

			if ($load = Util\Debug::getLoadAvg()) $statistics['load'] = $load;
			if ($uptime = Util\Debug::getUptime()) $statistics['uptime'] = $uptime*1000;
			if ($commit = Util\Debug::getCurrentCommit()) $statistics['commit-hash'] = $commit;

			$capabilities['statistics'] = $statistics;
		}
		
		if (is_null($section) || $section == 'definitions') {
			$this->view->setCaching('expires', time()+2*7*24*60*60); // cache for 2 weeks
			
			$capabilities['definitions']['entities'] = \Volkszaehler\Definition\EntityDefinition::getJSON();
			$capabilities['definitions']['properties'] = \Volkszaehler\Definition\PropertyDefinition::getJSON();
		}
		
		if (count($capabilities) == 0) {
			throw new \Exception('Invalid capability identifier!');
		}
		
		return $capabilities;
	}
}

?>
