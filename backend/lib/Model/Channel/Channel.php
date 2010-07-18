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

namespace Volkszaehler\Model\Channel;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Channel class
 *
 * @Entity
 * @Table(name="channels")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({
 * 		"meter" = "Meter",
 * 		"sensor" = "Sensor"
 * })
 */
abstract class Channel extends \Volkszaehler\Model\Entity {
	/** @Column(type="string") */
	protected $name;

	/** @Column(type="string") */
	protected $description;
	
	/** @Column(type="string") */
	protected $indicator;

	/**
	 * @OneToMany(targetEntity="Volkszaehler\Model\Data", mappedBy="channel"), cascade={"remove"}
	 */
	private $data = NULL;
	
	/*
	 * constructor
	 */
	public function __construct($indicator) {
		parent::__construct();
		
		$this->indicator = $indicator;
		$this->data = new ArrayCollection();
	}

	/*
	 * getter & setter
	 */
	public function getName() { return $this->name; }
	public function setName($name) { $this->name = $name; }
	public function getDescription() { return $this->description; }
	public function setDescription($description) { $this->description = $description; }
	public function getUnit() { return static::$indicators[$this->indicator]; }
	public function getIndicator() { return $this->indicator; }
	
	/*
	 * add a new data to the database
	 */
	public function addData(\Volkszaehler\Model\Data $data) {
		$this->data->add($data);
	}
	
	/*
	 * obtain channels data interpreter to calculate statistical information
	 */
	public function getInterpreter(\Doctrine\ORM\EntityManager $em) {
		$interpreterClassName = 'Volkszaehler\Interpreter\\' .  substr(strrchr(get_class($this), '\\'), 1);
		if (!(\Volkszaehler\Util\ClassLoader::classExists($interpreterClassName)) || !is_subclass_of($interpreterClassName, '\Volkszaehler\Interpreter\Interpreter')) {
			throw new \InvalidArgumentException('\'' . $interpreterClassName . '\' is not a valid Interpreter');
		}
		return new $interpreterClassName($this, $em);
	}
}