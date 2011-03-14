<?php
/**
 * @package default
 * @copyright Copyright (c) 2010, The volkszaehler.org project
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
	private $rowKey;	// internal key for PDO statement
	
	protected $rowCount;	// num of readings in PDOStatement
	protected $tupleCount;	// num of requested tuples
	protected $packageSize; // num of rows we aggregate in each tuple

	/**
	 * Constructor
	 *
	 * @param \PDOStatement $stmt
	 * @param integer $size
	 * @param integer $tuples
	 */
	public function __construct(\PDOStatement $stmt, $rowCount, $tupleCount) {
		$this->rowCount = $rowCount;
		$this->tupleCount = $tupleCount;
	
		$this->stmt = $stmt;
		$this->stmt->setFetchMode(\PDO::FETCH_NUM);

		if ($this->rowCount > $this->tupleCount) {
			$this->packageSize = floor($this->rowCount / $this->tupleCount);
			$this->tupleCount = floor($this->rowCount / $this->packageSize) + 1;
		}
		else {
			 $this->packageSize = 1;
		}
	}

	/**
	 * Aggregate data
	 */
	public function next() {
		$package = array(0, 0, 0);
		for ($i = 0; $i < $this->packageSize && $this->valid(); $i++) {
			$tuple = $this->stmt->fetch();

			$package[0] = $tuple[0];
			$package[1] += $tuple[1];
			$package[2] += $tuple[2];
			
			$this->rowKey++;
		}
		
		$this->key++;
		return $this->current = $package;
	}

	/**
	 * Rewind the iterator
	 *
	 * Should only be called once
	 * PDOStatement hasn't a rewind()
	 */
	public function rewind() {
		$this->key = $this->rowKey = 0;
	}

	public function valid() {
		return $this->rowKey < $this->rowCount;
	}

	/**
	 * Getter & setter
	 */
	public function getPackageSize() { return $this->packageSize; }
	public function count() { return $this->tupleCount; }
	public function key() { return $this->key; }
	public function current() { return $this->current; }
}

?>
