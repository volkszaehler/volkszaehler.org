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

use Doctrine\Common\Collections;
use Volkszaehler\Util;

/**
 * Entity superclass for all objects referenced by a UUID
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 *
 * @Entity
 * @Table(name="entities")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="class", type="string")
 * @DiscriminatorMap({
 * 		"channel" = "Channel",
 * 		"group" = "Aggregator"
 * })
 * @HasLifecycleCallbacks
 */
abstract class Entity {
	/**
	 * @Id
	 * @Column(type="smallint", nullable=false)
	 * @GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/** @Column(type="string", length=36, nullable=false, unique=true) */
	protected $uuid;

	/** @Column(type="string", nullable=false) */
	protected $type;

	/**
	 * @OneToMany(targetEntity="Token", mappedBy="entity")
	 */
	protected $tokens = NULL;

	/**
	 * @OneToMany(targetEntity="Property", mappedBy="entity")
	 * @OrderBy({"name" = "ASC"})
	 */
	protected $properties = NULL;

	/**
	 * Constructor
	 *
	 * @param string $type
	 * @param array $properties of Model\Property
	 */
	public function __construct($type, $properties = NULL) {
		if (!EntityDefinition::exists($type)) {
			throw new \Exception('unknown entity type');
		}

		$this->type = $type;
		$this->uuid = Util\UUID::mint();

		$this->tokens = new Collections\ArrayCollection();
		$this->properties = new Collections\ArrayCollection();

		if (isset($properties)) {
			foreach($properties as $property) {
				$this->properies->add($property);
			}
		}
	}

	/**
	 * Checks for optional and required properties according to share/entities.json
	 *
	 * Throws an exception if something is incorrect
	 *
	 * @PrePersist
	 * @PreUpdate
	 * @PostLoad
	 * @todo to be implemented
	 */
	protected function validate() {

	}

	/**
	 * Get a property by name
	 *
	 * @param string $name
	 * @return Model\Property
	 */
	public function getProperty($name) {
		return $this->properties->filter(function($property) use ($name) {
			return $property->getName() == $name;
		})->first();
	}

	/**
	 * Get all properties or properties by prefix
	 *
	 * @param string $prefix
	 */
	public function getProperties($prefix = NULL) {
		if (is_null($prefix)) {
			return $this->properties;
		}
		else {
			return $this->properties->filter(function($property) use ($prefix) {
				return substr($property->getName(), 0, strlen($prefix) + 1) == $prefix . ':';
			});
		}
	}

	/**
	 * @param string $name of the property
	 * @param string|integer|float $value of the property
	 * @todo to be implemented
	 */
	public function setProperty($name, $value) {

	}

	/**
	 * @param string $name of the property
	 * @todo to be implemented
	 */
	public function unsetProperty($name) {

	}

	/*
	 * Setter & Getter
	 */
	public function getId() { return $this->id; }		// read only
	public function getUuid() { return $this->uuid; }	// read only
	public function getType() { return $this->type; }	// read only
	public function getDefinition() { return EntityDefinition::get($this->type); }

	/**
	 * Get interpreter to obtain data and statistical information for a given time interval
	 *
	 * @param Doctrine\ORM\EntityManager $em
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 * @return Interpreter
	 */
	public function getInterpreter(\Doctrine\ORM\EntityManager $em, $from, $to) {
		$interpreterClassName = 'Volkszaehler\Interpreter\\' . $this->getDefinition()->getInterpreter();
		return new $interpreterClassName($this, $em, $from, $to);
	}
}

class EntityDefinition extends Util\Definition {
	/** @var string File containing the JSON definitons */
	const FILE = '/share/entities.json';

	/** @var array list of required properties */
	protected $required = array();

	/** @var array list of optional properties */
	protected $optional = array();

	/** @var string classname of intepreter (see backend/lib/Interpreter/) */
	protected $interpreter;

	/** @var string optional for Aggregator class entities */
	protected $unit;

	/**
	 * @todo url relative or absolute?
	 * @var string
	 */
	protected $icon;

	/*
	 * Setter & Getter
	 */
	public function getInterpreter() { return $this->interpreter; }
	public function getUnit() { return $this->unit; }
}

?>
