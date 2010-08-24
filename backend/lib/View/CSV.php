<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
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

/**
 * CSV view
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 * @todo rework
 */
class CSV extends View {
	protected $delimiter = ';';
	protected $enclosure = '"';

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

	public function addChannel(Model\Channel $channel, array $data = NULL) {
		$this->csv = array_merge($this->csv, $data);
	}

	public function addGroup(Model\Group $group) {

	}

	public function addDebug(Util\Debug $debug) {

	}

	protected function addException(\Exception $e) {

	}

	public function renderResponse() {
		// channel data
		foreach ($this->csv as $row) {
			$array = array_map(array($this, 'escape'), $row);
			echo implode($this->delimiter, $row) . PHP_EOL;
		}

		echo PHP_EOL;

		// debug
		echo 'time: ' . $debug->getExecutionTime() . PHP_EOL;
		echo 'database: ' . Util\Configuration::read('db.driver') . PHP_EOL;

		foreach ($debug->getMessages() as $message) {
			echo 'message: ' . $message['message'] . PHP_EOL;	// TODO add more information
		}

		foreach ($debug->getQueries() as $query) {
			echo 'query: ' . $query['sql'] . PHP_EOL;
			echo '  parameters: ' . implode(', ', $query['parameters']) . PHP_EOL;
		}
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
}

?>