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

namespace Volkszaehler\Logger;

use Volkszaehler\Model;
use Doctrine\ORM;

/**
 * Logger for the the original volkszaehler.org prototype based on ethersex's watchasync
 *
 * @package default
 * @link http://github.com/ethersex/ethersex/blob/master/services/watchasync
 * @author Steffen Vogel <info@steffenvogel.de>
 * @todo to be implemented
 */
class PrototypeLogger extends Logger {
	/**
	 * @return array of Model\Data
	 */
	public function getData() {
		$uuid = $this->request->getParameter('uuid');
		$port = $this->request->getParameter('port');

		$channel = $this->em->getRepository('Volkszaehler\Model\Channel')->findOneBy(array(
			'description' => $uuid,
			'name' => $port
		));

		if ($channel) {
			if (!($time = $this->request->getParameter('time'))) {
				$time = (int) (microtime(TRUE) * 1000);
			}
			return new Model\Data($channel, 1, $time);
		}
		else {
			return FALSE;
		}
	}

	/**
	 * the prototyp protocol doesn't have a version
	 */
	public function getVersion() {
		return FALSE;
	}
}

/*
 * Just some documentation
 *
 * /httplog/httplog.php?port=<port>&uuid=<uuid>&time=<unixtimestamp>
 *
 * <port> = <prefix:PC><no#>
 * <unixstimestamp> = timestamp in ms since 1970
 *
 */

?>