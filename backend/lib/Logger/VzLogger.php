<?php
/**
 * @package default
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @license http://www.gnu.org/licenses/gpl.txt GNU Public License
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

namespace Volkszaehler\Logger;

use Volkszaehler\Model;

use Doctrine\ORM;

/**
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 *
 */
class VzLogger extends Logger {
	/**
	 * @return array of Model\Data
	 */
	public function getData() {
		$ucid = $this->view->request->getParameter('ucid');
		$channel = $this->em->getRepository('Volkszaehler\Model\Channel\Channel')->findOneBy(array('uuid' => $ucid));

		$value = (float) $this->view->request->getParameter('value');
		$ts = (int) $this->view->request->getParameter('timestamp');
		if ($ts == 0) {
			$ts = microtime(TRUE) * 1000;
		}

		$data = new Model\Data($channel, $value, $ts);
	}

	/**
	 * @return string the version
	 */
	public function getVersion() {
		return $this->request->getParameter('version');
	}
}


?>