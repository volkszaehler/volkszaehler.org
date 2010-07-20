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

class Channel extends Xml {
	
	public function __construct(\Volkszaehler\View\Http\Request $request, \Volkszaehler\View\Http\Response $response) {
		parent::__construct($request, $response);
			
		$this->xml = $this->xmlDoc->createElement('channels');
	}
	
	public function add(\Volkszaehler\Model\Channel $obj, array $data = NULL) {
		$xmlChannel = $this->xmlDoc->createElement('channel');
		$xmlChannel->setAttribute('uuid', $obj->getUuid());
		
		$xmlChannel->appendChild($this->xmlDoc->createElement('indicator', $obj->getIndicator()));
		$xmlChannel->appendChild($this->xmlDoc->createElement('unit', $obj->getUnit()));
		$xmlChannel->appendChild($this->xmlDoc->createElement('name', $obj->getName()));
		$xmlChannel->appendChild($this->xmlDoc->createElement('description', $obj->getDescription()));
		$xmlChannel->appendChild($this->xmlDoc->createElement('resolution', (int) $obj->getResolution()));
		$xmlChannel->appendChild($this->xmlDoc->createElement('cost', (float) $obj->getCost()));
		
		if (isset($data)) {
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
			
		$this->xml->appendChild($xmlChannel);
	}
	
	public function render() {
		$this->xmlRoot->appendChild($this->xml);
		
		parent::render();
	}
}