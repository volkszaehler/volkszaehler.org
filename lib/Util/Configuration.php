<?php
/**
 * @copyright Copyright (c) 2011, The volkszaehler.org project
 * @package util
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

namespace Volkszaehler\Util;

/**
 * Static configuration class for loading and storing the configuration to the disk
 *
 * @package util
 * @author Steffen Vogel <info@steffenvogel.de>
 * @todo why not with json?
 */
class Configuration {
	static protected $values = array();

	/**
	 *
	 * @param string $var A string delimited by dots
	 * @return mixed the configuration value
	 */
	static public function read($var = NULL) {
		$tree = explode('.', $var);

		if (is_null($var)) {
			return self::$values;
		}

		$values = self::$values;
		foreach ($tree as $part) {
			if (isset($values[$part])) {
				$values = $values[$part];
			}
			else {
				return NULL;
			}
		}

		return $values;
	}

	/**
	 * loading configuration from fule
	 *
	 * @param string $filename A string pointing to a file on the filesystem
	 */
	static public function load($filename) {
		$filename .= '.php';

		if (!file_exists($filename)) {
			throw new \Exception('Configuration file not found: ' . $filename);
		}

		include $filename;

		if (!isset($config)) {
			throw new \Exception('No variable $config found in ' . $filename);
		}

		self::$values = $config;
	}
}

?>
