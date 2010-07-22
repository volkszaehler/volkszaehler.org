<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package default
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
 *
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

namespace Volkszaehler\View\CSV;

use Volkszaehler\View\HTTP;
use Volkszaehler\View;
use Volkszaehler\Util;

/**
 * CSV view
 *
 * also used for data
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
abstract class CSVView extends View\View {
	protected $csv = array();
	protected $header = array();
	protected $footer = array();

	protected $delimiter = ';';
	protected $enclosure = '"';

	/**
	 * constructor
	 */
	public function __construct(HTTP\Request  $request, HTTP\Response $response) {
		parent::__construct($request, $response);

		$this->header[] = 'source: volkszaehler.org';
		$this->header[] = 'version: ' . \Volkszaehler\VERSION;

		$this->response->setHeader('Content-type', 'text/csv');
		$this->response->setHeader('Content-Disposition', 'attachment; filename="data.csv"');
	}

	public function render() {
		foreach ($this->header as $line) {
			echo $line . PHP_EOL;
		}

		echo PHP_EOL;

		foreach ($this->csv as $array) {
			$array = array_map(array($this, 'escape'), $array);

			echo implode($this->delimiter, $array) . PHP_EOL;
		}

		echo PHP_EOL;

		foreach ($this->footer as $line) {
			echo $line . PHP_EOL;
		}

		parent::render();
	}

	protected function escape($value) {
		if (is_string($value)) {
			return $this->enclosure . $value . $this->enclosure;
		}
		elseif (is_numeric($value)) {
			return $value;
		}
		else {
			return (string) $value;
		}
	}

	public function addDebug(Util\Debug $debug) {
		$this->footer[] = 'time: ' . $debug->getExecutionTime();
		$this->footer[] = 'database: ' . Util\Configuration::read('db.driver');

		foreach ($debug->getMessages() as $message) {
			$this->footer[] = 'message: ' . $message['message'];	// TODO add more information
		}

		foreach ($debug->getQueries() as $query) {
			$this->footer[] = 'query: ' . $query['sql'];
			$this->footer[] = '  parameters: ' . implode(', ', $query['parameters']);
		}
	}

	public function addException(\Exception $exception) {
		echo $exception;
	}
}

?>