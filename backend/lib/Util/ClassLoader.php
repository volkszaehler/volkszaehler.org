<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package util
 * @author Steffen Vogel <info@steffenvogel.de>
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
 * class loader for volkszaehler and vendor libraries
 *
 * namespace is mapped to the filesystem structure
 *
 * @package util
 * @author Roman Borschel <roman@code-factory.org>
 * @license http://www.opensource.org/licenses/lgpl-license.php Lesser GNU Public License
 */
class ClassLoader {
	protected $fileExtension = '.php';
	protected $namespace;
	protected $includePath;
	protected $namespaceSeparator = '\\';

	/**
	 * Creates a new <tt>ClassLoader</tt> that loads classes of the
	 * specified namespace from the specified include path.
	 *
	 * If no include path is given, the ClassLoader relies on the PHP include_path.
	 * If neither a namespace nor an include path is given, the ClassLoader will
	 * be responsible for loading all classes, thereby relying on the PHP include_path.
	 *
	 * @param string $ns The namespace of the classes to load.
	 * @param string $includePath The base include path to use.
	 */
	public function __construct($ns = NULL, $includePath = NULL) {
		$this->namespace = $ns;
		$this->includePath = $includePath;
	}

	/**
	 * Sets the base include path for all class files in the namespace of this ClassLoader.
	 *
	 * @param string $includePath
	 */
	public function setIncludePath($includePath) {
		$this->includePath = $includePath;
	}

	/**
	 * Gets the base include path for all class files in the namespace of this ClassLoader.
	 *
	 * @return string
	 */
	public function getIncludePath() {
		return $this->includePath;
	}

	/**
	 * Sets the file extension of class files in the namespace of this ClassLoader.
	 *
	 * @param string $fileExtension
	 */
	public function setFileExtension($fileExtension) {
		$this->fileExtension = $fileExtension;
	}

	/**
	 * Gets the file extension of class files in the namespace of this ClassLoader.
	 *
	 * @return string
	 */
	public function getFileExtension() {
		return $this->fileExtension;
	}

	/**
	 * Registers this ClassLoader on the SPL autoload stack.
	 */
	public function register() {
		spl_autoload_register(array($this, 'loadClass'));
	}

	/**
	 * Removes this ClassLoader from the SPL autoload stack.
	 */
	public function unregister() {
		spl_autoload_unregister(array($this, 'loadClass'));
	}

	/**
	 * Loads the given class or interface.
	 *
	 * @param string $classname The name of the class to load.
	 * @return boolean TRUE if the class has been successfully loaded, FALSE otherwise.
	 */
	public function loadClass($className) {
		if ($this->namespace !== NULL && strpos($className, $this->namespace . $this->namespaceSeparator) !== 0) {
			return FALSE;
		}

		$subNamespace = substr($className, strlen($this->namespace));
		$parts = explode($this->namespaceSeparator, $subNamespace);
		$path = implode(DIRECTORY_SEPARATOR, $parts);

		require_once ($this->includePath !== NULL ? $this->includePath : '') . $path . $this->fileExtension;
		return TRUE;
	}

	/**
	 * Asks this ClassLoader whether it can potentially load the class (file) with
	 * the given name.
	 *
	 * @param string $className The fully-qualified name of the class.
	 * @return boolean TRUE if this ClassLoader can load the class, FALSE otherwise.
	 */
	public function canLoadClass($className) {
		if ($this->namespace !== NULL && strpos($className, $this->namespace . $this->namespaceSeparator) !== 0) {
			return FALSE;
		}

		$subNamespace = substr($className, strlen($this->namespace));
		$parts = explode($this->namespaceSeparator, $subNamespace);
		$path = implode(DIRECTORY_SEPARATOR, $parts);

		return file_exists(($this->includePath !== NULL ? $this->includePath . DIRECTORY_SEPARATOR : '') . $path . $this->fileExtension);
	}

	/**
	 * Checks whether a class with a given name exists. A class "exists" if it is either
	 * already defined in the current request or if there is an autoloader on the SPL
	 * autoload stack that is a) responsible for the class in question and b) is able to
	 * load a class file in which the class definition resides.
	 *
	 * If the class is not already defined, each autoloader in the SPL autoload stack
	 * is asked whether it is able to tell if the class exists. If the autoloader is
	 * a <tt>ClassLoader</tt>, {@link canLoadClass} is used, otherwise the autoload
	 * function of the autoloader is invoked and expected to return a value that
	 * evaluates to TRUE if the class (file) exists. As soon as one autoloader reports
	 * that the class exists, TRUE is returned.
	 *
	 * Note that, depending on what kinds of autoloaders are installed on the SPL
	 * autoload stack, the class (file) might already be loaded as a result of checking
	 * for its existence. This is not the case with a <tt>ClassLoader</tt>, who separates
	 * these responsibilities.
	 *
	 * @param string $className The fully-qualified name of the class.
	 * @return boolean TRUE if the class exists as per the definition given above, FALSE otherwise.
	 */
	public static function classExists($className) {
		if (class_exists($className, FALSE)) {
			return TRUE;
		}

		foreach (spl_autoload_functions() as $loader) {
			if (is_array($loader)) { // array(???, ???)
				if (is_object($loader[0])) {
					if ($loader[0] instanceof ClassLoader) { // array($obj, 'methodName')
						if ($loader[0]->canLoadClass($className)) {
							return TRUE;
						}
					} else if ($loader[0]->{$loader[1]}($className)) {
						return TRUE;
					}
				} else if ($loader[0]::$loader[1]($className)) { // array('ClassName', 'methodName')
					return TRUE;
				}
			} else if ($loader instanceof \Closure) { // function($className) {..}
				if ($loader($className)) {
					return TRUE;
				}
			} else if (is_string($loader) && $loader($className)) { // "MyClass::loadClass"
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Gets the <tt>ClassLoader</tt> from the SPL autoload stack that is responsible
	 * for (and is able to load) the class with the given name.
	 *
	 * @param string $className The name of the class.
	 * @return The <tt>ClassLoader</tt> for the class or NULL if no such <tt>ClassLoader</tt> exists.
	 */
	public static function getClassLoader($className) {
		foreach (spl_autoload_functions() as $loader) {
			if (is_array($loader) && $loader[0] instanceof ClassLoader &&
			$loader[0]->canLoadClass($className)) {
				return $loader[0];
			}
		}

		return NULL;
	}
}
?>
