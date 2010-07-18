<?php
/*
 * Copyright (c) 2010 by Justin Otherguy <justin@justinotherguy.org>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License (either version 2 or
 * version 3) as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * For more information on the GPL, please go to:
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace Volkszaehler\Util;

/**
 * Registry class to pass global variables between classes.
 */
abstract class Registry {
	/**
	 * Object registry provides storage for shared objects
	 *
	 * @var array
	 */
	protected static $registry = array();

	/**
	 * Adds a new variable to the Registry.
	 *
	 * @param string $key Name of the variable
	 * @param mixed $value Value of the variable
	 * @throws Exception
	 * @return bool
	 */
	public static function set($key, $value) {
		if (!isset(self::$registry[$key])) {
			self::$registry[$key] = $value;
			return true;
		} else {
			throw new \Exception('Unable to set variable `' . $key . '`. It was already set.');
		}
	}

	/**
	 * Returns the value of the specified $key in the Registry.
	 *
	 * @param string $key Name of the variable
	 * @return mixed Value of the specified $key
	 */
	public static function get($key)
	{
		if (isset(self::$registry[$key])) {
			return self::$registry[$key];
		}
		return null;
	}

	/**
	 * Returns the whole Registry as an array.
	 *
	 * @return array Whole Registry
	 */
	public static function getAll()
	{
		return self::$registry;
	}

	/**
	 * Removes a variable from the Registry.
	 *
	 * @param string $key Name of the variable
	 * @return bool
	 */
	public static function remove($key)
	{
		if (isset(self::$registry[$key])) {
			unset(self::$registry[$key]);
			return true;
		}
		return false;
	}

	/**
	 * Removes all variables from the Registry.
	 *
	 * @return void
	 */
	public static function removeAll()
	{
		self::$registry = array();
		return;
	}
}

?>