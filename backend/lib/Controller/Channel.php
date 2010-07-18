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

namespace Volkszaehler\Controller;

use \Volkszaehler\Model;

class Channel extends Controller {
	public function get() {
		// TODO filter by uuid, type etc...
		$channels = $this->em->getRepository('Volkszaehler\Model\Channel\Channel')->findAll();
		
		foreach ($channels as $channel) {
			$this->view->add($channel);
		}
	}
	
	public function add() {
		// TODO validate input
		$channel = new Model\Channel\Meter('power');
		
		$channel->setName($this->view->request->getParameter('name'));
		$channel->setResolution($this->view->request->getParameter('resolution'));
		$channel->setDescription($this->view->request->getParameter('description'));
		$channel->setCost($this->view->request->getParameter('cost'));
		
		$this->em->persist($channel);
		$this->em->flush();
		
		$this->view->add($channel);
	}
	
	// TODO check for valid user identity
	public function delete() {
		$ucid = $this->view->request->getParameter('ucid');
		$channel = $this->em->getRepository('Volkszaehler\Model\Channel\Channel')->findOneBy(array('uuid' => $ucid));
		
		$this->em->remove($channel);
		$this->em->flush();
	}
	
	public function edit() {
		// TODO implement ChannelController::edit();
	}
}

?>