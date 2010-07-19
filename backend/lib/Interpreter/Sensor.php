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

namespace Volkszaehler\Interpreter;

class Sensor extends Interpreter {
	
	public function getValues($from = NULL, $to = NULL, $groupBy = NULL) {
		$data = parent::getData($from, $to, $groupBy);
		
		array_walk($data, function(&$reading) {
			$reading['value'] /= $reading['count'];	// calculate average (ungroup the sql sum() function)
		});
		
		return $data;
	}

	public function getMin($from = NULL, $to = NULL) {	// TODO untested
		return $this->dbh->query('SELECT value, timestamp FROM data WHERE channel_id = ' . (int) $this->id . self::buildFilterTime($from, $to) . ' ORDER BY value ASC', 1)->current();
	}
	
	public function getMax($from = NULL, $to = NULL) {	// TODO untested
		return $this->dbh->query('SELECT value, timestamp FROM data WHERE channel_id = ' . (int) $this->id . self::buildFilterTime($from, $to) . ' ORDER BY value DESC', 1)->current();
	}
	
	public function getAverage($from = NULL, $to = NULL) {	// TODO untested
		return $this->dbh->query('SELECT AVG(value) AS value FROM data WHERE channel_id = ' . (int) $this->id . self::buildFilterTime($from, $to))->current();
	}
}

?>