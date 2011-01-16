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
		$this->sqlLogger = new Logging\DebugStack();
		$em->getConnection()->getConfiguration()->setSQLLogger($this->sqlLogger);

		if (isset(self::$instance)) {
			throw new \Exception('Debugging has already been started. please use the static functions!');
		}
		self::$instance = $this;

		// assert options
		assert_options(ASSERT_ACTIVE, TRUE);	// activate assertions
		assert_options(ASSERT_BAIL, FALSE);
		assert_options(ASSERT_WARNING, FALSE);
		assert_options(ASSERT_CALLBACK, array($this, 'assertHandler'));
	}

	/*
	 * logs messages to the debug stack including file, lineno, args and a stacktrace
	 *
	 * @param string $message
	 * @param more parameters could be passed
	 */
	static public function log($message) {
		if (isset(self::$instance)) {
			$trace = debug_backtrace();
			$info = $trace[0];

			self::$instance->messages[] = array(
				'message' => $message,
				'file' => $info['file'],
				'line' => $info['line'],
				//'time' => date('r'),
				'args' => array_slice($info['args'], 1)
				//'trace' => array_slice($trace, 1)
			);
		}
	}

	/**
	 * simple assertion passthrough for future improvements
	 *
	 * @param string $code code to be evaluated
	 */
	public static function assert($code) {
		return assert($code);
	}

	/**
	 * handles failed assertions
	 *
	 * @param string $file
	 * @param integer $line
	 * @param string $code code to be evaluated
	 */
	public function assertHandler($file, $line, $code) {
		$trace = debug_backtrace();
		$info = $trace[2];

		$this->messages[] = array(
			'message' => 'assertion failed: ' . $code,
			'file' => $info['file'],
			'line' => $info['line'],
			'time' => date('r'),
			'trace' => array_slice($trace, 3)
		);
	}

	/**
	 * Is debugging enabled?
	 * @return boolean
	 */
	public static function isActivated() { return isset(self::$instance); }

	/**
	 * @return float execution time
	 */
	public function getExecutionTime() { return round((microtime(TRUE) - $this->created), 5); }

	/**
	 * @return 2 dimensional array with sql queries and parameters
	 */
	public function getQueries() { return $this->sqlLogger->queries; }

	/**
	 * @return 2 dimensional array with messages
	 */
	public function getMessages() { return $this->messages; }

	/**
	 * @return Debug the Debug instance if available
	 * @todo encapsulate in state class? or inherit from singleton class?
	 */
	public static function getInstance() { return self::$instance; }
}

?>
