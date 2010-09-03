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
abstract class Definition {
	/** @var string discriminator for database column */
	protected $name;

	/** @var string title for UI */
	protected $title;

	/** @var string description for UI */
	protected $description;

	/**
	 * Hide default constructor
	 *
	 * @param array $object to cast from
	 */
	protected function __construct($object) {
		foreach (get_object_vars($object) as $name => $value) {
			if (property_exists(get_class($this), $name)) {
				$this->$name = $value;
			}
			else {
				throw new \Exception('unknown definition syntax: ' . $name);
			}
		}
	}


	/**
	 * Factory method for creating new instances
	 *
	 * @param string $name
	 * @return Util\Definition
	 */
	public static function get($name) {
		if (!static::exists($name)) {
			throw new \Exception('unknown definition');
		}

		return static::$definitions[$name];
	}

	/**
	 * Checks if $name is defined
	 * @param string $name
	 */
	public static function exists($name) {
		if (is_null(static::$definitions)) {
			static::load();
		}

		return isset(static::$definitions[$name]);
	}

	/**
	 * Load JSON definitions from file (via lazy loading from get())
	 */
	protected static function load() {
		$json = JSON::decode(file_get_contents(VZ_DIR . static::FILE));

		static::$definitions = array();

		foreach ($json as $property) {
			static::$definitions[$property->name] = new static($property);
		}
	}
}

?>