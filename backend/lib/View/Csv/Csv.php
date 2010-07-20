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

namespace Volkszaehler\View\Csv;

use Volkszaehler\Util;

abstract class Csv extends \Volkszaehler\View\View {
	protected $csv = array();
	protected $header = array();
	protected $footer = array();

	/*
	 * constructor
	 */
	public function __construct(\Volkszaehler\View\Http\Request $request, \Volkszaehler\View\Http\Response $response) {
		parent::__construct($request, $response);

		$this->header[] = 'source: volkszaehler.org';
		$this->header[] = 'version: ' . \Volkszaehler\VERSION;

		$this->response->setHeader('Content-type', 'text/plain');
		//$this->response->setHeader('Content-Disposition', 'attachment; filename="data.csv"');
	}
	
	public function render() {
		foreach ($this->header as $line) {
			echo $line . PHP_EOL;
		}
		
		echo PHP_EOL;
		
		foreach ($this->csv as $array) {
			echo implode(";", $array) . PHP_EOL;
		}
		
		echo PHP_EOL;
		
		foreach ($this->footer as $line) {
			echo $line . PHP_EOL;
		}
		
		parent::render();
	}
	
	public function addDebug() {
		$this->footer[] = 'time: ' . $this->getTime();
		$this->footer[] = 'database: ' . Util\Configuration::read('db.driver');
		
		foreach (\Volkszaehler\Util\Debug::getSQLLogger()->queries as $query) {
			$this->footer[] = 'query: ' . $query['sql'];
			$this->footer[] = '  parameters: ' . implode(', ', $query['parameters']);
		}
	}
	
	public function addException(\Exception $exception) {
		echo $exception;
	}
}

?>