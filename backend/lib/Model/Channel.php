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

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Channel entity
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 *
 * @Entity
 * @Table(name="channels")
 */
class Channel extends Entity {
	/** @Column(type="string", nullable=false) */
	protected $name;

	/** @Column(type="string", nullable=true) */
	protected $description;

	/** @Column(type="string", nullable=false) */
	protected $indicator;

	/**
	 * @OneToMany(targetEntity="Data", mappedBy="channel"), cascade={"remove"}
	 */
	protected $data = NULL;

	/** @Column(type="integer", nullable=true) */
	protected $resolution;

	/** @Column(type="decimal", precision="5", scale="2", nullable=true) */
	protected $cost;

	/**
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

	/**
	 * constructor
	 */
	public function __construct($indicator) {
		parent::__construct();

		if (!in_array($indicator, self::$indicators)) {
			throw new \Exception($indicator . ' is no known indicator');
		}

		$this->indicator = $indicator;
		$this->data = new ArrayCollection();
	}

	/**
	 * add a new data to the database
	 * @todo move to Logger\Logger?
	 */
	public function addData(\Volkszaehler\Model\Data $data) {
		$this->data->add($data);
	}

	/**
	 * obtain channels data interpreter to calculate statistical information
	 */
	public function getInterpreter(\Doctrine\ORM\EntityManager $em) {
		$interpreterClassName = 'Volkszaehler\Interpreter\\' . ucfirst(self::$indicators[$this->indicator][0]) . 'Interpreter';
		if (!(\Volkszaehler\Util\ClassLoader::classExists($interpreterClassName)) || !is_subclass_of($interpreterClassName, '\Volkszaehler\Interpreter\Interpreter')) {
			throw new \Exception('\'' . $interpreterClassName . '\' is not a valid Interpreter');
		}
		return new $interpreterClassName($this, $em);
	}

	/**
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

?>
