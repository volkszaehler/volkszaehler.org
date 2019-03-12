<?php
/**
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
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

/**
 * @author Steffen Vogel <info@steffenvogel.de>
 */
abstract class Definition {
	/**
	 * Cache key
	 */
	const CACHE_KEY = 'VZ_';

	/**
	 * @var string discriminator for database column
	 */
	public $name;

	/**
	 * @var string title for UI
	 */
	public $translation;

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
				throw new \Exception('Unknown definition syntax: \'' . $name . '\'');
			}
		}
	}

	/**
	 * Factory method for creating new instances
	 *
	 * @param string $name
	 * @return Definition|array
	 */
	public static function get($name = NULL) {
		if (is_null(static::$definitions)) {
			static::load();
		}

		if (is_null($name)) {
			return array_values(static::$definitions);
		}
		elseif (static::exists($name)) {
			return static::$definitions[$name];
		}
		else {
			throw new \Exception('Unknown definition: \'' . $name . '\'');
		}
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
	 *
	 * @todo add caching
	 */
	protected static function load() {
		static::$definitions = array();

		$cache_id = static::CACHE_KEY . static::FILE;

		if (Util\Configuration::read('devmode') == FALSE && extension_loaded('apcu') && apcu_exists($cache_id) &&
			(time() - filemtime(__DIR__ . '/' . static::FILE) > Util\Configuration::read('cache.ttl', 3600)))
		{
			static::$definitions = apcu_fetch($cache_id);
		}
		else {
			// expensive - cache results
			$json = Util\JSON::decode(file_get_contents(__DIR__ . '/' . static::FILE));

			foreach ($json as $property) {
				static::$definitions[$property->name] = new static($property);
			}

			if (extension_loaded('apcu')) {
				apcu_store($cache_id, static::$definitions, Util\Configuration::read('cache.ttl', 3600));
			}
		}
	}

	/*
	 * Setter & Getter
	 */
	public function getName() { return $this->name; }
	public function getTranslation($language) { return $this->translation[$language]; }
}

?>
