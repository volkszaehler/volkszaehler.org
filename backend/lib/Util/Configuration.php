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

class Configuration {
	static protected $values = array();

	static public function write($var, $value) {
		if (!is_scalar($value) && !is_array($value)) {
			throw new \Exception('sry we can\'t store this datatype in the configuration');
		}
		
		$values =& self::$values;
		$tree = explode('.', $var);
		foreach ($tree as $part) {
			$values =& $values[$part];	// TODO array_merge_recursive()
		}
		
		$values = $value;
	}

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

	static public function delete($var) {

	}

	/*
	 * configuration file handling
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

	static public function store($filename) {
		$filename .= '.php';
		
		$delcaration = '';
		foreach (self::$values as $key => $value) {
			$export = var_export($value, true);
			$export = preg_replace('/=>\s+array/', '=> array', $export);
			$export = str_replace("  ", "\t", $export);
			
			$declaration .= '$config[\'' . $key . '\'] = ' . $export . ';' . PHP_EOL . PHP_EOL; 
		}
		
		$content = <<<EOT
<?php

/*
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