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
	public $data = array();

	public function __construct(HttpRequest $request, HttpResponse $response) {
		parent::__construct($request, $response);

		$config = Registry::get('config');

		$this->data['source'] = 'volkszaehler.org';
		$this->data['version'] = VZ_VERSION;
		$this->data['storage'] = $config['db']['backend'];
		$this->data['controller'] = $request->get['controller'];
		$this->data['action'] = $request->get['action'];

		$this->response->setHeader('Content-type', 'application/json');
	}

	public function render() {
		$this->data['time'] = round(microtime(true) - $this->created, 4);
		echo json_encode($this->data);
	}

	public function exceptionHandler(Exception $exception) {
		$this->data['exception'] = array('message' => $exception->getMessage(),
										'code' => $exception->getCode(),
										'file' => $exception->getFile(),
										'line' => $exception->getLine(),
										'trace' => $exception->getTrace()
		);
		$this->data['status'] = 'exception';
		$this->render();
		die();
	}

	public function addChannel(Channel $obj, $data = NULL) {
			$channel['id'] = (int) $obj->id;
			$channel['ucid'] = $obj->ucid;
			$channel['resolution'] = (int) $obj->resolution;
			$channel['description'] = $obj->description;
			$channel['type'] = $obj->type;
			$channel['costs'] = $obj->cost;
			
			// TODO check for optional data in second param
			if (!is_null($data) && is_array($data)) {
				$channel['data'][] = array();
				foreach ($data as $reading) {
					$channel['data'][] = array($reading['timestamp'], $reading['value'], $reading['count']);
				}
			}
			
			$this->data['channels'][] = $channel;
	}
			
	public function addUser(User $obj) {
			$user['id'] = (int) $obj->id;
			$user['uuid'] = $obj->uuid;
			
			$this->data['users'][] = $user;
	}
		
	public function addGroup(Group $obj) {
			$group['id'] = (int) $obj->id;
			$group['ugid'] = $obj->ugid;
			$group['description'] = $obj->description;
			
			// TODO include sub groups?
			
			$this->data['groups'][] = $group;
	}
			
	public function add($obj) {
		if (is_array($obj)) {
			array_merge($this->data, $obj);	// TODO check array_merge beavior with duplicate keys
		}
		else {
			$this->data[] = $obj;
		}
	}
}

?>