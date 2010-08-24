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

use Volkszaehler\Util;
use Volkszaehler\Model;

/**
 * Property entity
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 *
 * @Entity
 * @Table(name="properties")
 */
class Property {
	/**
	 * @Id
	 * @Column(type="smallint", nullable=false)
	 * @GeneratedValue(strategy="AUTO")
	 *
	 * @todo wait until DDC-117 is fixed (PKs on FKs)
	 */
	protected $id;

	/** @Column(type="string", nullable=false) */
	protected $name;

	/** @Column(type="string", nullable=false) */
	protected $value;

	/** @ManyToOne(targetEntity="Entity", inversedBy="properties") */
	protected $entity;

	/**
	 * Property definition
	 *
	 * Used to validate
	 *
	 * @var Model\PropertyDefinition
	 */
	protected $definition;

	/**
	 * Constructor
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function __construct($name, $value) {
		$this->definition = Model\PropertyDefinition::get($name);

		$this->setName($name);
		$this->setValue($value);
	}

	/*
	 * Setter & Getter
	 */

	public function getName() { return $this->name; }
	public function getValue() { return $this->value; }

	public function setValue($value) {
		if (!$this->definition->validate($value)) {
			throw new \Exception('invalid property value'); $this->value = $value;
		}
	}

	/**
	 *
	 * @param string $name
	 * @todo validation
	 */
	protected function setName($name) { $this->name = $name; }
}

class PropertyDefinition extends Util\JSONDefinition {
	/** One of: string, numeric, multiple */
	public $type;

	/**
	 * Regex pattern to match if type == string
	 *
	 * @var string
	 */
	protected $pattern;

	/**
	 * Minimal value if type == numeric
	 * Required string length if type == string
	 *
	 * @var integer|float
	 */
	protected $min;

	/**
	 * Maximal value if type == numeric
	 * Allowed string length if type == string
	 *
	 * @var integer|float
	 */
	protected $max;

	/**
	 * List of possible choices if type == multiple
	 * (type as in javascript: 1.2 => numeric, "test" => string)
	 *
	 * @var array
	 */
	protected $choices = array();


	/**
	 * File containing the JSON definitons
	 *
	 * @var string
	 */
	const FILE = '/share/properties.json';

	/**
	 * Validate value according to $this->type
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function validate($value) {
		switch ($this->type) {
			case 'string':
				$invalid = !is_string($value);
				$invalid |= isset($this->pattern) && !preg_match($this->pattern, $value);
				$invalid |= isset($this->min) && strlen($value) < $this->min;
				$invalid |= isset($this->max) && strlen($value) > $this->max;
				break;

			case 'numeric':
				$invalid = !is_numeric($value);
				$invalid |= isset($this->min) && $value < $this->min;
				$invalid |= isset($this->max) && $value > $this->max;
				break;

			case 'multiple':
				$invalid = !in_array($value, $this->choices, TRUE);
				break;

			default:
				throw new \Exception('unknown property type');
		}

		return !$invalid;
	}
}

?>
