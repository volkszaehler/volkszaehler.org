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

use Doctrine\ORM;
use Doctrine\Common\Collections;
use Volkszaehler\Util;
use Volkszaehler\Definition;

/**
 * Entity superclass for all objects referenced by a UUID
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 *
 * @Entity
 * @Table(name="entities")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="class", type="string")
 * @DiscriminatorMap({
 * 		"channel" = "Channel",
 * 		"aggregator" = "Aggregator"
 * })
 * @HasLifecycleCallbacks
 */
abstract class Entity {
	/**
	 * @Id
	 * @Column(type="integer", nullable=false)
	 * @GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/** @Column(type="string", length=36, nullable=false, unique=true) */
	protected $uuid;

	/** @Column(type="string", nullable=false) */
	protected $type;

	/**
	 * @OneToMany(targetEntity="Property", mappedBy="entity", cascade={"remove", "persist"}, orphanRemoval=true)
	 * @OrderBy({"key" = "ASC"})
	 */
	protected $properties = NULL;

	/**
	 * @ManyToMany(targetEntity="Aggregator", mappedBy="children")
	 */
	protected $parents = NULL;

	/**
	 * Constructor
	 *
	 * @param string $type
	 */
	public function __construct($type) {
		if (!Definition\EntityDefinition::exists($type)) {
			throw new \Exception('Unknown entity type: \'' . $type . '\'');
		}

		$this->type = $type;
		$this->uuid = (string) Util\UUID::mint(); // generate random UUID

		$this->properties = new Collections\ArrayCollection();
		$this->parents = new Collections\ArrayCollection();
	}


	/**
	 * Checks for required and invalid properties
	 *
	 * @PrePersist
	 */
	public function checkProperties() {
		$missingProperties = array_diff($this->getDefinition()->getRequiredProperties(), array_keys($this->getProperties()));
		$invalidProperties = array_diff(array_keys($this->getProperties()), $this->getDefinition()->getValidProperties());

		if (count($missingProperties) > 0) {
			throw new \Exception('Entity \'' . $this->getType() . '\' requires propert' . ((count($missingProperties) == 1) ? 'y' : 'ies') . ": '" . implode(', ', $missingProperties) . "'");
		}

		if (count($invalidProperties) > 0) {
			throw new \Exception('Propert' . ((count($invalidProperties) == 1) ? 'y' : 'ies') . ' \'' . implode(', ', $invalidProperties) . '\' ' . ((count($invalidProperties) == 1) ? 'is' : 'are') . ' not valid for entity \'' . $this->getType() . '\'');
		}
	}

	/**
	 * Get a property by name
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getProperty($key) {
		return $this->findProperty($key)->getValue();
	}

	/**
	 * Get all properties or properties by prefix
	 *
	 * @param string $prefix
	 * @return array
	 */
	public function getProperties($prefix = NULL) {
		$properties = array();
		foreach ($this->properties as $property) {
			if (substr($property->getKey(), 0, strlen($prefix)) == $prefix) {
				$properties[$property->getKey()] = $property->getValue();
			}
		}

		return $properties;
	}

	/**
	 * Get properties by regexp pattern
	 *
	 * @param string $regex
	 * @return array
	 */
	public function getPropertiesByRegex($regex) {
		$properties = array();
		foreach ($this->properties as $property) {
			if (preg_match($regex, $property->getKey())) {
				$properties[$property->getKey()] = $property->getValue();
			}
		}

		return $properties;
	}

	/**
	 * Find property by key
	 *
	 * @param string $key
	 * @return Model\Property
	 */
	protected function findProperty($key) {
		foreach ($this->properties as $property) {
			if ($property->getKey() == $key) {
				return $property;
			}
		}

		return FALSE; // not found
	}

	/**
	 * Check if a property exists
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function hasProperty($key) {
		foreach ($this->properties as $property) {
			if ($property->getKey() == $key) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Set property by key/value
	 *
	 * @param string $key name of the property
	 * @param mixed $value of the property
	 */
	public function setProperty($key, $value) {
		if ($property = $this->findProperty($key)) {
			// property already exists; just change value
			$property->setValue($value);
		}
		else {
			// create new property
			$property = new Property($this, $key, $value);
			$this->properties->add($property);
		}
	}

	/**
	 * Unset property
	 *
	 * @param string $name of the property
	 * @param Doctrine\EntityManager $em
	 */
	public function deleteProperty($key) {
		$property = $this->findProperty($key);

		if (!$property) {
			throw new \Exception('Entity has no property: \'' . $key . '\'');
		}

		$this->properties->removeElement($property);
	}


	/**
	 * HACK - Cast properties to internal state
	 * see https://github.com/doctrine/doctrine2/pull/382
	 */
	public function castProperties() {
		foreach ($this->properties as $property) {
			$property->cast();
		}
	}

	/*
	 * Setter & getter
	 */

	public function getId() { return $this->id; }		// read only
	public function getUuid() { return $this->uuid; }	// read only
	public function getType() { return $this->type; }	// read only
	public function getDefinition() { return Definition\EntityDefinition::get($this->type); }
}

?>
