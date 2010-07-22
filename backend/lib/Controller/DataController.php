<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package data
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

namespace Volkszaehler\Controller;

use Volkszaehler\Util;

/**
 * data controller
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @todo call via redirect from Controller\Channel
 * @package data
 */
class DataController extends Controller {

	/**
	 * @todo authentification/indentification
	 */
	public function get() {
		// TODO use uuids for groups or channels
		$ids = explode(',', trim($this->view->request->getParameter('ids')));

		$q = $this->em->createQuery('SELECT c FROM Volkszaehler\Model\Channel c WHERE c.id IN (' . implode(', ', $ids) . ')');
		$channels = $q->execute();

		$from = ($this->view->request->getParameter('from')) ? (int) $this->view->request->getParameter('from') : NULL;
		$to = ($this->view->request->getParameter('to')) ? (int) $this->view->request->getParameter('to') : NULL;
		$groupBy = ($this->view->request->getParameter('groupBy')) ? $this->view->request->getParameter('groupBy') : NULL;	// get all readings by default

		foreach ($channels as $channel) {
			$interpreter = $channel->getInterpreter($this->em);
			$this->view->add($channel, $interpreter->getValues($from, $to, $groupBy));
		}
	}

	/**
	 *
	 */
	public function add() {
		$ucid = $this->view->request->getParameter('ucid');
		$channel = $this->em->getRepository('Volkszaehler\Model\Channel\Channel')->findOneBy(array('uuid' => $ucid));

		$value = (float) $this->view->request->getParameter('value');
		$ts = (int) $this->view->request->getParameter('timestamp');
		if ($ts == 0) {
			$ts = microtime(TRUE) * 1000;
		}

		$data = new \Volkszaehler\Model\Data($channel, $value, $ts);

		$channel->addData($data);

		$this->em->persist($data);
		$this->em->flush();
	}

	/**
	 * prune data from database
	 *
	 * @todo authentification/indentification
	 */
	public function delete() {
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

?>
