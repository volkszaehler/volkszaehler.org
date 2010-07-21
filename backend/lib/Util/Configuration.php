<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package util
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
 *
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
 * static configuration class for loading and storing the configuration to the disk
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 */
class Configuration {
	static protected $values = array();

	/**
	 * @param string $var A string delimited by dots
	 * @param mixed $value A scalar value or array which should be set as the value for $var
	 */
	static public function write($var, $value) {
		if (!is_scalar($value) && !is_array($value)) {
			throw new \Exception('sry we can\'t store this datatype in the configuration');
		}

		$values =& self::$values;
		$tree = explode('.', $var);
		foreach ($tree as $part) {
			$values =& $values[$part];	// TODO use array_merge_recursive()
		}

		$values = $value;
	}

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
			$values = $values[$part];
		}

		return $values;
	}

	/**
	 *
	 * @param string $var A string delimited by dots
	 */
	static public function delete($var) {
		$tree = explode('.', $var);

		$values =& self::$values;
		foreach ($tree as $part) {
			$values =& $values[$part];
		}

		unset($values);
	}

	/**
	 * loading configuration from fule
	 *
	 * @param string $filename A string pointing to a file on the filesystem
	 */
	static public function load($filename) {
		$filename .= '.php';

		if (!file_exists($filename)) {
			throw new \Exception('configuration file not found: ' . $filename);
		}

		include $filename;

		if (!isset($config)) {
			throw new \Exception('no variable $config found in ' . $filename);
		}

		self::$values = $config;
	}
	/**
	 *
	 * @param string $filename A string pointing to a file on the filesystem
	 * @return boolean TRUE on success
	 */
	static public function store($filename) {
		$filename .= '.php';

		$delcaration = '';
		foreach (self::$values as $key => $value) {
			$export = var_export($value, TRUE);
			$export = preg_replace('/=>\s+array/', '=> array', $export);
			$export = str_replace("  ", "\t", $export);

			$declaration .= '$config[\'' . $key . '\'] = ' . $export . ';' . PHP_EOL . PHP_EOL;
		}

		$content = <<<EOT
<?php

/**
 * That's the volkszaehler.org configuration file.
 * Please take care of the following rules:
 * - you are allowed to edit it by your own
 * - anything else than the \$config declaration
 *   will maybe be removed during the reconfiguration
 *   by the configuration parser!
 * - only literals are allowed as parameters
 * - expressions will be evaluated by the parser
 *   and saved as literals
 */

$declaration?>
EOT;
		return file_put_contents($filename, $content);
	}
}

?>