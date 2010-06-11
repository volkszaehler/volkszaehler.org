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
	private $xmlDoc;
	private $xml;
	private $xmlChannels;
	private $xmlUsers;
	private $xmlGroups;

	public function __construct(HttpRequest $request, HttpResponse $response) {
		parent::__construct($request, $response);

		$config = Registry::get('config');

		$this->xmlDoc = new DOMDocument('1.0', 'UTF-8');

		$this->xml = $this->xmlDoc->createElement('volkszaehler');
		$this->xml->setAttribute('version', VZ_VERSION);
		$this->xmlChannels = $this->xmlDoc->createElement('channels');
		$this->xmlUsers = $this->xmlDoc->createElement('users');
		$this->xmlGroups = $this->xmlDoc->createElement('groups');

		$this->xml->appendChild($this->xmlDoc->createElement('source', 'volkszaehler.org'));
		$this->xml->appendChild($this->xmlDoc->createElement('storage', $config['db']['backend']));
		$this->xml->appendChild($this->xmlDoc->createElement('controller', $request->get['controller']));
		$this->xml->appendChild($this->xmlDoc->createElement('action', $request->get['action']));
		
		$this->response->setHeader('Content-type', 'text/xml');
	}

	public function addChannel(Channel $obj, $data = NULL) {
		$xmlChannel = $this->xmlDoc->createElement('channel');
		$xmlChannel->setAttribute('id', (int) $obj->id);
		
		$xmlChannel->appendChild($this->xmlDoc->createElement('ucid', $obj->ucid));
		$xmlChannel->appendChild($this->xmlDoc->createElement('type', $obj->type));
		$xmlChannel->appendChild($this->xmlDoc->createElement('unit', $obj->unit));
		$xmlChannel->appendChild($this->xmlDoc->createElement('description', $obj->description));
		$xmlChannel->appendChild($this->xmlDoc->createElement('resolution', (int) $obj->resolution));
		$xmlChannel->appendChild($this->xmlDoc->createElement('cost', (float) $obj->cost));
		
		if (!is_null($data) && is_array($data)) {
			$xmlData = $this->xmlDoc->createElement('data');
			
			foreach ($data as $reading) {
				$xmlReading = $this->xmlDoc->createElement('reading');
				
				$xmlReading->setAttribute('timestamp', $reading['timestamp']);	// hardcoded data fields for performance optimization
				$xmlReading->setAttribute('value', $reading['value']);
				$xmlReading->setAttribute('count', $reading['count']);
				
				$xmlData->appendChild($xmlReading);
			}
			
			$xmlChannel->appendChild($xmlData);
		}
			
		$this->xmlChannels->appendChild($xmlChannel);
	}
		
	public function addUser(User $obj) {
		$xmlUser = $this->xmlDoc->createElement('user');
		$xmlUser->setAttribute('id', (int) $obj->id);
		$xmlUser->appendChild($this->xmlDoc->createElement('uuid', $obj->uuid));
			
		$this->xmlUsers->appendChild($xmlUser);
	}

	public function addGroup(Group $obj) {
		$xmlGroup = $this->xmlDoc->createElement('group');
		$xmlGroup->setAttribute('id', (int) $obj->id);
		$xmlGroup->appendChild($this->xmlDoc->createElement('ugid', $obj->uuid));
		$xmlGroup->appendChild($this->xmlDoc->createElement('description', $obj->description));
			
		// TODO include sub groups?
			
		$this->xmlGroups->appendChild($xmlGroup);
	}

	public function render() {
		$this->xml->appendChild($this->xmlDoc->createElement('time', $this->getTime()));
		
		// channels
		if ($this->xmlChannels->hasChildNodes()) {
			$this->xml->appendChild($this->xmlChannels);
		}
		
		// users
		if ($this->xmlUsers->hasChildNodes()) {
			$this->xml->appendChild($this->xmlUsers);
		}
		
		// groups
		if ($this->xmlGroups->hasChildNodes()) {
			$this->xml->appendChild($this->xmlGroups);
		}
		
		$this->xmlDoc->appendChild($this->xml);
		echo $this->xmlDoc->saveXML();
	}

	protected function addException(Exception $exception) {
		$xmlException = $this->xmlDoc->createElement('exception');
		$xmlException->setAttribute('code', $exception->getCode());
		$xmlException->appendChild($this->xmlDoc->createElement('message', $exception->getMessage()));
		$xmlException->appendChild($this->xmlDoc->createElement('line', $exception->getLine()));
		$xmlException->appendChild($this->xmlDoc->createElement('file', $exception->getFile()));
		$xmlException->appendChild($this->fromTrace($exception->getTrace()));

		$this->xml->appendChild($xmlException);
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