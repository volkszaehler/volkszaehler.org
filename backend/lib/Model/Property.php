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

use Volkszaehler\Util;
use Volkszaehler\Model;

/**
 * Property entity
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 *
 * @Entity
 * @Table(
 * 		name="properties",
 * 		uniqueConstraints={
 * 			@UniqueConstraint(name="unique_keys", columns={"entity_id", "`key`"})
 * 		}
 * )
 * @HasLifecycleCallbacks
 */
class Property {
	/**
	 * @Id
	 * @Column(type="smallint", nullable=false)
	 * @GeneratedValue(strategy="AUTO")
	 *
	 * @todo wait until DDC-117 is fixed (PKs on FKs)
	 */
	protected $id;

	/** @Column(name="`key`", type="string", nullable=false) */
	protected $key;

	/** @Column(type="string", nullable=false) */
	protected $value;

	/** @ManyToOne(targetEntity="Entity", inversedBy="properties") */
	protected $entity;

	/**
	 * Constructor
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function __construct(Model\Entity $entity, $key, $value) {
		$this->entity = $entity;

		$this->key = $key;
		$this->value = $value;
	}

	/**
	 * Cast property value according to $this->type
	 *
	 * @PostLoad
	 */
	public function cast() {
		if ($this->getDefinition()->getType() != 'multiple') {
			settype($this->value, $this->getDefinition()->getType());
		}
	}

	/**
	 * Validate property key & value
	 *
	 * Throws an exception if something is incorrect
	 *
	 * @PrePersist
	 * @PreUpdate
	 */
	public function validate() {
		if (!PropertyDefinition::exists($this->key)) {
			throw new \Exception('Invalid property key: ' . $this->key);
		}

		$this->cast();	// TODO not safe

		if (!$this->getDefinition()->validateValue($this->value)) {
			throw new \Exception('Invalid property value: ' . $this->value);
		}
	}

	/**
	 * @PreRemove
	 */
	public function checkRemove() {
		if (in_array($this->key, $this->entity->getDefinition()->getRequiredProperties())) {
			throw new \Exception($this->key . ' is a required property for the entity: ' . $this->entity->getType());
		}
	}

	/**
	 * @PrePersist
	 */
	public function checkPersist() {
		if (!in_array($this->key, $this->entity->getDefinition()->getValidProperties())) {
			throw new \Exception($this->key . ' is not a valid property for the entity: ' . $this->entity->getType());
		}
	}

	/*
	 * Setter & Getter
	 */
	public function getKey() { return $this->key; }
	public function getValue() { return $this->value; }
	public function getDefinition() { return PropertyDefinition::get($this->key); }

	public function setValue($value) { $this->value = $value; }
}

?>
