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

namespace Volkszaehler\View\Xml;

// TODO outdated
class Xml extends \Volkszaehler\View\View {
	protected $xmlDoc;
	protected $xml;

	public function __construct(\Volkszaehler\View\Http\Request $request, \Volkszaehler\View\Http\Response $response) {
		parent::__construct($request, $response);

		$this->xmlDoc = new \DOMDocument('1.0', 'UTF-8');

		$this->xmlRoot = $this->xmlDoc->createElement('volkszaehler');
		$this->xmlRoot->setAttribute('version', \Volkszaehler\VERSION);

		$this->xmlRoot->appendChild($this->xmlDoc->createElement('source', 'volkszaehler.org'));

		$this->response->setHeader('Content-type', 'application/xml; charset=UTF-8');
	}

	public function render() {
		parent::render();
		
		$this->xmlDoc->appendChild($this->xmlRoot);
		$this->xmlRoot->appendChild($this->xml);
		echo $this->xmlDoc->saveXML();
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
	
	public function addDebug() {
		$config = \Volkszaehler\Util\Registry::get('config');

		$xmlDebug = $this->xmlDoc->createElement('debug');
		
		$xmlDebug->appendChild($this->xmlDoc->createElement('time', $this->getTime()));
		$xmlDebug->appendChild($this->xmlDoc->createElement('database', $config['db']['driver']));
		
		// TODO add queries
		
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
							$xmlArgs->appendChild($this->xmlDoc->createElement('arg', print_r($value, true))); // TODO check $value content
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