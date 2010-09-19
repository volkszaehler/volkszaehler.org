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
 * Data controller
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @todo call via redirect from Controller\Channel
 * @package default
 */
class DataController extends Controller {

	/**
	 * Query for data by given channel or group
	 */
	public function get(Model\Entity $entity) {
		$from = $this->view->request->getParameter('from');
		$to = $this->view->request->getParameter('to');
		$groupBy = $this->view->request->getParameter('groupBy');

		return $entity->getInterpreter($this->em, $from, $to)->getValues($groupBy);
	}

	/**
	 * Log new readings with logger interfaces
	 */
	public function add() {

	}
}

?>
