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

class ChannelController extends Controller {
	private $jsonHeader = array();

	public function __construct(HttpRequest $request, HttpResponse $response) {
		parent::__construct($request, $response);

		$config = Registry::get('config');

		$this->jsonHeader = array('source' => 'volkszaehler.org',
									'version' => '0.1',
									'storage' => $config['db']['backend']);

		//$this->response->headers['Content-type'] = 'application/json'; // TODO uncomment in production use (just for debug)
	}

	public function process() {
		switch ($this->request->get['action']) {
			case 'get':
				$this->get();
				break;
				
			case 'log':
				$this->log();
				break;
				
			default:
				throw new InvalidArgumentException('Invalid action specified!');
		}
	}
	
	private function get() {
		$json = $this->jsonHeader;

		if ($this->request->get['data'] == 'channels' || $this->request->get['data'] == 'pulses') {
			$json['type'] = 'channels';
				
			if ($this->request->get['data'] == 'channels') {			// get all channels assigned to user
				$user = current(User::getByFilter(array('id' => 1)));
				$channels = $user->getChannels();
			}
			else {
				$ids = explode(',', trim($this->request->get['ids']));
				$channels = Channel::getByFilter(array('id' => $ids), true, false);	// get all channels with id in $ids as an array
				
				$from = (isset($this->request->get['from'])) ? (int) $this->request->get['from'] : NULL;
				$to = (isset($this->request->get['to'])) ? (int) $this->request->get['to'] : NULL;
				$groupBy = (isset($this->request->get['groupby'])) ? $this->request->get['groupby'] : 400;
				
				$json['from'] = $from;	// TODO use min max tiestamps from Channel::getData()
				$json['to'] =  $to;
			}
			
			foreach ($channels as $channel) {
				$jsonChannel = array('id' => (int) $channel->id,
										'resolution' => (int) $channel->resolution,
										'description' => $channel->description,
										'type' => $channel->type,
										'costs' => $channel->cost);

				if ($this->request->get['data'] == 'pulses') {
					$json['type'] = 'pulses';
					$jsonChannel['pulses'] = array();
					
					foreach ($channel->getPulses($from, $to, $groupBy) as $pulse) {
						$jsonChannel['pulses'][] = array($pulse['timestamp'], $pulse['value']);
					} 
				}

				$json['channels'][] = $jsonChannel;
			}
		}

		echo json_encode($json);
	}
	
	private function log() {
		$ucid = $this->request->get['ucid'];
		
		$channel = Channel::getByUcid($ucid);
		
		if (!($channel instanceof Channel)) {
			$channel = Channel::addChannel($ucid);
		}
		
		$channel->addData($this->request->get);
	}
}

?>