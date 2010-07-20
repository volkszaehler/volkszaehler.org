chann<?php
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

namespace Volkszaehler\Controller;

class Group extends Controller {
	public function get() {
		// TODO get groups from entitymanager according to API specs
		
		foreach ($groups as $group) {
			$this->view->addGroup($group);
		}
	}
	
	
	public function add() {
		$group = new Group();
		
		$group->name = $this->view->request->getParameter('name');
		$group->description = $this->view->request->getParameter('description');
		
		$this->em->persist($group);
		$this->em->flush();
		
		$this->view->add($group);
	}
	
	// TODO authentification/indentification
	public function delete() {
		$group = Group::getByUuid($this->view->request->getParameter('ugid'));
		
		$this->em->remove($group);
		$this->em->flush();
	}
	
	public function edit() {
		// TODO implement Controller\Group::edit();
	}
}

?>