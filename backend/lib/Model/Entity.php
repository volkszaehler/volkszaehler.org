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

	/** @Column(type="string", length=36, nullable=false) */
	protected $uuid;

	/**
	 * @OneToMany(targetEntity="Token", mappedBy="entity")
	 */
	protected $tokens = NULL;

	/**
	 * @OneToMany(targetEntity="Property", mappedBy="entity")
	 * @OrderBy({"key" = "ASC"})
	 */
	protected $properties = NULL;

	public function __construct() {
		$this->uuid = Util\UUID::mint();
		$this->tokens = new Collections\ArrayCollection();
		$this->properties = new Collections\ArrayCollection();
	}

	/**
	 *
	 * @param unknown_type $token
	 */
	public function validateToken($token) {

	}

	public function getToken() {

	}

	public function getProperty($name) {

	}

	public function setProperty($name) {

	}

	/**
	 * Getter & setter
	 */
	public function getId() { return $this->id; }		// read only
	public function getUuid() { return $this->uuid; }	// read only
}

?>
