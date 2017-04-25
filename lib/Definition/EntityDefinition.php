<?php
/**
 * @package default
 * @copyright Copyright (c) 2011, The volkszaehler.org project
 * @license http://www.gnu.org/licenses/gpl.txt GNU Public License
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

namespace Volkszaehler\Definition;

use Volkszaehler\Util;
use Volkszaehler\Model\Aggregator;

/**
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @package default
 */
class EntityDefinition extends Definition {
	/**
	 * File containing the JSON definitons
	 *
	 * @var string
	 */
	const FILE = 'EntityDefinition.json';

	/**
	 * List of required properties
	 *
	 * @var array
	 */
	public $required = array();

	/**
	 * List of optional properties
	 *
	 * @var array
	 */
	public $optional = array();

	/**
	 * Classname of intepreter (see Volkszaehler\Interpreter)
	 *
	 * @var string
	 */
	public $interpreter;

	/**
	 * Style for plotting
	 *
	 * @var string (lines|points|steps)
	 */
	 public $style;

	/**
	 * Classname of model (see Volkszaehler\Model)
	 *
	 * @var string
	 */
	public $model;

	/**
	 * Optional for Aggregator class entities
	 *
	 * @var string
	 */
	public $unit;

	/**
	 * Relative url to an icon
	 * @var string
	 */
	public $icon;

	/**
	 * Indicates if a consumption value can be calculated
 	 * @var boolean
	 */
	public $hasConsumption = FALSE;

	/**
	 * Scaler for unit values
	 * E.g. $scale = 1000 means entity definition is in impulses per 1000 units (same for initialconsumption and cost)
 	 * @var boolean
	 */
	public $scale = 1;

	/**
	 * @var array holds definitions
	 */
	protected static $definitions = NULL;

	/**
	 * Properties required/optional by default for all Entity types
	 * @var array
	 */
	static protected $defaultRequired = array('title');
	static protected $defaultOptional = array('public', 'color', 'style', 'fillstyle', 'yaxis', 'active', 'description', 'owner', 'address:', 'link');
	static protected $groupOptional = array('public', 'color', 'active', 'description', 'owner', 'address:', 'link');

	/**
	 * Constructor
	 *
	 * Adding default properties
	 */
	 protected function __construct($object) {
	 	parent::__construct($object);

	 	$this->required = array_merge(self::$defaultRequired, $this->required);

	 	$optional = $this->getModel() == Aggregator::class
	 		? self::$groupOptional
	 		: self::$defaultOptional;
		$this->optional = array_merge($optional, $this->optional);
	 }

	/*
	 * Setter & Getter
	 */
	public function getInterpreter() { return $this->interpreter; }
	public function getModel() { return $this->model; }
	public function getUnit() { return $this->unit; }
	public function getRequiredProperties() { return $this->required; }
	public function getValidProperties() { return array_merge($this->required, $this->optional); }
}

?>
