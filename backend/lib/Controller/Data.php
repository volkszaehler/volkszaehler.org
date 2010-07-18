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

class Data extends Controller {
	public function get() {
		// TODO why not ucids?
		$ids = explode(',', trim($this->view->request->getParameter('ids')));
		
		$q = $this->em->createQuery('SELECT c FROM Volkszaehler\Model\Channel\Channel c WHERE c.id IN (' . implode(', ', $ids) . ')');
		$channels = $q->execute();

		$from = ($this->view->request->getParameter('from')) ? (int) $this->view->request->getParameter('from') : NULL;
		$to = ($this->view->request->getParameter('to')) ? (int) $this->view->request->getParameter('to') : NULL;
		$groupBy = ($this->view->request->getParameter('groupBy')) ? $this->view->request->getParameter('groupBy') : NULL;	// get all readings by default

		foreach ($channels as $channel) {
			$interpreter = $channel->getInterpreter($this->em);
			$this->view->add($interpreter->getValues($from, $to, $groupBy));
		}
	}
	
	public function add() {
		$ucid = $this->view->request->getParameter('ucid');
		$channel = $this->em->getRepository('Volkszaehler\Model\Channel\Channel')->findOneBy(array('uuid' => $ucid));
		
		$value = (float) $this->view->request->getParameter('value');
		$ts = (int) $this->view->request->getParameter('timestamp');
		if ($ts == 0) {
			$ts = microtime(true) * 1000;
		}
		
		$data = new \Volkszaehler\Model\Data($channel, $value, $ts);
		
		$channel->addData($data);
		
		$this->em->persist($data);
		$this->em->flush();
	}
	
	/*
	 * prune all data from database
	 */
	public function delete() {	// TODO add user authentification
		$dql = 'DELETE FROM \Volkszaehler\Model\Data WHERE channel_id = ' . $this->id;
		
		if ($this->view->request->getParameter('from')) {
			$dql .= ' && timestamp > ' . (int) $this->view->request->getParameter('from');
		}
		
		if ($this->view->request->getParameter('to')) {
			$dql .= ' && timestamp < ' . $this->view->request->getParameter('to');
		}
		
		$q = $em->createQuery($dql);
		$q->execute();
	}
}