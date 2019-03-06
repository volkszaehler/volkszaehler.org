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

use Volkszaehler\Definition;
use Volkszaehler\Util;
use Volkszaehler\Model;

/**
 * Property entity
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 *
 * @Entity
 * @Table(
 * 		name="properties",
 * 		uniqueConstraints={
 * 			@UniqueConstraint(name="property_unique", columns={"entity_id", "pkey"})
 * 		}
 * )
 * @HasLifecycleCallbacks
 */
class Property {
	/**
	 * @Id
	 * @Column(type="integer", nullable=false)
	 * @GeneratedValue(strategy="AUTO")
	 *
	 * @todo wait until DDC-117 is fixed (PKs on FKs)
	 */
	protected $id;

	/**
	 * @Column(name="pkey", type="string", nullable=false)
	 * HINT: column name "key" is reserved by mysql
	 */
	protected $key;

	/** @Column(type="text", nullable=false) */
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
		$type = $this->getDefinition()->getType();
		if ($type == 'multiple') {
			// TODO
		}
		elseif ($type == 'text') {
			settype($this->value, 'string');
		}
		else {
			settype($this->value, $type);
		}
	}

	/**
	 * Undo type cast to prevent Doctrine storing unmodified properties
	 *
	 * @PreFlush
	 */
	public function uncast() {
		if ($this->value === false) { // force boolean false to 0 instead of ''
			$this->value = '0';
		}
		else {
			settype($this->value, 'string');
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
		if (!Definition\PropertyDefinition::exists($this->key)) {
			throw new \Exception('Invalid property: \'' . $this->key . '\'');
		}

		if (!$this->getDefinition()->validateValue($this->value)) {
			throw new \Exception('Invalid property value: \'' . $this->value . '\'');
		}
	}

	/*
	 * Setter & getter
	 */

	public function getKey() { return $this->key; }
	public function getValue() { return $this->value; }
	public function getDefinition() { return Definition\PropertyDefinition::get($this->key); }

	public function setValue($value) { $this->value = $value; }
}

?>
