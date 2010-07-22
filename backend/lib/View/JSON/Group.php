<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package group
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
 *
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
 * JSON group view
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package group
 */
class Group extends JSON {

	public function add(\Volkszaehler\Model\Group $obj, $recursive = FALSE) {
		$group['uuid'] = (string) $obj->getUuid();
		$group['name'] = $obj->getName();
		$group['description'] = $obj->getDescription();

		if ($recursive) {	// TODO add nested groups in json view
			$children = $obj->getChildren();

			foreach ($children as $child) {
				$this->addGroup($child, $recursive);
			}
		}

		$this->json['groups'][] = $group;
	}
}

?>

