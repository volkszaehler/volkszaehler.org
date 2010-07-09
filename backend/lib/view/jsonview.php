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
	public $jsonData = array();

	public function __construct() {
		parent::__construct();

		$config = Registry::get('config');

		$this->jsonData['source'] = 'volkszaehler.org';
		$this->jsonData['version'] = VERSION;
		$this->jsonData['storage'] = $config['db']['backend'];
		$this->jsonData['controller'] = $request->get['controller'];
		$this->jsonData['action'] = $request->get['action'];

		$this->response->setHeader('Content-type', 'application/json');
	}

	public function render() {
		$this->jsonData['time'] = $this->getTime();
		echo json_encode($this->jsonData);
	}

	protected function addException(Exception $exception) {
		$this->jsonData['exception'] = array('message' => $exception->getMessage(),
										'code' => $exception->getCode(),
										'file' => $exception->getFile(),
										'line' => $exception->getLine(),
										'trace' => $exception->getTrace()
		);
	}

	public function addChannel(Channel $obj, $data = NULL) {
		$channel['id'] = (int) $obj->id;
		$channel['uuid'] = $obj->uuid;
		$channel['type'] = $obj->type;
		$channel['unit'] = $obj::unit;
		$channel['name'] = $obj->name;
		$channel['description'] = $obj->description;
		$channel['resolution'] = (int) $obj->resolution;
		$channel['cost'] = (float) $obj->cost;
			
		if (!is_null($data) && is_array($data)) {
			$channel['data'] = array();
			foreach ($data as $reading) {
				$channel['data'][] = array($reading['timestamp'], $reading['value'], $reading['count']);
			}
		}
			
		$this->jsonData['channels'][] = $channel;
	}
		
	public function addUser(User $obj) {
		$user['id'] = (int) $obj->id;
		$user['uuid'] = $obj->uuid;
			
		$this->jsonData['users'][] = $user;
	}

	public function addGroup(Group $obj, $recursive = false) {	// TODO fix this. how do we want to handly nested set structures?
		$group['id'] = (int) $obj->id;
		$group['uuid'] = $obj->uuid;
		$group['name'] = $obj->name;
		$group['description'] = $obj->description;
		
		$backtrace = array(&$group);
			
		if ($recursive) {
			$children = $obj->getChildren();

			foreach ($children as $child) {
				$subGroup['id'] = (int) $child->id;
				$subGroup['uuid'] = $child->uuid;
				$subGroup['name'] = $child->name;
				$subGroup['description'] = $child->description;
				
				if ($child->level > $lastLevel) {
					array_push(end($backtrace), $subGroup);
				//	array_push($backtrace, &$subgroup);	// TODO: Deprecated: Call-time pass-by-reference has been deprecated
				}
				elseif ($child->level < $lastLevel) {
					array_pop($backtrace);
					array_push(end($backtrace), $subGroup);
				}
				elseif ($child->level == $lastLevel) {
					array_push(end($backtrace), $subGroup);
				}
			}
		}
			
		$this->jsonData['groups'][] = $group;
	}
}

?>