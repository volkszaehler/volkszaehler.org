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

namespace Volkszaehler\View\JSON;

/**
 * JSON channel view
 *
 * also used for data
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class JSONChannelView extends JSONView {

	public function add(\Volkszaehler\Model\Channel $obj, array $data = NULL) {
		$channel['uuid'] = (string) $obj->getUuid();
		$channel['indicator'] = $obj->getIndicator();
		$channel['unit'] = $obj->getUnit();
		$channel['name'] = $obj->getName();
		$channel['description'] = $obj->getDescription();

		// TODO adapt to new indicator style
		if (is_subclass_of($obj, '\Volkszaehler\Model\Channel\Meter')) {
			$channel['resolution'] = (int) $obj->getResolution();
			$channel['cost'] = (float) $obj->getCost();
		}

		if (isset($data)) {
			$channel['data'] = array();
			foreach ($data as $reading) {
				$channel['data'][] = array($reading['timestamp'], $reading['value'], $reading['count']);
			}
		}

		$this->json['channels'][] = $channel;
	}
}

?>