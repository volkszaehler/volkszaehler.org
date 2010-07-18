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
 * User class
 * 
 * @Entity
 * @Table(name="users")
 */
class User extends Entity {
	/** @Column(type="string") */
	private $email;
	
	/** @Column(type="string") */
	private $password;
	
	/**
	 * @ManyToMany(targetEntity="Group")
	 * @JoinTable(name="groups_users",
	 * 		joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
	 * 		inverseJoinColumns={@JoinColumn(name="group_id", referencedColumnName="id")}
	 * )
	 */
	private $groups = NULL;
	
	/*
	 * constructor
	 */
	public function __construct() {
		parent::__construct();
		
		$this->groups  = new ArrayCollection();
	}
	
	/*
	 * getter & setter
	 */
	public function getEmail() { return $this->email; }
	public function setEmail($email) { $this->email = $email; }
	public function setPassword($password) { $this->password = sha1($password); }

	/*
	 * check hashed password against cleartext
	 */
	public function checkPassword($password) {
		return (sha1($password) === $this->password);
	}
}

?>