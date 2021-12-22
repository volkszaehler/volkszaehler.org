<?php
/**
 * @copyright Copyright (c) 2011-2020, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
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
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Goetz <cpuidle@gmx.de>
 */
class Debug {
    /**
     * @var Debug|null
     */
	protected static $instance = NULL;

	/**
	 * @var ORM\EntityManager
	 */
	protected $em;

	/**
	 * @var mixed[] Array of logged messages
	 */
	protected $messages;

	/**
	 * @var Logging\SQLLogger
	 */
	protected $sqlLogger;

	/**
	 * @var float timestamp of initialization, used to calculate execution time
	 */
	protected $created;

	/**
	 * Constructor
	 */
	protected function __construct(ORM\EntityManager $em) {
		$this->messages = array();

		// take timestamp to stop execution time
		$this->created = microtime(TRUE);

		// starting logging of sql queries
		$this->em = $em;
		$this->attachSqlLogger(new Logging\DebugStack());
	}

	/**
	 * Log messages to the debug stack including file, lineno, args and a stacktrace
	 *
	 * @param string $message
	 */
	static public function log($message) {
		if (isset(self::$instance)) {
			$trace = debug_backtrace(FALSE);
			$info = $trace[0];

			self::$instance->messages[] = array(
				'message' => $message,
				'file' => $info['file'],
				'line' => $info['line']
			);
		}
	}

	/**
	 * Is debugging enabled?
	 * @return boolean
	 */
	public static function isActivated() {
		return isset(self::$instance);
	}

	/**
	 * Activate debugging
	 */
	public static function activate(ORM\EntityManager $em) {
		if (self::$instance) {
			// always cleanup
			self::deactivate();
		}
		self::$instance = new Debug($em);
	}

	/**
	 * Deactivate debugging
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
	protected function attachSqlLogger(Logging\SQLLogger $sqlLogger = null) {
		$this->sqlLogger = $sqlLogger;
		$this->em->getConnection()->getConfiguration()->setSQLLogger($sqlLogger);
	}

	/**
	 * @return float execution time
	 */
	public function getExecutionTime() {
		return round((microtime(TRUE) - $this->created), 5);
	}

	/**
	 * @return array two-dimensional array with sql queries and parameters
	 */
	public function getQueries() {
		return ($this->sqlLogger) ? $this->sqlLogger->queries : array();
	}

	/**
	 * getParametrizedQuery helper function
	 */
	private static function formatSQLParameter($para) {
		if (is_string($para)) {
			return (strtoupper($para) == 'NULL') ?: "'" . $para . "'";
		}
		return $para;
	}

	/**
	 * @return string format SQL string with parameters
	 * @author Andreas Goetz <cpuidle@gmx.de>
	 */
	public static function getParametrizedQuery($sql, $sqlParameters) {
		while (count((array) $sqlParameters)) {
			$sql = preg_replace('/\?/', self::formatSQLParameter(array_shift($sqlParameters)), $sql, 1);
		}
		if (php_sapi_name() === 'cli' && class_exists('\SqlFormatter')) {
			$sql = \SqlFormatter::format($sql, false);
		}
		return $sql;
	}

	/**
	 * @return array two-dimensional array with messages
	 */
	public function getMessages() { return $this->messages; }

	/**
	 * @return Debug the Debug instance if available
	 * @todo encapsulate in state class? or inherit from singleton class?
	 */
	public static function getInstance() { return self::$instance; }

	/**
	 * Fail-safe, non-warning, portable shell_exec
	 */
	public static function safeExec($cmd) {
		// shell_exec doesn't exist in safe mode
		if (!function_exists('shell_exec')) {
			return FALSE;
		}

		// platform-independent null device to silence STDERR
		$null = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'nul' : '/dev/null';
		return @shell_exec($cmd . ' 2>' . $null);
	}

	/**
	 * Tries to determine the current SHA1 hash of your git commit
	 *
	 * @return string the hash
	 */
	public static function getCurrentCommit() {
		if (file_exists(VZ_DIR . '/.git/HEAD') && ($head = @file_get_contents(VZ_DIR . '/.git/HEAD'))) {
			if ($commit = substr(@file_get_contents(VZ_DIR . '/.git/' . substr($head, strpos($head, ' ')+1, -1)), 0, -1)) {
				return $commit;
			}
		}

		return self::safeExec('git show --pretty=format:%H --quiet');
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
		elseif (@is_readable('/proc/loadavg')) {
			$load = file_get_contents('/proc/loadavg');
			$load = array_slice(explode(' ', $load), 0, 3);
		}
		elseif ($res = self::safeExec('uptime')) {
			$load = explode(', ', substr($res, -16));
		}

		return (isset($load)) ? array_map('floatval', $load) : FALSE;
	}

	/**
	 * Get server uptime
	 *
	 * @return number|bool server uptime in seconds
	 */
	public static function getUptime() {
		if (@is_readable("/proc/uptime")) {
			$uptime = explode(' ', file_get_contents("/proc/uptime"));
			return (float) $uptime[0];
		}
		elseif ($res = self::safeExec('uptime')) {
			$matches = array();
			if (preg_match("/up (?:(?P<days>\d+) days?,? )?(?P<hours>\d+):(?P<minutes>\d{2})/", $res, $matches)) {
				$uptime = 60 * (int)$matches['hours'] + (int)$matches['minutes'];

				if (isset($matches['days']) && $matches['days'] > 0) {
					$uptime += $matches['days']*60*24;
				}

				return 60 * $uptime; // minutes => seconds
			}
		}
		return FALSE;
	}
}

?>
