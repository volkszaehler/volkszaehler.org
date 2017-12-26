<?php
/**
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
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

/**
 * @author Steffen Vogel <info@steffenvogel.de>
 */
class PropertyDefinition extends Definition {
	/**
	 * File containing the JSON definitons
	 *
	 * @var string
	 */
	const FILE = 'PropertyDefinition.json';

	/**
	 * One of: string, integer, float, boolean, multiple, text
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Regex pattern to match if type == string
	 *
	 * @var string
	 */
	public $pattern;

	/**
	 * Minimal value if type == integer or type == float
	 * Required string length if type == string
	 *
	 * @var integer|float
	 */
	public $min;

	/**
	 * Maximal value if type == integer or type == float
	 * Allowed string length if type == string
	 *
	 * @var integer|float
	 */
	public $max;

	/**
	 * List of possible choices if type == multiple
	 * (type as in javascript: 1.2 => float, 5 => integer, true => boolean, "test" => string)
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * @var array holds definitions
	 */
	protected static $definitions = NULL;

	/**
	 * Validate value according to $this->type
	 *
	 * @param string|numeric $value
	 * @return boolean
	 */
	public function validateValue($value) {
		switch ($this->type) {
			case 'string':
			case 'text':
				$invalid = !is_string($value);
				$invalid |= isset($this->pattern) && !preg_match($this->pattern, $value);
				$invalid |= isset($this->min) && strlen($value) < $this->min;
				$invalid |= isset($this->max) && strlen($value) > $this->max;
				break;

			case 'integer':
				// $invalid = !is_int($value);
				$invalid = NULL === filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
				break;

			case 'float':
				// $invalid = !is_float($value);
				$invalid = NULL === filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
				break;

			case 'boolean':
				// $invalid = !is_bool($value);
				$invalid = NULL === filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
				break;

			case 'multiple':
				$invalid = !in_array($value, $this->options, TRUE);
				break;

			default:
				throw new \Exception('Unknown property type: \'' . $type . '\'');
		}

		if ($this->type == 'integer' || $this->type == 'float') {
			$invalid |= isset($this->min) && $value < $this->min;
			$invalid |= isset($this->max) && $value > $this->max;
		}

		return !$invalid;
	}

	/*
	 * Setter & getter
	 */
	public function getType() { return $this->type; }
}

?>
