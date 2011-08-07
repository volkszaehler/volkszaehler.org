<?php
/**
 * @package default
 * @copyright Copyright (c) 2011, The volkszaehler.org project
 * @license http://www.gnu.org/licenses/gpl.txt GNU Public License
 */
/*
 * This file is part of volkzaehler.org
 *
 * volkzaehler.org is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * volkzaehler.org is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Volkszaehler\Interpreter;

use Volkszaehler\Util;
use Doctrine\DBAL;

/**
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class DataIterator implements \Iterator, \Countable {
	protected $stmt;	// PDO statement

	protected $current;	// the current data
	protected $key;		// key
	protected $rowKey;	// internal key for PDO statement
	
	protected $rowCount;	// num of readings in PDOStatement
	protected $tupleCount;	// num of requested tuples
	protected $packageSize; // num of rows we aggregate in each tuple
	
	protected $from;	// exact timestamps based on on query results
	protected $to;		// from/to of Interpreter are based on the request parameters!

	/**
	 * Constructor
	 *
	 * @param \PDOStatement $stmt
	 * @param integer $rowCount total num of rows in $stmt
	 * @param integer $tupleCount set to NULL to get all rows
	 */
	public function __construct(\PDOStatement $stmt, $rowCount, $tupleCount) {
		$this->rowCount = $rowCount;
		$this->tupleCount = $tupleCount;
	
		$this->stmt = $stmt;
		$this->stmt->setFetchMode(\PDO::FETCH_NUM);

		if (empty($this->tupleCount) || $this->rowCount < $this->tupleCount) { // get all rows
			 $this->packageSize = 1;
			 $this->tupleCount = $this->rowCount;
		}
		else { // sumarize
			$this->packageSize = floor($this->rowCount / $this->tupleCount);
			$this->tupleCount = floor($this->rowCount / $this->packageSize);
			
			if (fmod($this->rowCount, $this->packageSize) > 0) {
				$this->tupleCount++;
			}
		}
	}

	/**
	 * Aggregate data
	 * @return next aggregated tuple
	 */
	public function next() {
		$package = array(0, 0, 0);
		for ($i = 0; $i < $this->packageSize && $tuple = $this->stmt->fetch(); $i++) {
			$package[0] = $tuple[0]; // use last timestamp in package
			$package[1] += $tuple[1];
			$package[2] += $tuple[2];
			
			$this->rowKey++;
		}
		
		$this->key++;
		
		if ($package[2]) $this->to = $package[0];
		
		return $this->current = $package;
	}

	/**
	 * Rewind the iterator
	 *
	 * @internal Should only be called once: PDOStatement hasn't a rewind()
	 */
	public function rewind() {
		$this->key = $this->rowKey = 0;
		$this->from = $this->stmt->fetchColumn(); // skipping first reading, just for getting first timestamp
		return $this->next(); // fetch first tuple
	}

	public function valid() {
		return ($this->current[2] > 0); // current package contains at least 1 tuple
	}

	/**
	 * Getter & setter
	 */
	public function getPackageSize() { return $this->packageSize; }
	public function getFrom() { return $this->from; }
	public function getTo() { return $this->to; }
	
	public function count() { return $this->tupleCount; }
	public function key() { return $this->key; }
	public function current() { return $this->current; }
}

?>
