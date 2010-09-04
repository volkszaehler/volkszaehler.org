<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
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
 * Extensible PRNG
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package util
 */
class Random {
	protected static $func = 'twister';
	protected static $source = NULL;

	/**
	 * Initialize the PRNG
	 *
	 * Look for a system-provided source of randomness, which is usually crytographically secure.
	 * /dev/urandom is tried first simply out of bias for Linux systems.
	 */
	public static function init() {
		if (is_readable('/dev/urandom')) {
			self::$source = fopen('/dev/urandom', 'rb');
			self::$func = 'fRead';
		}
		else if (class_exists('COM', 0)) {
			try {
				self::$source = new COM('CAPICOM.Utilities.1');  // See http://msdn.microsoft.com/en-us/library/aa388182(VS.85).aspx
				self::$func = 'COM';
			}
			catch(\Exception $e) {}
		}
		return self::$func;
	}

	/**
	 *
	 * @param intger $bytes
	 * @todo check if initialized
	 */
	public static function getBytes($count) {
		return call_user_func(array('self', self::$func), $count);
	}

	/**
	 *
	 * @param array $chars charset for random string
	 * @param integer $count length of string
	 */
	public function getString(array $chars, $length) {
		$numbers = self::get(0, count($chars) - 1, $length);
		$string = '';

		foreach ($numbers as $number) {
			$string .= $chars[$number];
		}

		return $string;
	}

	/**
	 * Generate $count random numbers between $min and $max
	 *
	 * @param integer $min
	 * @param integer $max
	 * @param integer $count
	 * @return integer|array single integer if $count == 1 or array of integers if $count > 1
	 */
	public function get($min, $max, $count = 1) {
		$bytes = self::getBytes($count);

		$numbers = array();

		for ($i = 0; $i < $count; $i++) {
			$numbers[] = ord($bytes[$i]) % ($max - $min + 1) + $min;
		}

		return ($count == 1) ? $numbers[0] : $numbers;
	}

	/*
	 * Get the specified number of random bytes, using mt_rand().
	 * Randomness is returned as a string of bytes.
	 */
	protected static function twister($count) {
		$rand = '';
		for ($a = 0; $a < $count; $a++) {
			$rand .= chr(mt_rand(0, 255));
		}
		return $rand;
	}

	/**
	 * Get the specified number of random bytes using a file handle
	 * previously opened with Random::init().
	 * Randomness is returned as a string of bytes.
	 */
	protected static function fRead($count) {
		return fread(self::$source, $count);
	}

	/*
	 * Get the specified number of random bytes using Windows'
	 * randomness source via a COM object previously created by Random::init().
	 * Randomness is returned as a string of bytes.
	 */
	protected static function COM($count) {
		return base64_decode(self::$source->GetRandom($count, 0)); // straight binary mysteriously doesn't work, hence the base64
	}
}

?>