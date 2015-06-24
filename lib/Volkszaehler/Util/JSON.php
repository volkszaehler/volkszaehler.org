<?php
/**
 * @package util
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

namespace Volkszaehler\Util;

use Zend;

/**
 * Custom option constant for JSON::encode()
 */
define('JSON_PRETTY', 128);

/**
 * Static JSON utility class
 *
 * @package util
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Goetz <cpuidle@gmx.de>
 */
class JSON {

	/**
	 * OOP wrapper and factory
	 * @param string $json
	 * @return Util\JSON
	 */
	public static function decode($json, $assoc = FALSE, $depth = 512) {
		$data = json_decode(self::strip($json), $assoc, $depth);

		if (is_null($data)) {
			// allow DataController to try/catch empty requests
			throw new \RuntimeException();
		}

		return $data;
	}

	/**
	 * OOP wrapper
	 * @param integer $options use JSON_* constants
	 * @return string the JSON encoded string
	 */
	public static function encode($value, $options = 0) {
		// use Zend\Json\Encoder instead of Zend\Json\Json or toJson won't be called as arrays are short-circuited
		$json = Zend\Json\Encoder::encode($value);

		// remove encoded class names
		$json = preg_replace('/"__className":\s*".*?",?/', '', $json);

		if ($options & JSON_PRETTY) {
			$json = Zend\Json\Json::prettyPrint($json, array("indent" => "\t"));
		}

		return $json;
	}

	/**
	 * Strip whitespaces and comments from JSON string
	 *
	 * Nessecary for parsing a JSON string with json_decode()
	 *
	 * @param string $json
	 */
	protected static function strip($json) {
		$json = preg_replace(array(
			// eliminate single line comments in '// ...' form
			'#//(.+)$#m',

			// eliminate multi-line comments in '/* ... */' form
			'#/\*.*?\*/#s'
		), '', $json);

		// eliminate extraneous space
		return trim($json);
	}
}

?>
