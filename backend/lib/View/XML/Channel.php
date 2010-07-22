<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package channel
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

namespace Volkszaehler\View\XML;

use Volkszaehler\View\HTTP;

/**
 * XML channel view
 *
 * also used for data
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package channel
 */
class Channel extends XML {

	public function __construct(HTTP\Request $request, HTTP\Response $response) {
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

?>
