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

use \Volkszaehler\Model;

/**
 * Channel controller
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class ChannelController extends Controller {

	/**
	 * Get channels by filter
	 *
	 * @todo authentification/indentification
	 * @todo implement filters
	 */
	public function get() {
		$dql = 'SELECT c, p FROM Volkszaehler\Model\Channel c LEFT JOIN c.properties p';

		if ($uuid = $this->view->request->getParameter('uuid')) {
			// TODO add conditions
		}

		if ($ugid = $this->view->request->getParameter('ugid')) {
			// TODO add conditions
		}

		if ($indicator = $this->view->request->getParameter('indicator')) {
			// TODO add conditions
		}

		$q = $this->em->createQuery($dql);
		$channels = $q->getResult();

		foreach ($channels as $channel) {
			$this->view->addChannel($channel);
		}
	}

	/**
	 * Add channel
	 *
	 * @todo validate input and throw exceptions
	 */
	public function add() {
		$channel = new Model\Channel($this->view->request->getParameter('indicator'));

		$channel->setName($this->view->request->getParameter('name'));
		$channel->setDescription($this->view->request->getParameter('description'));

		$channel->setResolution($this->view->request->getParameter('resolution'));
		$channel->setCost($this->view->request->getParameter('cost'));

		$this->em->persist($channel);
		$this->em->flush();

		$this->view->addChannel($channel);
	}

	/**
	 * Delete channel by uuid
	 *
	 * @todo authentification/indentification
	 */
	public function delete() {
		$ucid = $this->view->request->getParameter('ucid');
		$channel = $this->em->getRepository('Volkszaehler\Model\Channel')->findOneBy(array('uuid' => $ucid));

		$this->em->remove($channel);
		$this->em->flush();
	}

	/**
	 * Edit channel properties
	 *
	 * @todo authentification/indentification
	 * @todo to be implemented
	 */
	public function edit() {

	}
}

?>