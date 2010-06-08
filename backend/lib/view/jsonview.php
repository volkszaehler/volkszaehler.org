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

class JsonView extends View {
	private $data = array();
	
	public function __construct(HttpRequest $request, HttpResponse $response) {
		parent::__construct($request, $response);
		
		$config = Registry::get('config');

		$this->source = 'volkszaehler.org';
		$this->version = VZ_VERSION;
		$this->storage = $config['db']['backend'];
		$this->controller = $request->get['controller'];
		$this->action = $request->get['action'];
		
		
		$this->response->headers['Content-type'] = 'application/json';
	}
	
	public function __set($key, $value) {
		$this->data[$key] = $value;
	}
	
	public function __get($key) {
		return $this->data[$key];
	}
	
	public function __isset($key) {
		return isset($this->data[$key]);
	}
	
	public function render() {
		$this->time = round(microtime(true) - $this->created, 4);
		echo json_encode($this->data);
	}
	
	public function exceptionHandler(Exception $exception) {
		$this->exception = array('message' => $exception->getMessage(),
									'code' => $exception->getCode(),
									'file' => $exception->getFile(),
									'line' => $exception->getLine(),
									'trace' => $exception->getTrace()
								);
		$this->render();
	}
	
	public function getChannel(Channel $channel) {		// TODO improve view interface
		return array('id' => (int) $channel->id,
						'ucid' => $channel->ucid,
						'resolution' => (int) $channel->resolution,
						'description' => $channel->description,
						'type' => $channel->type,
						'costs' => $channel->cost);
	}
}

?>