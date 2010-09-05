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
 * Aggregator controller
 *
 * @author Steffen Vogel (info@steffenvogel.de)
 * @package default
 */
use Volkszaehler\Model;

class AggregatorController extends Controller {

	/**
	 * Get aggregators by filter
	 *
	 * @todo filter to root aggregators when using recursion
	 */
	public function get() {
		$dql = 'SELECT g, c, d, p FROM Volkszaehler\Model\Aggregator g LEFT JOIN g.children c LEFT JOIN g.channels d LEFT JOIN g.properties p';

		// TODO fix this (depending on DDC-719)
		if ($recursion = $this->view->request->getParameter('recursive')) {
			//$dql .= ' WHERE g.parents IS EMPTY';
		}

		$q = $this->em->createQuery($dql);
		$groups = $q->getResult();

		foreach ($groups as $group) {
			$this->view->addAggregator($group, $recursion);
		}
	}

	/**
	 * Add new aggregator as child of a parent aggregator
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
}

?>