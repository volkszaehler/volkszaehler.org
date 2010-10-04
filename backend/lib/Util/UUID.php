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
 * DrUUID RFC4122 library for PHP5
 *
 * @author J. King
 * @package util
 * @link http://jkingweb.ca/code/php/lib.uuid/
 * @license Licensed under MIT license
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */
class UUID {
	const MD5  = 3;
	const SHA1 = 5;
	const clearVer = 15;	// 00001111  Clears all bits of version byte with AND
	const clearVar = 63;	// 00111111  Clears all relevant bits of variant byte with AND
	const varRes   = 224;	// 11100000  Variant reserved for future use
	const varMS    = 192;	// 11000000  Microsft GUID variant
	const varRFC   = 128;	// 10000000  The RFC 4122 variant (this variant)
	const varNCS   = 0;		// 00000000  The NCS compatibility variant
	const version1 = 16;	// 00010000
	const version3 = 48;	// 00110000
	const version4 = 64;	// 01000000
	const version5 = 80;	// 01010000
	const interval = 0x01b21dd213814000;	// Time (in 100ns steps) between the start of the UTC and Unix epochs
	const nsDNS  = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
	const nsURL  = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';
	const nsOID  = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';
	const nsX500 = '6ba7b814-9dad-11d1-80b4-00c04fd430c8';

	//instance properties
	protected $bytes;
	protected $hex;
	protected $string;
	protected $urn;
	protected $version;
	protected $variant;
	protected $node;
	protected $time;

	/**
	 * Create a new UUID based on provided data
	 *
	 * @param integer $ver
	 * @param string $node
	 * @param string $ns
	 */
	public static function mint($ver = 1, $node = NULL, $ns = NULL) {
		switch((int) $ver) {
			case 1:
				return new self(self::mintTime($node));
			case 2:
				// Version 2 is not supported
				throw new \Exception('Version 2 is unsupported.');
			case 3:
				return new self(self::mintName(self::MD5, $node, $ns));
			case 4:
				return new self(self::mintRand());
			case 5:
				return new self(self::mintName(self::SHA1, $node, $ns));
			default:
				throw new \Exception('Selected version is invalid or unsupported.');
		}
	}

	/**
	 * Import an existing UUID
	 *
	 * @param unknown_type $uuid
	 */
	public static function import($uuid) {
		return new self(self::makeBin($uuid, 16));
	}

	/**
	 * Validation of UUID's
	 *
	 * @param string $uuid
	 */
	public static function validate($uuid) {
		return (boolean) preg_match('/^[0-9a-zA-Z]{8}-[0-9a-zA-Z]{4}-[0-9a-zA-Z]{4}-[0-9a-zA-Z]{4}-[0-9a-zA-Z]{12}$/', $uuid);
	}

	/**
	 * Compares the binary representations of two UUIDs.
	 * The comparison will return TRUE if they are bit-exact,
	 * or if neither is valid
	 *
	 * @param unknown_type $a
	 * @param unknown_type $b
	 */
	public static function compare($a, $b) {
		return (self::makeBin($a, 16) == self::makeBin($b, 16));
	}

	public function __toString() {
		return $this->string;
	}

	public function __get($var) {
		switch($var) {
			case 'bytes':
				return $this->bytes;
			case 'hex':
				return bin2hex($this->bytes);
			case 'string':
				return $this->__toString();
			case 'urn':
				return 'urn:uuid:' . $this->__toString();
			case 'version':
				return ord($this->bytes[6]) >> 4;
			case 'variant':
				$byte = ord($this->bytes[8]);
				if ($byte >= self::varRes) return 3;
				if ($byte >= self::varMS) return 2;
				if ($byte >= self::varRFC) return 1;
				else return 0;
			case 'node':
				if (ord($this->bytes[6])>>4==1) {
					return bin2hex(substr($this->bytes,10));
				}
				else {
					return NULL;
				}
			case 'time':
				if (ord($this->bytes[6])>>4==1) {
					// Restore contiguous big-endian byte order
					$time = bin2hex($this->bytes[6].$this->bytes[7].$this->bytes[4].$this->bytes[5].$this->bytes[0].$this->bytes[1].$this->bytes[2].$this->bytes[3]);
					// Clear version flag
					$time[0] = '0';
					// Do some reverse arithmetic to get a Unix timestamp
					$time = (hexdec($time) - self::interval) / 10000000;
					return $time;
				}
				else {
					return NULL;
				}
			default:
				return NULL;
		}
	}

	protected function __construct($uuid) {
		if (strlen($uuid) != 16) {
			throw new \Exception('Input must be a 128-bit integer.');
		}

		$this->bytes  = $uuid;

		// Optimize the most common use
		$this->string =
		bin2hex(substr($uuid,0,4)).'-'.
		bin2hex(substr($uuid,4,2)).'-'.
		bin2hex(substr($uuid,6,2)).'-'.
		bin2hex(substr($uuid,8,2)).'-'.
		bin2hex(substr($uuid,10,6));
	}

	/**
	 * Generates a Version 1 UUID.
	 * These are derived from the time at which they were generated.
	 *
	 * @param $node
	 */
	protected static function mintTime($node = NULL) {
		// Get time since Gregorian calendar reform in 100ns intervals
		// This is exceedingly difficult because of PHP's (and pack()'s)
		//  integer size limits.
		// Note that this will never be more accurate than to the microsecond.
		$time = microtime(TRUE) * 10000000 + self::interval;

		// Convert to a string representation
		$time = sprintf('%F', $time);
		preg_match('/^\d+/', $time, $time); //strip decimal point

		// And now to a 64-bit binary representation
		$time = base_convert($time[0], 10, 16);
		$time = pack('H*', str_pad($time, 16, '0', STR_PAD_LEFT));

		// Reorder bytes to their proper locations in the UUID
		$uuid  = $time[4].$time[5].$time[6].$time[7].$time[2].$time[3].$time[0].$time[1];

		// Generate a random clock sequence
		$uuid .= Random::getBytes(2);

		// set variant
		$uuid[8] = chr(ord($uuid[8]) & self::clearVar | self::varRFC);

		// set version
		$uuid[6] = chr(ord($uuid[6]) & self::clearVer | self::version1);

		// Set the final 'node' parameter, a MAC address
		if ($node) {
			$node = self::makeBin($node, 6);
		}
		else {
			// If no node was provided or if the node was invalid,
			//  generate a random MAC address and set the multicast bit
			$node = Random::getBytes(6);
			$node[0] = pack('C', ord($node[0]) | 1);
		}

		$uuid .= $node;
		return $uuid;
	}

	/**
	 * Generate a Version 4 UUID.
	 * These are derived soly from random numbers.
	 */
	protected static function mintRand() {
		// generate random fields
		$uuid = Random::getBytes(16);

		// set variant
		$uuid[8] = chr(ord($uuid[8]) & self::clearVar | self::varRFC);

		// set version
		$uuid[6] = chr(ord($uuid[6]) & self::clearVer | self::version4);

		return $uuid;
	}

	/**
	 * Generates a Version 3 or Version 5 UUID.
	 * These are derived from a hash of a name and its namespace, in binary form.
	 *
	 * @param integer $ver the version (MD5 or SHA1)
	 * @param string $node the name string
	 * $param string $ns the namespace
	 */
	protected static function mintName($ver, $node, $ns) {
		if (!$node) {
			throw new \Exception('A name-string is required for Version 3 or 5 UUIDs.');
		}

		// if the namespace UUID isn't binary, make it so
		$ns = self::makeBin($ns, 16);
		if (!$ns) {
			throw new \Exception('A binary namespace is required for Version 3 or 5 UUIDs.');
		}

		switch($ver) {
			case self::MD5:
				$version = self::version3;
				$uuid = md5($ns.$node,1);
				break;
			case self::SHA1:
				$version = self::version5;
				$uuid = substr(sha1($ns.$node,1),0, 16);
				break;
		}

		// set variant
		$uuid[8] = chr(ord($uuid[8]) & self::clearVar | self::varRFC);

		// set version
		$uuid[6] = chr(ord($uuid[6]) & self::clearVer | $version);

		return ($uuid);
	}

	/**
	 * Insure that an input string is either binary or hexadecimal.
	 * Returns binary representation, or FALSE on failure.
	 *
	 * @param unkown_type $str
	 * @param integer $len
	 */
	protected static function makeBin($str, $len) {
		if ($str instanceof self) {
			return $str->bytes;
		}

		if (strlen($str) == $len) {
			return $str;
		}
		else {
			$str = preg_replace('/^urn:uuid:/is', '', $str); // strip URN scheme and namespace
		}

		$str = preg_replace('/[^a-f0-9]/is', '', $str);  // strip non-hex characters

		if (strlen($str) != ($len * 2)) {
			return FALSE;
		}
		else {
			return pack('H*', $str);
		}
	}
}

?>

