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

namespace Volkszaehler\Interpreter\Iterator;

use Volkszaehler\Util;

use Doctrine\DBAL;

/**
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class DataAggregationIterator implements \Iterator, \Countable {
	protected $current;
	protected $key;			// key
	protected $size;		// total readings in PDOStatement
	protected $iterator;	// subiterator

	/**
	 * Constructor
	 *
	 * @param \PDOStatement $stmt
	 * @param integer $size
	 * @param integer $tuples
	 */
	public function __construct(\PDOStatement $stmt, $size, $tuples) {
		$this->iterator = new DataIterator($stmt, $size);

		$this->packageSize = floor($size / $tuples);
		$this->size = (int) $tuples;
	}

	/**
	 * Aggregate data
	 */
	public function next() {
		$current = array(0, 0, 0);
		for ($i = 0; $i < $this->packageSize && $this->iterator->valid(); $i++, $this->iterator->next()) {
			$tuple = $this->iterator->current();

			$current[0] = $tuple[0];
			$current[1] += $tuple[1];
			$current[2] += $tuple[2];
		}

		$this->key++;
		return $this->current = $current;
	}

	public function rewind() {
		$this->iterator->rewind();
		// skip first readings to get an even divisor
		$skip = count($this->iterator) - count($this) * $this->packageSize;
		for ($i = 0; $i < $skip; $i++) {
			$this->iterator->next();
		}
		return $this->next();
	}

	public function valid() {
		return $this->key <= $this->size;
	}

	/**
	 * Getter & setter
	 */
	public function getPackageSize() { return $this->packageSize; }
	public function count() { return $this->size; }
	public function key() { return $this->key; }
	public function current() { return $this->current; }
}

?>
