<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package group
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
 *
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

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Group entity
 * 
 * @author Steffen Vogel <info@steffenvogel.de>
 * 
 * @Entity
 * @Table(name="groups")
 */
class Group extends Entity {
	/** @Column(type="string") */
	protected $name;
	
	/** @Column(type="string") */
	protected $description;
	
	/**
	 * @ManyToMany(targetEntity="Channel")
	 * @JoinTable(name="groups_channel",
	 * 		joinColumns={@JoinColumn(name="group_id", referencedColumnName="id")},
	 * 		inverseJoinColumns={@JoinColumn(name="channel_id", referencedColumnName="id")}
	 * )
	 */
	protected $channels = NULL;
	
	/**
	 * @ManyToMany(targetEntity="Group")
	 * @JoinTable(name="groups_groups",
	 * 		joinColumns={@JoinColumn(name="parent_id", referencedColumnName="id")},
	 * 		inverseJoinColumns={@JoinColumn(name="child_id", referencedColumnName="id")}
	 * )
	 */
	protected $children = NULL;

	/**
	 * construct
	 */
	public function __construct() {
		parent::__construct();
		
		$this->channels = new ArrayCollection();
		$this->children = new ArrayCollection();
	}
	
	/**
	 * getter & setter
	 */
	public function getName() { return $this->name; }
	public function setName($name) { $this->name = $name; }
	public function getDescription() { return $this->description; }
	public function setDescription($description) { $this->description = $description; }
}

?>