<?php
/**
 * @copyright Copyright (c) 2011, The volkszaehler.org project
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

use Doctrine\ORM;
use Doctrine\DBAL\Logging;

/**
 * Static debugging class
 *
 * @package util
 * @author Steffen Vogel <info@steffenvogel.de>
 */
class Debug {
	protected static $instance = NULL;

	protected $messages = array();
	protected $sqlLogger;

	/** @var float holds timestamp of initialization, used later to return time of execution */
	protected $started;

	/** * @var integer the debug level */
	protected $level;

	/**
	 * Constructor
	 *
	 * @param integer $level the debug level
	 */
	public function __construct($level, ORM\EntityManager $em) {
		// taking timestamp to stop execution time
		$this->created = microtime(TRUE);

		// saving debug level
		$this->level = $level;

		// starting logging of sql queries
		$this->em = $em;
		$this->attachSqlLogger(new Logging\DebugStack());

		if (isset(self::$instance)) {
			throw new \Exception('Debugging has already been started. Please use the static functions!');
		}
		self::$instance = $this;
	}

	/**
	 * Log messages to the debug stack including file, lineno, args and a stacktrace
	 *
	 * @param string $message
	 * @param more parameters could be passed
	 */
	static public function log($message) {
		if (isset(self::$instance)) {
			$trace = debug_backtrace(FALSE);
			$info = $trace[0];
			$level = self::$instance->level;

			$message = array('message' => $message);

			if ($level > 2) {
				$message['file'] = $info['file'];
				$message['line'] = $info['line'];
			}

			if ($level > 4) {
				$message['args'] = array_slice($info['args'], 1);
			}

			if ($level > 5) {
				$message['trace'] = array_slice($trace, 1);
			}

			self::$instance->messages[] = $message;
		}
	}

	/**
	 * Is debugging enabled?
	 * @return boolean
	 */
	public static function isActivated() { return isset(self::$instance); }

	/**
	 * Deactivate debugging (for http server)
	 * @return boolean
	 */
	public static function deactivate() {
		if (self::$instance) {
			self::$instance->attachSqlLogger(null);
			self::$instance = null;
		}
	}

	/**
	 * Set SQL logger on entity manager
	 */
	protected function attachSqlLogger($sqlLogger) {
		$this->sqlLogger = $sqlLogger;
		$this->em->getConnection()->getConfiguration()->setSQLLogger($sqlLogger);
	}

	/**
	 * @return float execution time
	 */
	public function getExecutionTime() { return round((microtime(TRUE) - $this->created), 5); }

	/**
	 * @return 2 dimensional array with sql queries and parameters
	 */
	public function getQueries() { return $this->sqlLogger->queries; }

	/**
	 * getParametrizedQuery helper function
	 */
	private static function formatSQLParameter($para) {
		if (is_numeric($para)) {
			return $para;
		}
		elseif (is_string($para)) {
			return (strtoupper($para) == 'NULL') ?: "'" . $para . "'";
		}
		return $para;
	}

	/**
	 * @return format SQL string with parameters
	 * @author Andreas Goetz <cpuidle@gmx.de>
	 */
	public static function getParametrizedQuery($sql, $sqlParameters) {
		while (count($sqlParameters)) {
			$sql = preg_replace('/\?/', self::formatSQLParameter(array_shift($sqlParameters)), $sql, 1);
		}
		return $sql;
	}

	/**
	 * @return 2 dimensional array with messages
	 */
	public function getMessages() { return $this->messages; }

	/**
	 * @return Debug the Debug instance if available
	 * @todo encapsulate in state class? or inherit from singleton class?
	 */
	public static function getInstance() { return self::$instance; }

	/**
	 * @return integer current debug level
	 */
	 public function getLevel() { return $this->level; }

	/**
	 * Tries to determine the current SHA1 hash of your git commit
	 *
	 * @return string the hash
	 */
	public static function getCurrentCommit() {
		if (false && file_exists(VZ_DIR . '/.git/HEAD')) {
			$head = file_get_contents(VZ_DIR . '/.git/HEAD');
			return substr(file_get_contents(VZ_DIR . '/.git/' . substr($head, strpos($head, ' ')+1, -1)), 0, -1);
		}
		elseif (function_exists("shell_exec")) {
			return shell_exec('git show --pretty=format:%H --quiet');
		}
		else {
			return FALSE;
		}
	}

	public static function getPhpVersion() {
		return phpversion();
	}

	/**
	 * Get average server load
	 *
	 * @return array average load (1min, 5min, 15min)
	 */
	public static function getLoadAvg() {
		if (function_exists('sys_getloadvg')) {
			$load = sys_getloadvg();
		}
		elseif (file_exists('/proc/loadavg')) {
			$load = file_get_contents('/proc/loadavg');
			$load = array_slice(explode(' ', $load), 0, 3);
		}
		elseif (function_exists('shell_exec')) {
			// fail-safe shell exec
			$null = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'nul' : '/dev/null';
			$load = explode(', ', substr(shell_exec('uptime 2>' . $null), -16));
		}

		return (isset($load)) ? array_map('floatval', $load) : FALSE;
	}

	/**
	 * Get server uptime
	 *
	 * @return integer server uptime in seconds
	 */
	public static function getUptime() {
		if (file_exists("/proc/uptime")) {
			$uptime = explode(' ', file_get_contents("/proc/uptime"));
			return (float) $uptime[0];
		}
		elseif (function_exists("shell_exec")) {
			$matches = array();
			// fail-safe shell exec
			$null = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'nul' : '/dev/null';
			if (preg_match("/up (?:(?P<days>\d+) days?,? )?(?P<hours>\d+):(?P<minutes>\d{2})/", shell_exec('uptime 2>' . $null), $matches)) {
				$uptime = 60*$matches['hours'] + $matches['minutes'];

				if (isset($matches['days'])) {
					$uptime += $matches['days']*60*24;
				}

				return $uptime*60; // minutes => seconds
			}
		}
		return FALSE;
	}
}

?>
