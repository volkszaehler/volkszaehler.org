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
 * Group controller
 *
 * @author Steffen Vogel (info@steffenvogel.de)
 * @package default
 */
use Volkszaehler\Model;

class GroupController extends Controller {

	/**
	 * Get groups by filter
	 *
	 * @todo filter to root groups when using recursion
	 */
	public function get() {
		$dql = 'SELECT g, c, d FROM Volkszaehler\Model\Aggregator g LEFT JOIN g.children c LEFT JOIN g.channels d';

		// TODO fix this (depending on DDC-719)
		if ($recursion = $this->view->request->getParameter('recursive')) {
			//$dql .= ' WHERE g.parents IS EMPTY';
		}

		if ($uuid = $this->view->request->getParameter('uuid')) {
			// TODO add conditions
		}

		if ($ugid = $this->view->request->getParameter('ugid')) {
			// TODO add conditions
		}

		$q = $this->em->createQuery($dql);
		$groups = $q->getResult();

		foreach ($groups as $group) {
			$this->view->addAggregator($group, $recursion);
		}
	}

	/**
	 * Add new group as child of a parent group
	 *
	 * @todo add parent validation to model?
	 */
	public function add() {
		$ugid = $this->view->request->getParameter('ugid');
		$parent = $this->em->getRepository('Volkszaehler\Model\Aggregator')->findOneBy(array('uuid' => $ugid));

		if ($parent == FALSE) {
			throw new \Exception('every group needs a parent');
		}

		$group = new Model\Aggregator();

		$group->setName($this->view->request->getParameter('name'));
		$group->setDescription($this->view->request->getParameter('description'));

		$this->em->persist($group);
		$parent->addAggregator($group);

		$this->em->flush();

		$this->view->add($group);
	}

	/**
	 * @todo authentification/indentification
	 */
	public function delete() {
		$ugid = $this->view->request->getParameter('ugid');
		$group = $this->em->getRepository('Volkszaehler\Model\Aggregator')->findOneBy(array('uuid' => $ugid));

		$this->em->remove($group);
		$this->em->flush();
	}

	/**
	 * edit group properties
	 *
	 * @todo implement Controller\Aggregator::edit()
	 */
	public function edit() {

	}
}

?>