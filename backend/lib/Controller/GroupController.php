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

/**
 * group controller
 *
 * @author Steffen Vogel (info@steffenvogel.de)
 * @package default
 */
class GroupController extends Controller {

	/**
	 *
	 */
	public function get() {
		// TODO get groups from entitymanager according to API specs

		foreach ($groups as $group) {
			$this->view->addGroup($group);
		}
	}

	/**
	 *
	 */
	public function add() {
		$group = new Group();

		$group->name = $this->view->request->getParameter('name');
		$group->description = $this->view->request->getParameter('description');

		$this->em->persist($group);
		$this->em->flush();

		$this->view->add($group);
	}

	/**
	 * @todo authentification/indentification
	 */
	public function delete() {
		$group = Group::getByUuid($this->view->request->getParameter('ugid'));

		$this->em->remove($group);
		$this->em->flush();
	}

	/**
	 * edit group properties
	 *
	 * @todo implement Controller\Group::edit()
	 */
	public function edit() {

}
}

?>