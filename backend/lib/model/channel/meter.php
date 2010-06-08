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
	
	public function getMin($from = NULL, $to = NULL) {
		$data = $this->getData($from, $to);
		
		$min = current($data);
		foreach ($data as $reading) {
			if ($reading['value '] < $min['value']) {
				$min = $reading;
			}
		}
		return $min;
	}
	
	public function getMax($from = NULL, $to = NULL) {
		$data = $this->getData($from, $to);
		
		$min = current($data);
		foreach ($data as $reading) {
			if ($reading['value '] > $min['value']) {
				$min = $reading;
			}
		}
		return $min;
	}
	
	public function getAverage($from, $to) {
		return $this->getConsumption($from, $to) / ($to - $from) * 1000;
	}
	
	/*
	 * just a passthru of raw data
	 */
	public function getPulses($from = NULL, $to = NULL, $groupBy = NULL) {
		return parent::getData($from, $to, $groupBy);
	}
	
	/*
	 * raw pulses to power conversion
	 */
	public function getData($from = NULL, $to = NULL, $groupBy = NULL) {
		$pulses = parent::getData($from, $to, $groupBy);
		$pulseCount = count($pulses);
		
		for ($i = 1; $i < $pulseCount; $i++) {
			$delta = $pulses[$i]['timestamp'] - $pulses[$i-1]['timestamp'];
			
			$pulses[$i]['timestamp'] -= $delta/2;
			$pulses[$i]['value'] *= 3600000/(($this->resolution / 1000) * $delta);
		}
		
		return $pulses;
	}
}