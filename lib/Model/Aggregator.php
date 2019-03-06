<?php
/**
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
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

use Doctrine\ORM\Mapping;

use Volkszaehler\Interpreter;
use Doctrine\ORM;
use Doctrine\Common\Collections;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Aggregator entity
 *
 * @author Steffen Vogel <info@steffenvogel.de>
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
	 * Constructor
	 */
	public function __construct($type) {
		parent::__construct($type);

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
		if ($this->children->contains($child)) {
			throw new \Exception('Entity is already a child of the aggregator');
		}

		if ($child instanceof Aggregator && ($this === $child || $child->contains($this, true))) {
			throw new \Exception('Circular group relation.');
		}

		$this->children->add($child);
	}

	/**
	 * Checks if aggregator contains given entity
	 *
	 * @param Entity $entity
	 * @param boolean $recursive should we search recursivly?
	 */
	protected function contains(Entity $entity, $recursive = FALSE) {
		if ($this->children->contains($entity)) {
			return TRUE;
		}

		if ($recursive) {
			foreach ($this->children as $child) {
				if ($child instanceof Aggregator && $child->contains($entity, $recursive)) {
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Remove child from group
	 *
	 * @param Entity $child
	 * @todo check if the entity is member of the group
	 * @todo add bidrectional association
	 */
	public function removeChild(Entity $child) {
		if (!$this->children->removeElement($child)) {
			throw new \Exception('This entity is not a child of this aggregator');
		}
	}

	/*
	 * Setter & getter
	 */
	public function getChildren() { return $this->children->toArray(); }
}


?>
