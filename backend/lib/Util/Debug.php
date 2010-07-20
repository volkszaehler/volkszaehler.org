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

use Doctrine\DBAL\Logging;

class Debug implements Logging\SQLLogger {
	protected static $instance = NULL;
	
	protected $queries = array();
	protected $messages = array();
	
	protected $started;	// holds timestamp of initialization, used later to return time of execution
	protected $level;
	
	/*
	 * constructor
	 */
	public function __construct($level) {
		// taking timestamp to stop execution time
		$this->created = microtime(TRUE);
		
		$this->level = $level;
		
		if (isset(self::$instance)) {
			throw new \Exception('debugging has already been started. please use the static functions!');
		}
		self::$instance = $this;
		
		// assert options
		assert_options(ASSERT_ACTIVE, TRUE);
		assert_options(ASSERT_BAIL, FALSE);
		assert_options(ASSERT_WARNING, FALSE);
		assert_options(ASSERT_CALLBACK, array($this, 'assertHandler'));
		
		
	}
	
	/*
	 * interface for doctrine's dbal sql logger
	 */ 
	function logSQL($sql, array $parameter = NULL) {
		$query['sql'] = $sql;
		
		if (isset($parameter) && !empty($parameter)) {
			$query['parameters'] = $parameter;
		}
		
		$this->queries[] = $query;
	}
	
	static public function log($message) {
		if (isset(self::$instance)) {
			$trace = debug_backtrace();
			
			self::$instance->messages[] = array(
				'message' => $message,
				'file' => $trace[0]['file'],
				'line' => $trace[0]['line'],
				'time' => date('r'),
				'args' => array_slice($trace[0]['args'], 1),
				'trace' => $trace
			);
		}
	}
	
	/*
	 * simple assertion passthrough for future improvements
	 * 
	 * @todo check how should be en/disabled (options etc..)
	 */
	public static function assert($code) {
		assert($code);
	}
	
	public function assertHandler($file, $line, $code) {
		$trace = debug_backtrace();
		$info = $trace[2];
		
		$this->messages[] = array(
			'message' => 'assertion failed: ' . $code,
			'file' => $info['file'],
			'line' => $info['line'],
			'time' => date('r'),
			'trace' => array_slice($trace, 2)
		);
	}
	
	public static function isActivated() { return isset(self::$instance); }
	
	public function getExecutionTime() { return round((microtime(TRUE) - $this->created), 5); }
	public function getQueries() { return $this->queries; }
	public function getMessages() { return $this->messages; }
}

?>