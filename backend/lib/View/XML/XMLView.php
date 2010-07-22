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

namespace Volkszaehler\View\XML;

use Volkszaehler\View\HTTP;
use Volkszaehler\View;
use Volkszaehler\Util;

/**
 * XML view
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
abstract class XMLView extends View\View {
	protected $xmlDoc;

	public function __construct(HTTP\Request  $request, HTTP\Response $response) {
		parent::__construct($request, $response);

		$this->xmlDoc = new \DOMDocument('1.0', 'UTF-8');

		$this->xmlRoot = $this->xmlDoc->createElement('volkszaehler');
		$this->xmlRoot->setAttribute('version', \Volkszaehler\VERSION);

		$this->xmlRoot->appendChild($this->xmlDoc->createElement('source', 'volkszaehler.org'));

		$this->response->setHeader('Content-type', 'application/xml; charset=UTF-8');
	}

	public function render() {
		$this->xmlDoc->appendChild($this->xmlRoot);
		echo $this->xmlDoc->saveXML();

		parent::render();
	}

	public function addException(\Exception $exception) {
		$xmlException = $this->xmlDoc->createElement('exception');
		$xmlException->setAttribute('code', $exception->getCode());
		$xmlException->appendChild($this->xmlDoc->createElement('message', $exception->getMessage()));
		$xmlException->appendChild($this->xmlDoc->createElement('line', $exception->getLine()));
		$xmlException->appendChild($this->xmlDoc->createElement('file', $exception->getFile()));
		$xmlException->appendChild($this->fromTrace($exception->getTrace()));

		$this->xmlRoot->appendChild($xmlException);
	}

	public function addDebug(Util\Debug $debug) {
		$xmlDebug = $this->xmlDoc->createElement('debug');

		$xmlDebug->appendChild($this->xmlDoc->createElement('time', $debug->getExecutionTime()));
		$xmlDebug->appendChild($this->xmlDoc->createElement('database', Util\Configuration::read('db.driver')));

		// TODO add queries to xml debug
		// TODO add messages to xml output

		$this->xmlRoot->appendChild($xmlDebug);
	}

	private function fromTrace($traces) {
		$xmlTraces = $this->xmlDoc->createElement('backtrace');

		foreach ($traces as $step => $trace) {
			$xmlTrace = $this->xmlDoc->createElement('trace');
			$xmlTraces->appendChild($xmlTrace);
			$xmlTrace->setAttribute('step', $step);

			foreach ($trace as $key => $value) {
				switch ($key) {
					case 'args':
						$xmlArgs = $this->xmlDoc->createElement($key);
						$xmlTrace->appendChild($xmlArgs);
						foreach ($value as $arg) {
							$xmlArgs->appendChild($this->xmlDoc->createElement('arg', (is_scalar($value)) ? $value : print_r($value, TRUE)));
						}
						break;

					case 'type':
					case 'function':
					case 'line':
					case 'file':
					case 'class':
					default:
						$xmlTrace->appendChild($this->xmlDoc->createElement($key, $value));
				}
			}
		}

		return $xmlTraces;
	}
}

?>
