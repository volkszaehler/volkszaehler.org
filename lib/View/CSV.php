<?php
/**
 * @copyright Copyright (c) 2011, The volkszaehler.org project
 * @package default
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

namespace Volkszaehler\View;

use Volkszaehler\View\HTTP;
use Volkszaehler\Util;
use Volkszaehler\Interpreter;

/**
 * CSV view
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 * @todo rework
 */
class CSV extends View {
	const DELIMITER = ';';
	const ENCLOSURE = '"';

	protected $csv = array();

	/**
	 * constructor
	 */
	public function __construct(HTTP\Request  $request, HTTP\Response $response) {
		parent::__construct($request, $response);

		echo 'source: volkszaehler.org' . PHP_EOL;
		echo 'version: ' . VZ_VERSION . PHP_EOL;

		$this->response->setHeader('Content-type', 'text/csv');
		$this->response->setHeader('Content-Disposition', 'attachment; filename="data.csv"');
	}
	
	/**
	 * Add object to output
	 *
	 * @param mixed $data
	 */
	public function add($data) {
		if ($data instanceof Interpreter\Interpreter || $data instanceof Interpreter\AggregatorInterpreter) {
			$this->addData($data);
		}
		elseif ($data instanceof \Exception) {
			$this->addException($data);
		}
		elseif ($data instanceof Util\Debug) {
			$this->addDebug($data);
		}
		elseif (isset($data)) { // ignores NULL data
			throw new \Exception('Can\'t show ' . get_class($data));
		}
	}
	
	/**
	 * Add debugging information include queries and messages to output queue
	 *
	 * @param Util\Debug $debug
	 */
	protected function addDebug(Util\Debug $debug) {
		echo 'time: ' . $debug->getExecutionTime() . PHP_EOL;
		echo 'database: ' . Util\Configuration::read('db.driver') . PHP_EOL;

		foreach ($debug->getMessages() as $message) {
			echo 'message: ' . $message['message'] . PHP_EOL;	// TODO add more information
		}

		foreach ($debug->getQueries() as $query) {
			echo 'query: ' . $query['sql'] . PHP_EOL;
			if (isset($query['parameters'])) {
				echo "\tparameters: " . implode(', ', $query['parameters']) . PHP_EOL;
			}
		}
	}
	
	/**
	 * Add exception to output queue
	 *
	 * @param \Exception $exception
	 * @param boolean $debug
	 */
	protected function addException(\Exception $exception) {
		echo get_class($exception) . '[' . $exception->getCode() . ']' . ':' . $exception->getMessage() . PHP_EOL;

		if (Util\Debug::isActivated()) {
			echo "\tfile: " . $exception->getFile() . PHP_EOL;
			echo "\tline: " . $exception->getLine() . PHP_EOL;
		}
	}
	
	/**
	 * Add data to output queue
	 *
	 * @param Interpreter\InterpreterInterface $interpreter
	 */
	protected function addData(Interpreter\Interpreter $interpreter) {
		//$this->response->setHeader('Content-Disposition', 'attachment; filename="' . strtolower($interpreter->getEntity()->getProperty('title')) . '.csv"'); // TODO add time?
		
		$tuples = $interpreter->processData(
			$this->request->getParameter('tuples'),
			$this->request->getParameter('group'), 
			function($tuple) {
				echo implode(CSV::DELIMITER, array(
					$tuple[0],
					View::formatNumber($tuple[1]),
					$tuple[2]
				)) . PHP_EOL; 
			}
		);
	}

	/**
	 * Process, encode and print output to stdout
	 */
	protected function render() { }

	/**
	 * Escape data according to CSV format
	 *
	 * @param $value to be escaped
	 * @return string escaped data
	 */
	protected function escape($value) {
		if (is_string($value)) {
			return self::ENCLOSURE . $value . self::ENCLOSURE;
		}
		elseif (is_numeric($value)) {
			return $value;
		}
		else {
			return (string) $value;
		}
	}
}

?>
