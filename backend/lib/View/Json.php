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

namespace Volkszaehler\View;

use Volkszaehler\Util;

class Json extends View {
	protected $json = array();

	/*
	 * constructor
	 */
	public function __construct(Http\Request $request, Http\Response $response) {
		parent::__construct($request, $response);

		$this->json['source'] = 'volkszaehler.org';
		$this->json['version'] = \Volkszaehler\VERSION;
		
		$this->response->setHeader('Content-type', 'application/json');
	}

	public function render() {
		parent::render();
		
		echo json_encode($this->json);
	}
	
	protected function addDebug() {
		$config = Util\Registry::get('config');
		
		$this->json['debug'] = array('time' => $this->getTime(),
										'database' => array('driver' => $config['db']['driver'],
																'queries' => Util\Debug::getSQLLogger()->queries)
									);
		
	}

	protected function addException(\Exception $exception) {
		$this->json['exception'] = array('type' => get_class($exception),
										'message' => $exception->getMessage(),
										'code' => $exception->getCode(),
										'file' => $exception->getFile(),
										'line' => $exception->getLine(),
										'trace' => $exception->getTrace()
		);
	}
}

?>