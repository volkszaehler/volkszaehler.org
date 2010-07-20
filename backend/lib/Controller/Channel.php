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
	
	// TODO authentification/indentification
	public function get() {
		$dql = 'SELECT c FROM Volkszaehler\Model\Channel c';

		if ($this->view->request->getParameter('uuid')) {
			// TODO add conditions
		}
		
		if ($this->view->request->getParameter('ugid')) {
			// TODO add conditions
		}
		
		if ($this->view->request->getParameter('indicator')) {
			// TODO add conditions
		}
		
		$q = $this->em->createQuery($dql);
		$channels = $q->getResult();
		
		foreach ($channels as $channel) {
			$this->view->add($channel);
		}
	}
	
	// TODO validate input and throw exceptions
	public function add() {
		$channel = new Model\Channel\Meter($this->view->request->getParameter('indicator'));
		
		$channel->setName($this->view->request->getParameter('name'));
		$channel->setDescription($this->view->request->getParameter('description'));
		
		$channel->setResolution($this->view->request->getParameter('resolution'));
		$channel->setCost($this->view->request->getParameter('cost'));
		
		$this->em->persist($channel);
		$this->em->flush();
		
		$this->view->add($channel);
	}
	
	// TODO authentification/indentification
	public function delete() {
		$ucid = $this->view->request->getParameter('ucid');
		$channel = $this->em->getRepository('Volkszaehler\Model\Channel\Channel')->findOneBy(array('uuid' => $ucid));
		
		$this->em->remove($channel);
		$this->em->flush();
	}
	
	// TODO implement Controller\Channel::edit();
	// TODO authentification/indentification
	public function edit() {
		
	}
}

?>