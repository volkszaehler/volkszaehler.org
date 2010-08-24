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
 * Entity superclass for all models with database persistance
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 *
 * @Entity
 * @Table(name="entities")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({"channel" = "Channel", "group" = "Group"})
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
	 * @param array $properties of Model\Property
	 */
	public function __construct($properties = array()) {
		$this->uuid = Util\UUID::mint();
		$this->tokens = new Collections\ArrayCollection();
		$this->properties = new Collections\ArrayCollection();
	}

	/**
	 * Getter & setter
	 */

	/**
	 *
	 * @param string $name
	 * @return Model\Property
	 */
	public function getProperty($name) {

	}

	public function getProperties() {
		return $this->properties;
	}

	public function setProperty($name, $value) {

	}

	public function unsetProperty($name) {

	}

	public function getId() { return $this->id; }		// read only
	public function getUuid() { return $this->uuid; }	// read only
}

class EntityDefiniton extends Util\JSONDefinition {
	/**
	 * File containing the JSON definitons
	 *
	 * @var string
	 */
	const FILE = '/share/entities.json';

	/**
	 * List of required properties
	 * Allowed properties = optional + required
	 * @var array
	 */
	protected $required = array();

	/**
	 * List of optional properties
	 * Allowed properties = optional + required
	 * @var array
	 */
	protected $optional = array();

	/**
	 * Classname of intepreter (see backend/lib/Interpreter/)
	 * @var string
	 */
	protected $interpreter;

	/**
	 * Not required for group entity
	 * @var string
	 */
	protected $unit;

	/**
	 * @todo url relative or absolute?
	 * @var string
	 */
	protected $icon;

	/**
	 * Check for required and optional properties
	 *
	 * @return boolean
	 */
	public function checkProperties() {

	}

}

?>
