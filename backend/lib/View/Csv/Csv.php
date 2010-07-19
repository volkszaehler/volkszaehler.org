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

class Csv extends \Volkszaehler\View\View {

	/*
	 * constructor
	 */
	public function __construct(Http\Request $request, Http\Response $response) {
		parent::__construct($request, $response);

		$this->csv['source'] = 'volkszaehler.org';
		$this->csv['version'] = \Volkszaehler\VERSION;

		$this->response->setHeader('Content-type', 'text/csv');
	}
	
	public function render() {
		parent::render();
		
		// TODO implement
	}
	
	public function addDebug() {
		// TODO implement debug output for csv view
	}
	
	public function addException(\Exception $exception) {
		// TODO implement exception output for csv view
	}
}

?>