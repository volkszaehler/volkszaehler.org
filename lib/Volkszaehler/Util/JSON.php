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

/**
 * Custom option constant for JSON::encode()
 */
define('JSON_PRETTY', 128);

/**
 * Static JSON utility class
 *
 * @package util
 * @author Steffen Vogel <info@steffenvogel.de>
 */
class JSON extends \ArrayObject {

	/**
	 * OOP wrapper and factory
	 * @param string $json
	 * @return Util\JSON
	 */
	static public function decode($json, $assoc = FALSE, $depth = 512) {
		$data = json_decode(self::strip($json), $assoc, $depth);

		if (is_null($data)) {
			throw new JSONException();
		}

		return new self($data);
	}

	/**
	 * OOP wrapper
	 * @param integer $options use JSON_* constants
	 * @return string the JSON encoded string
	 */
	public function encode($options = 0) {
		$json = json_encode($this->getArrayCopy(), $options);

		if ($options & JSON_PRETTY) {
			$json = self::format($json);
		}

		return $json;
	}

	/**
	 * Cast to string
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->encode();
	}

	/**
	 * Formats JSON with indents and new lines
	 *
	 * @param string $json
	 * @param string $indent
	 * @param string $newLine
	 * @return string the formatted JSON
	 */
	protected static function format($json, $indent = "\t", $newLine = "\n") {
		$formatted = '';
		$indentLevel = 0;
		$inString = FALSE;

		$len = strlen($json);
		for($c = 0; $c < $len; $c++) {
			$char = $json[$c];
			switch($char) {
				case '{':
				case '[':
					$formatted .= $char;
					if (!$inString && (ord($json[$c+1]) != ord($char)+2)) {
						$indentLevel++;
						$formatted .= $newLine . str_repeat($indent, $indentLevel);
					}
					break;
				case '}':
				case ']':
					if (!$inString && (ord($json[$c-1]) != ord($char)-2)) {
						$indentLevel--;
						$formatted .= $newLine . str_repeat($indent, $indentLevel);
					}
					$formatted .= $char;
					break;
				case ',':
					$formatted .= $char;
					if (!$inString) {
						$formatted .= $newLine . str_repeat($indent, $indentLevel);
					}
					break;
				case ':':
					$formatted .= $char;
					if (!$inString) {
						$formatted .= ' ';
					}
					break;
				case '"':
					if ($c > 0 && $json[$c-1] != '\\') {
						$inString = !$inString;
					}
				default:
					$formatted .= $char;
					break;
			}
		}

		return $formatted;
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

class JSONException extends \Exception {
	/**
	 * @var array errorcodes defined by json_last_error()
	 * @link http://www.php.net/manual/en/json.constants.php
	 */
	protected static $errors = array(
		JSON_ERROR_NONE => 'No error has occurred',
		JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
		JSON_ERROR_CTRL_CHAR => 'Control character error',
		JSON_ERROR_SYNTAX => 'Syntax error',
		JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON'
		// JSON_ERROR_UTF8 => 'Malformed UTF-8 characters' // INFO this constant exists since PHP 5.3.3
	);

	public function __construct($message = NULL, $code = 0) {
		parent::__construct((is_null($message)) ? self::$errors[json_last_error()] : $message, $code);
	}
}

?>
