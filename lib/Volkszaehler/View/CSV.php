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

		echo '# source: volkszaehler.org' . PHP_EOL;
		echo '# version: ' . VZ_VERSION . PHP_EOL;

		$this->response->setHeader('Content-type', 'text/csv');
	}

	/**
	 * Add object to output
	 *
	 * @param mixed $data
	 */
	public function add($data) {
		if ($data instanceof Interpreter\Interpreter) {
			$this->addData($data);
		}
		elseif (is_array($data) && isset($data[0]) && $data[0] instanceof Interpreter\Interpreter) {
			$this->addMultipleData($data);
		}
		elseif ($data instanceof \Exception) {
			$this->addException($data);
		}
		elseif ($data instanceof Util\Debug) {
			$this->addDebug($data);
		}
		elseif (isset($data)) { // ignores NULL data
			throw new \Exception('Can\'t show: \'' . get_class($data) . '\'');
		}
	}

	/**
	 * Add debugging information include queries and messages to output queue
	 *
	 * @param Util\Debug $debug
	 */
	protected function addDebug(Util\Debug $debug) {
		echo '# level: ' . $debug->getLevel() . PHP_EOL;
		echo '# database: ' . Util\Configuration::read('db.driver') . PHP_EOL;
		echo '# time: ' . $debug->getExecutionTime() . PHP_EOL;

		if ($uptime = Util\Debug::getUptime()) echo '# uptime: ' . $uptime*1000;
		if ($load = Util\Debug::getLoadAvg()) echo '# load: ' . implode(', ', $load) . PHP_EOL;
		if ($commit = Util\Debug::getCurrentCommit()) echo '# commit-hash: ' . $commit;
		if ($version = Util\Debug::getPhpVersion()) echo '# php-version: ' . $version;

		foreach ($debug->getMessages() as $message) {
			echo '# message: ' . $message['message'] . PHP_EOL;	// TODO add more information
		}

		foreach ($debug->getQueries() as $query) {
			echo '# query: ' . $query['sql'] . PHP_EOL;
			if (isset($query['parameters'])) {
				echo "# \tparameters: " . implode(', ', $query['parameters']) . PHP_EOL;
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
		echo get_class($exception) . '# [' . $exception->getCode() . ']' . ':' . $exception->getMessage() . PHP_EOL;

		if (Util\Debug::isActivated()) {
			echo "#\tfile: " . $exception->getFile() . PHP_EOL;
			echo "#\tline: " . $exception->getLine() . PHP_EOL;
		}
	}

	/**
	 * Add multiple data objects to output queue
	 *
	 * @param $interpreter
	 */
	protected function addMultipleData($data) {
		for ($i=0; $i<count($data); $i++) {
			$this->addData($data[$i], $i>0);
		}
	}

	/**
	 * Add data to output queue
	 *
	 * @param Interpreter\InterpreterInterface $interpreter
	 * @param boolean $children
	 * @todo  Aggregate first is assumed- this deviates from json view behaviour
	 */
	protected function addData(Interpreter\Interpreter $interpreter, $children = false) {
		if ($children == false) {
			$this->response->setHeader(
				'Content-Disposition',
				'attachment; ' .
				'filename="' . strtolower($interpreter->getEntity()->getProperty('title')) . '.csv" ' .
				'creation-date="' .  date(DATE_RFC2822, $interpreter->getTo()/1000). '"'
			);
		}

		$tuples = $interpreter->processData(
			function($tuple) {
				return array(
					$tuple[0],
					View::formatNumber($tuple[1]),
					$tuple[2]
				);
			}
		);

		$min = $interpreter->getMin();
		$max = $interpreter->getMax();
		$average = $interpreter->getAverage();
		$consumption = $interpreter->getConsumption();

		$from = $this->formatTimestamp($interpreter->getFrom());
		$to = $this->formatTimestamp($interpreter->getTo());

		echo '# uuid: ' . $interpreter->getEntity()->getUuid() . PHP_EOL;

		if (isset($from)) echo '# from: ' . $from . PHP_EOL;
		if (isset($to)) echo '# to: ' . $to . PHP_EOL;
		if (isset($min)) echo '# min: ' . $this->formatTimestamp($min[0]) . ' => ' . View::formatNumber($min[1]) . PHP_EOL;
		if (isset($max)) echo '# max: ' . $this->formatTimestamp($max[0]) . ' => ' . View::formatNumber($max[1]) . PHP_EOL;
		if (isset($average))  echo '# average: ' . View::formatNumber($average) . PHP_EOL;
		if (isset($consumption)) echo '# consumption: ' . View::formatNumber($consumption) . PHP_EOL;

		echo '# rows: ' . $interpreter->getRowCount() . PHP_EOL;

		if (isset($tuples)) {
			// Aggregators don't return tuples
			foreach ($tuples as $tuple) {
				echo $this->formatTimestamp($tuple[0]) . CSV::DELIMITER . $tuple[1] . CSV::DELIMITER . $tuple[2] . PHP_EOL;
			}
		}
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
