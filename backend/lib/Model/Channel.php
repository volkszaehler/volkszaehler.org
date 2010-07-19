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
 * Channel class
 *
 * @Entity
 * @Table(name="channels")
 */
abstract class Channel extends Entity {
	/** @Column(type="string") */
	protected $name;

	/** @Column(type="string") */
	protected $description;
	
	/** @Column(type="string") */
	protected $indicator;

	/**
	 * @OneToMany(targetEntity="Data", mappedBy="channel"), cascade={"remove"}
	 */
	private $data = NULL;
	
	/** @Column(type="integer") */
	private $resolution;

	/** @Column(type="decimal") */
	private $cost;
	
	/*
	 * indicator => interpreter, unit mapping
	 */
	protected static $indicators = array(
		'power' =>			array('meter', 'kW/h'),
		'gas' =>			array('meter', 'qm/h'),
		'water' =>			array('meter', 'qm/h'),
		'temperature' =>	array('sensor', '° C'),
		'pressure' =>		array('sensor', 'hPa'),
		'humidity' =>		array('sensor', '%')
	);
	
	/*
	 * constructor
	 */
	public function __construct($indicator) {
		parent::__construct();
		
		if (!in_array($indicator, self::$indicators)) {
			throw new \InvalidArgumentException($indicator . ' is no known indicator');
		}
		
		$this->indicator = $indicator;
		$this->data = new ArrayCollection();
	}
	
	/*
	 * add a new data to the database
	 * @todo move to logger?
	 */
	public function addData(\Volkszaehler\Model\Data $data) {
		$this->data->add($data);
	}
	
	/*
	 * obtain channels data interpreter to calculate statistical information
	 */
	public function getInterpreter(\Doctrine\ORM\EntityManager $em) {
		$interpreterClassName = 'Volkszaehler\Interpreter\\' . ucfirst(self::$indicators[$this->indicator][0]);
		if (!(\Volkszaehler\Util\ClassLoader::classExists($interpreterClassName)) || !is_subclass_of($interpreterClassName, '\Volkszaehler\Interpreter\Interpreter')) {
			throw new \InvalidArgumentException('\'' . $interpreterClassName . '\' is not a valid Interpreter');
		}
		return new $interpreterClassName($this, $em);
	}
	
	/*
	 * getter & setter
	 */
	public function getName() { return $this->name; }
	public function setName($name) { $this->name = $name; }
	public function getDescription() { return $this->description; }
	public function setDescription($description) { $this->description = $description; }
	public function getUnit() { return self::$indicators[$this->indicator][1]; }
	public function getIndicator() { return $this->indicator; }
	public function getResolution() { return $this->resolution; }
	public function setResolution($resolution) { $this->resolution = $resolution; }
	public function getCost() { return $this->cost; }
	public function setCost($cost) { $this->cost = $cost; }
}