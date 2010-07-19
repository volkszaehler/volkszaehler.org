<?php
/*
 * Copyright (c) 2010 by Justin Otherguy <justin@justinotherguy.org>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License (either version 2 or
 * version 3) as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * For more information on the GPL, please go to:
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace Volkszaehler\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Grouping class
 * 
 * @Entity
 * @Table(name="groups")
 */
class Group extends Entity {
	/** @Column(type="string") */
	private $name;
	
	/** @Column(type="string") */
	private $description;
	
	/**
	 * @ManyToMany(targetEntity="Channel")
	 * @JoinTable(name="groups_channel",
	 * 		joinColumns={@JoinColumn(name="group_id", referencedColumnName="id")},
	 * 		inverseJoinColumns={@JoinColumn(name="channel_id", referencedColumnName="id")}
	 * )
	 */
	private $channels = NULL;
	
	/**
	 * @ManyToMany(targetEntity="Group")
	 * @JoinTable(name="groups_groups",
	 * 		joinColumns={@JoinColumn(name="parent_id", referencedColumnName="id")},
	 * 		inverseJoinColumns={@JoinColumn(name="child_id", referencedColumnName="id")}
	 * )
	 */
	private $children = NULL;

	/*
	 * construct
	 */
	public function __construct() {
		parent::__construct();
		
		$this->channels = new ArrayCollection();
		$this->children = new ArrayCollection();
	}
	
	/*
	 * getter & setter
	 */
	public function getName() { return $this->name; }
	public function setName($name) { $this->name = $name; }
	public function getDescription() { return $this->description; }
	public function setDescription($description) { $this->description = $description; }
}

?>