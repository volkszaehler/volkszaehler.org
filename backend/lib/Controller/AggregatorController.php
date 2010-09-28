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
use Volkszaehler\Definition;

use Volkszaehler\Model;

class AggregatorController extends EntityController {
	/**
	 * Get aggregator
	 */
	public function get($identifier) {
		$aggregator = parent::get($identifier);

		if ($aggregator instanceof Model\Aggregator) {
			return $aggregator;
		}
		else {
			throw new \Exception($identifier . ' is not a group uuid');
		}
	}

	/**
	 * Create new aggregator or add entity to aggregator
	 */
	public function add($identifier = NULL) {
		if (isset($identifier)) {	// add entity to aggregator
			$aggregator = $this->get($identifier);

			if ($uuid = $this->view->request->getParameter('uuid')) {
				$ec = new EntityController($this->view, $this->em);
				$aggregator->addChild($ec->get($uuid));
			}
			else {
				throw new \Exception('You have to specifiy a uuid to add');
			}
		}
		else {	// create new aggregator
			$aggregator = new Model\Aggregator('group');	// TODO support for other aggregator types
			$this->setProperties($aggregator);
			$this->em->persist($aggregator);

			if ($this->view->request->getParameter('setcookie')) {
				$this->setCookie($channel);
			}
		}

		$this->em->flush();

		return $aggregator;
	}

	/**
	 * Delete Aggregator or remove entity from aggregator
	 */
	public function delete($identifier) {
		if (isset($identifier) && $uuid = $this->view->request->getParameter('uuid')) {	// remove entity from aggregator
			$aggregator = $this->get($identifier);

			if ($uuid) {
				$ec = new EntityController($this->view, $this->em);
				$aggregator->removeChild($ec->get($uuid));

				$this->em->flush();
			}
			else {
				throw new \Exception('You have to specifiy a uuid to remove');
			}
		}
		else {	// remove aggregator
			parent::delete($identifier);
		}

		return $aggregator;
	}
}

?>