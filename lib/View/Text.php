<?php
/**
 * @copyright Copyright (c) 2011-2017, The volkszaehler.org project
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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Volkszaehler\Util;
use Volkszaehler\Interpreter;

/**
 * Plain text view
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 * @package default
 */
class Text extends CSV {

	/**
	 * Constructor
	 */
	public function __construct(Request  $request) {
		// avoid calling parent::__construct($request);
		$this->request = $request;
		$this->response = new Response();
		$this->response->headers->set('Content-Type', 'text/plain');
		ob_start();
	}

	/**
	 * Add exception to output queue
	 *
	 * @param \Exception $exception
	 * @param boolean $debug
	 */
	protected function addException(\Exception $exception) {
		echo get_class($exception) . '# [' . $exception->getCode() . ']' . ':' . $exception->getMessage() . PHP_EOL;

		if (Util\Debug::isActivated()) {
			echo "file:\t" . $exception->getFile() . PHP_EOL;
			echo "line:\t" . $exception->getLine() . PHP_EOL;
		}
	}

	/**
	 * Add debugging information include queries and messages to output queue
	 *
	 * @param Util\Debug $debug
	 */
	protected function addDebug(Util\Debug $debug) {
		echo "database:\t" . Util\Configuration::read('db.driver') . PHP_EOL;
		echo "time:\t" . $debug->getExecutionTime() . PHP_EOL;

		if ($uptime = Util\Debug::getUptime()) echo "uptime:\t" . $uptime*1000;
		if ($load = Util\Debug::getLoadAvg()) echo "load:\t" . implode(', ', $load) . PHP_EOL;
		if ($commit = Util\Debug::getCurrentCommit()) echo "commit-hash:\t" . $commit . PHP_EOL;
		if ($version = phpversion()) echo "php-version:\t" . $version . PHP_EOL;

		foreach ($debug->getMessages() as $message) {
			echo "message:\t" . $message['message'] . PHP_EOL;	// TODO add more information
		}

		foreach ($debug->getQueries() as $query) {
			echo "query:\t" . $query['sql'] . PHP_EOL;
			if (isset($query['parameters'])) {
				echo "\tparameters:\t" . implode(', ', $query['parameters']) . PHP_EOL;
			}
		}
	}

	/**
	 * Add data to output queue
	 *
	 * @param Interpreter\InterpreterInterface $interpreter
	 * @param boolean $children
	 * @todo  Aggregate first is assumed- this deviates from json view behaviour
	 */
	protected function addData(Interpreter\Interpreter $interpreter) {
		// echo "uuid:' . $interpreter->getEntity()->getUuid() . PHP_EOL;
		// echo "title:' . $interpreter->getEntity()->getProperty('title') . PHP_EOL;

		if ($interpreter instanceof Interpreter\AggregatorInterpreter) {
			// min/ max etc are not populated if $children->processData hasn't been called
			return;
		}

		$data = array();
		// iterate through PDO resultset
		foreach ($interpreter as $tuple) {
			$data[] = $tuple;
		}

		// get unit
		$unit = $interpreter->getEntity()->getDefinition();
		$unit = isset($unit->unit) ? $unit->unit : '';

		if (sizeof($data) == 0 ||
			$this->request->query->has('tuples') && $this->request->query->get('tuples') == 1
		) {
			$val = $interpreter->getConsumption();
			$unit .= 'h';
		}
		else {
			$val = $data[sizeof($data)-1];
		}
		$val = is_array($val) ? $val[1] : $val;

		echo $val . ' ' . $unit;
	}
}

?>
