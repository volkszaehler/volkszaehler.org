<?php
/*
 * Copyright (c) 2010 by Justin Otherguy <justin@justinotherguy.org>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License (either version 2 or
 * version 3) as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * For more information on the GPL, please go to:
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace Volkszaehler\View\Json;

class Channel extends Json {
	
	public function add(\Volkszaehler\Model\Channel $obj, $data = NULL) {
		$channel['id'] = (int) $obj->getId();
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
			
		if (!is_null($data) && is_array($data)) {
			$channel['data'] = array();
			foreach ($data as $reading) {
				$channel['data'][] = array($reading['timestamp'], $reading['value'], $reading['count']);
			}
		}
			
		$this->json['channels'][] = $channel;
	}
}

?>