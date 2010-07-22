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

/**
 * XML group view
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class XMLGroupView extends XMLView {
	protected $xml;

	public function __construct(HTTP\Request $request, HTTP\Request $response) {
		parent::__construct($request, $response);

		$this->xml = $this->xmlDoc->createElement('groups');
	}

	public function add(\Volkszaehler\Model\Group $obj) {
		$xmlGroup = $this->xmlDoc->createElement('group');
		$xmlGroup->setAttribute('uuid', $obj->getUuid());
		$xmlGroup->appendChild($this->xmlDoc->createElement('name', $obj->getName()));
		$xmlGroup->appendChild($this->xmlDoc->createElement('description', $obj->getDescription()));

		// TODO include sub groups?

		$this->xml->appendChild($xmlGroup);
	}

	public function render() {
		$this->xmlRoot->appendChild($this->xml);

		parent::render();
	}
}

?>
