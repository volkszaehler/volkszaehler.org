<?php
/**
 * Console parameter handling
 *
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2013, The volkszaehler.org project
 * @package util
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
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

class Console {

	protected $parameters;

	protected $options;
	protected $commands;

	/**
	 * @param array $options Associative short/long options array in getopt syntax
	 */
	public function __construct($parameters) {
		$this->parameters = $parameters;
		$this->options = getopt(join('', array_keys($parameters)), array_values($parameters));

		$this->commands = array();
		$argv = $GLOBALS['argv'];

		for ($i=1; $i<count($argv); $i++) {
			$arg = $argv[$i];
			if (preg_match('#^[-/]+(.+)#', $arg, $m)) {
				// skip argv if option is set
				if (in_array($m[1], array_keys($this->options))) {
					$i++;
				}
				continue;
			}
			$this->commands[] = $arg;
		}
	}

	/**
	 * Get cmd line commands
	 * @param  string $command return true of command is defined
	 * @return mixed           true/false if command specified or array of defined commands
	 */
	public function getCommand($command = null) {
		if (isset($command)) {
			return in_array($command, $this->commands);
		}
		else {
			return $this->commands;
		}
	}

	/**
	 * Get short or long options value
	 * @param  string $parameter short or long parameter name
	 * @return array         	 options, FALSE if not set
	 */
	function getMultiOption($parameter, $default = array()) {
		$candidates = array($parameter);

		// add matching short/long options to candidates
		if (in_array($parameter, array_keys($this->parameters))) {
			$candidates[] = $this->parameters[$parameter];
		}
		elseif ($key = array_search($parameter, $this->parameters)) {
			$candidates[] = $key;
		}

		$val = array();

		// find option values
		foreach ($candidates as $candidate) {
			if (isset($this->options[$candidate])) {
				$optVal = $this->options[$candidate];
 				// use true to indicate option is set
				if ($optVal == false) $optVal = true;
				if (is_array($optVal))
					$val = array_merge($val, $optVal);
				else
					$val[] = $optVal;
			}
		}

		return count($val) ? $val : $default;
	}

	/**
	 * Get short or long options value
	 * @param  string $parameter short or long parameter name
	 * @return mixed         	 option value, true if set
	 */
	function getSimpleOption($parameter, $default = null) {
		// make sure default is passed as array
		$default = is_array($default) ? $default : array($default);
		$res = $this->getMultiOption($parameter, $default);
		return $res[0];
	}

	/**
	 * Check if script is run from console
	 */
	public static function isConsole() {
		return php_sapi_name() == 'cli' || (isset($_SERVER['SESSIONNAME']) && $_SERVER['SESSIONNAME'] == 'Console');
	}
}

?>
