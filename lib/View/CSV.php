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

use Volkszaehler\Util;
use Volkszaehler\Interpreter;

/**
 * CSV view
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class CSV extends View {
	const DELIMITER = ';';
	const ENCLOSURE = '"';

	/**
	 * constructor
	 */
	public function __construct(Request  $request) {
		parent::__construct($request);

		// set default timestamp format
		if (!$this->request->query->has('tsfmt')) {
			$this->request->query->set('tsfmt', 'sql');
		}

		$this->response->headers->set('Content-Type', 'text/csv');

		ob_start();

		echo '# source:' . CSV::DELIMITER . 'volkszaehler.org' . PHP_EOL;
		echo '# version:' . CSV::DELIMITER . VZ_VERSION . PHP_EOL;
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
			foreach ($data as $interpreter) {
				$this->add($interpreter);
			}
		}
		elseif ($data instanceof \Exception) {
			$this->addException($data);
		}
		elseif ($data instanceof Util\Debug) {
			$this->addDebug($data);
		}
		elseif (isset($data)) { // ignores NULL data
			throw new \Exception('Can\'t show: \'' . self::getClassOrType($data) . '\'');
		}
	}

	/**
	 * Add debugging information include queries and messages to output queue
	 *
	 * @param Util\Debug $debug
	 */
	protected function addDebug(Util\Debug $debug) {
		echo '# database:' . CSV::DELIMITER . Util\Configuration::read('db.driver') . PHP_EOL;
		echo '# time:' . CSV::DELIMITER . $debug->getExecutionTime() . PHP_EOL;

		if ($uptime = Util\Debug::getUptime()) echo '# uptime:' . CSV::DELIMITER . $uptime*1000;
		if ($load = Util\Debug::getLoadAvg()) echo '# load:' . CSV::DELIMITER . implode(', ', $load) . PHP_EOL;
		if ($commit = Util\Debug::getCurrentCommit()) echo '# commit-hash:' . CSV::DELIMITER . $commit;
		if ($version = phpversion()) echo '# php-version:' . CSV::DELIMITER . $version;

		foreach ($debug->getMessages() as $message) {
			echo '# message:' . CSV::DELIMITER . $message['message'] . PHP_EOL;	// TODO add more information
		}

		foreach ($debug->getQueries() as $query) {
			echo '# query:' . CSV::DELIMITER . $query['sql'] . PHP_EOL;
			if (isset($query['parameters'])) {
				echo "# \tparameters:" . CSV::DELIMITER . implode(', ', $query['parameters']) . PHP_EOL;
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
			echo "#\tfile:" . CSV::DELIMITER . $exception->getFile() . PHP_EOL;
			echo "#\tline:" . CSV::DELIMITER . $exception->getLine() . PHP_EOL;
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
		$this->response->headers->set(
			'Content-Disposition', 'attachment; ' .
				'filename="' . strtolower($interpreter->getEntity()->getProperty('title')) . '.csv" '
				// removed to to lack of support in Chrome
				// 'creation-date="' . date(DATE_RFC2822, $interpreter->getTo() / 1000). '"'
		);

		echo PHP_EOL; // UUID delimiter
		echo '# uuid:' . CSV::DELIMITER . $interpreter->getEntity()->getUuid() . PHP_EOL;
		echo '# title:' . CSV::DELIMITER . $interpreter->getEntity()->getProperty('title') . PHP_EOL;

		if ($interpreter instanceof Interpreter\AggregatorInterpreter) {
			// min/ max etc are not populated if $children->processData hasn't been called
			return;
		}

		$data = array();
		// iterate through PDO resultset
		foreach ($interpreter as $tuple) {
			$data[] = $tuple;
		}

		$min = $interpreter->getMin();
		$max = $interpreter->getMax();
		$average = $interpreter->getAverage();
		$consumption = $interpreter->getConsumption();

		$from = $this->formatTimestamp($interpreter->getFrom());
		$to = $this->formatTimestamp($interpreter->getTo());

		if (isset($from)) echo '# from:' . CSV::DELIMITER . $from . PHP_EOL;
		if (isset($to)) echo '# to:' . CSV::DELIMITER . $to . PHP_EOL;
		if (isset($min)) echo '# min:' . CSV::DELIMITER . $this->formatTimestamp($min[0]) . CSV::DELIMITER . ' => ' . CSV::DELIMITER . View::formatNumber($min[1]) . PHP_EOL;
		if (isset($max)) echo '# max:' . CSV::DELIMITER . $this->formatTimestamp($max[0]) . CSV::DELIMITER . ' => ' . CSV::DELIMITER . View::formatNumber($max[1]) . PHP_EOL;
		if (isset($average))  echo '# average:' . CSV::DELIMITER . View::formatNumber($average) . PHP_EOL;
		if (isset($consumption)) echo '# consumption:' . CSV::DELIMITER . View::formatNumber($consumption) . PHP_EOL;

		echo '# rows:' . CSV::DELIMITER . $interpreter->getRowCount() . PHP_EOL;

		if (isset($data)) {
			// Aggregators don't return data
			foreach ($data as $tuple) {
				echo $this->formatTimestamp($tuple[0]) . CSV::DELIMITER . View::formatNumber($tuple[1]) . CSV::DELIMITER . $tuple[2] . PHP_EOL;
			}
		}
	}

	/**
	 * Process, encode and print output to stdout
	 */
	protected function render() {
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

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
