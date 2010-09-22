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

namespace Volkszaehler\Model;

use Volkszaehler\Interpreter;
use Doctrine\ORM;
use Doctrine\Common\Collections;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Aggregator entity
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 * @todo use nested sets: http://github.com/blt04/doctrine2-nestedset
 *
 * @Entity
 */
class Aggregator extends Entity {
	/**
	 * @ManyToMany(targetEntity="Entity", inversedBy="parents")
	 * @JoinTable(name="entities_in_aggregator",
	 * 		joinColumns={@JoinColumn(name="parent_id", referencedColumnName="id")},
	 * 		inverseJoinColumns={@JoinColumn(name="child_id", referencedColumnName="id")}
	 * )
	 */
	protected $children = NULL;

	/**
	 * Construct
	 */
	public function __construct($properties = array()) {
		parent::__construct($properties);

		$this->channels = new ArrayCollection();
		$this->children = new ArrayCollection();
	}

	/**
	 * Adds entity as new child
	 *
	 * @param Entity $child
	 * @todo check if the entity is already member of the group
	 * @todo add bidrectional association
	 */
	public function addChild(Entity $child) {
		$this->children->add($child);
	}

	/**
	 * Remove child from group
	 *
	 * @param Entity $child
	 * @todo check if the entity is member of the group
	 * @todo add bidrectional association
	 */
	public function removeChild(Entity $child) {
		return $this->children->removeElement($child);
	}

	/*
	 * Setter & getter
	 */
	public function getChildren() { return $this->children->toArray(); }
}


?>