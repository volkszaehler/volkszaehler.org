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

class XmlView extends View {
	public $doc;

	public function __construct(HttpRequest $request, HttpResponse $response) {
		parent::__construct($request, $response);

		$config = Registry::get('config');

		$this->doc = new DOMDocument('1.0', 'UTF-8');

		$this->source = 'volkszaehler.org';		// TODO create XML
		$this->version = VZ_VERSION;
		$this->storage = $config['db']['backend'];
		$this->controller = $request->get['controller'];
		$this->action = $request->get['action'];

		$this->response->headers['Content-type'] = 'application/json';
	}
	
	public function getChannel(Channel $channel) {		// TODO improve view interface
	return array('id' => (int) $channel->id,
						'ucid' => $channel->ucid,
						'resolution' => (int) $channel->resolution,
						'description' => $channel->description,
						'type' => $channel->type,
						'costs' => $channel->cost);
	}

	public function render() {
		$this->time = round(microtime(true) - $this->created, 4);
		echo json_encode($this->data);
	}

	public function exceptionHandler(Exception $exception) {
		$xmlException = $this->doc->createElement('exception');

		$xmlException->setAttribute('code', $exception->getCode());

		$xmlException->appendChild($this->doc->createElement('message', $exception->getMessage()));
		$xmlException->appendChild($this->doc->createElement('line', $exception->getLine()));
		$xmlException->appendChild($this->doc->createElement('file', $exception->getFile()));

		$xmlException->appendChild($this->backtrace($exception->getTrace()));

		$this->render();
		die();
	}

	function backtrace($traces) {
		$xmlTraces = $this->doc->createElement('backtrace');

		foreach ($traces as $step => $trace) {
			$xmlTrace = $this->doc->createElement('trace');
			$xmlTraces->appendChild($xmlTrace);
			$xmlTrace->setAttribute('step', $step);

			foreach ($trace as $key => $value) {
				switch ($key) {
					case 'function':
					case 'line':
					case 'file':
					case 'class':
					case 'type':
						$xmlTrace->appendChild($this->doc->createElement($key, $value));
						break;
					case 'args':
						$xmlArgs = $doc->createElement($key);
						$xmlTrace->appendChild($xmlArgs);
						foreach ($value as $arg) {
							$xmlArgs->appendChild($this->doc->createElement('arg', $value));
						}
						break;
				}
			}
		}

		return $xmlTraces;
	}
}

?>