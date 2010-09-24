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
	public function get($capabilities, $sub) {
		if ($capabilities == 'definition' && in_array($sub, array('property', 'entity'))) {
			$class = 'Volkszaehler\Definition\\' . ucfirst($sub) . 'Definition';
			$json = $class::getJSON();
			$this->view->add(array('definition' => array($sub => $json)));
		}
		elseif ($capabilities == 'version') {
		}
		else {
			throw new \Exception('Unknown capability information: ' . implode('/', func_get_args()));
		}
	}
}

?>
