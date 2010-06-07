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

	public function __construct(View $view) {
		parent::__construct($view);
	}

	public function execute() {
		switch ($this->view->request->get['action']) {
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
		if ($this->view->request->get['data'] == 'channels' || $this->view->request->get['data'] == 'pulses') {
			$this->view->type = 'channels';
			$this->view->channels = array();
				
			if ($this->view->request->get['data'] == 'channels') {			// get all channels assigned to user
				$user = current(User::getByFilter(array('id' => 1)));		// TODO replace by authentication or session handling
				$channels = $user->getChannels();
			}
			else {
				$ids = explode(',', trim($this->view->request->get['ids']));
				$channels = Channel::getByFilter(array('id' => $ids), true, false);	// get all channels with id in $ids as an array
				
				$from = (isset($this->view->request->get['from'])) ? (int) $this->view->request->get['from'] : NULL;
				$to = (isset($this->view->request->get['to'])) ? (int) $this->view->request->get['to'] : NULL;
				$groupBy = (isset($this->view->request->get['groupby'])) ? $this->view->request->get['groupby'] : 400;
				
				$this->view->from = $from;	// TODO use min max timestamps from Channel::getData()
				$this->view->to = $to;
			}
			
			$jsonChannels = array();
			foreach ($channels as $channel) {
				$jsonChannel = $channel->toJson();		// TODO fix hardcoded json output

				if ($this->view->request->get['data'] == 'pulses') {
					$this->view->type = 'pulses';
					$jsonChannel['pulses'] = array();
					
					foreach ($channel->getPulses($from, $to, $groupBy) as $pulse) {
						$jsonChannel['pulses'][] = array($pulse['timestamp'], $pulse['value']);
					}
				}

				$jsonChannels[] = $jsonChannel;
			}
			
			$this->view->channels = $jsonChannels;
		}
	}
	
	private function log() {
		$ucid = $this->view->request->get['ucid'];
		
		$channel = Channel::getByUcid($ucid);
		
		if (!($channel instanceof Channel)) {	// TODO rework
			$channel = Channel::addChannel($ucid);
		}
		
		$channel->addData($this->view->request->get);
	}
	
	public function add($ucid) {		// TODO rework
		$channel = new Channel();
		$channel->ucid = $ucid;

		if (substr($channel->ucid, 0, 19) == OneWireSensor::$ucidPrefix) {
			$channel->type = 'OneWireSensor';
			$channel->description = OneWireSensor::getFamilyDescription($channel);
		}
		else {
			$channel->type = 'Channel';
		}

		$channel->save();
	}
}

?>