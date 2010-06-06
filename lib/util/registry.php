<?php

/**
 * Registry class to pass global variables between classes.
 */
abstract class Registry {
	/**
	 * Object registry provides storage for shared objects
	 *
	 * @var array
	 */
	private static $registry = array();

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
			throw new Exception('Unable to set variable `' . $key . '`. It was already set.');
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