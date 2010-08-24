<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package util
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

namespace Volkszaehler\Util;

/**
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package util
 */
abstract class JSONDefinition {
	/**
	 * Cached json definitions
	 *
	 * @var array
	 */
	protected static $definitions = NULL;

	/** Discriminator for database column */
	protected $name;

	/**
	 * Hide default constructor
	 *
	 * @param array $name
	 */
	protected function __construct($object) {
		foreach (get_object_vars($object) as $name => $value) {
			if (property_exists(get_class($this), $name)) {
				$this->$name = $value;
			}
			else {
				throw new \Exception('unknown definition: ' . $name);
			}
		}
	}


	/**
	 * Factory method for creating new instances
	 *
	 * @param string $name
	 * @return Model\PropertyDefinition
	 */
	public static function get($name) {
		if (is_null(self::$definitions)) {
			self::load();
		}

		if (!isset(self::$definitions[$name])) {
			throw new \Exception('unknown definition');
		}

		return self::$definitions[$name];
	}

	/**
	 * Load JSON definitions from file (via lazy loading from get())
	 */
	protected static function load() {
		$json = file_get_contents(VZ_DIR . static::FILE);
		$json = JSON::strip($json);
		$json = json_decode($json);	// TODO move to Util\JSON class

		if (!is_array($json) || count($json) == 0) {
			throw new \Exception('syntax error in definition');
		}

		self::$definitions = array();

		foreach ($json as $property) {
			self::$definitions[$property->name] = new static($property);
		}
	}
}

?>