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

class DataController extends Controller {
	public function add() {
		$ucid = $this->view->request->get['ucid'];

		$channel = Channel::getByUcid($ucid);

		// TODO add channel if it doesn't exist (use $this->add)

		$channel->addData($this->view->request->get);
	}

	public function get() {
		$ids = explode(',', trim($this->view->request->get['ids']));
		$channels = Channel::getByFilter(array('id' => $ids), true, false);	// get all channels with id in $ids as an array

		$from = (isset($this->view->request->get['from'])) ? (int) $this->view->request->get['from'] : NULL;
		$to = (isset($this->view->request->get['to'])) ? (int) $this->view->request->get['to'] : NULL;
		$groupBy = (isset($this->view->request->get['groupby'])) ? $this->view->request->get['groupby'] : 400;		// get all readings by default

		$data['from'] = $from;	// TODO use min max timestamps from Channel::getData()
		$data['to'] = $to;
			
		$jsonChannels = array();
		foreach ($channels as $channel) {
			$jsonChannel = $this->view->getChannel($channel);

			$jsonChannel['readings'] = array();
				
			foreach ($channel->getPulses($from, $to, $groupBy) as $reading) {
				$jsonChannel['readings'][] = array($reading['timestamp'], $reading['value'], $reading['count']);
			}

			$data['channels'][] = $jsonChannel;
		}
		$this->view->data += $data;
	}
}