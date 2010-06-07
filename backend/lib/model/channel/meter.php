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

abstract class Meter extends Channel {
	
	public function getData($from = NULL, $to = NULL, $groupBy = NULL) {
		return parent::getData($from, $to, $groupBy);
	}
	
	public function getConsumption($from = NULL, $to = NULL) {	// TODO untested
		$sql = 'SELECT SUM(value) AS count
				FROM data
				WHERE
					channel_id = ' . (int) $this->id . ' &&
					' . $this->getTimeFilter($from, $to) . '
				GROUP BY channel_id';

		$result = $this->dbh->query($sql)->rewind();

		return $result['count'] / $this->resolution;
	}
	
	private function getDerivative($diffrence, $count = 1) {
		return ($count / $this->resolution) / $diffrence;
	}
	
	public function getMin($from = NULL, $to = NULL) {	// TODO fix draft
		return array();
			$result = $this->getData($from, $to);	// get all pulses
		$ts = 0;
		
		foreach ($result as $pulse) {
			$last = $new;
			$new = $pulse['timestamp'];
			
			if ($last - $new < $return['diffrence']) {
				$return['diffrence'] = $last - $new;
				$return['count'] = $pulse['value'];
				$return['timestamp'] = $new + $return['diffrence'] / 2;	// calculate new ts between $ts and $lastTs
			}
		}
		
		$return['value'] = $this->getDerivative($return['diffrence'], $return['count']);
		return $return;
	}
	
	public function getMax($from = NULL, $to = NULL) {	// TODO fix draft
			return array();
	
	}
	
	public function getAverage($from = NULL, $to = NULL) {	// TODO fix draft
		return array();
	
		$result = $this->getData($from, $to);	// get all pulses
		$sum = $count = 0;
		
		foreach ($result as $pulse) {
			$sum += $pulse['value'];
		}
		
		$return['value'] = $this->getDerivative(0, $sum);
		
		return $return;
	}
	
	/*
	 * just a passthru of raw data
	 */
	public function getPulses($from = NULL, $to = NULL, $groupBy = NULL) {
		return parent::getData($from, $to, $groupBy);
	}
}