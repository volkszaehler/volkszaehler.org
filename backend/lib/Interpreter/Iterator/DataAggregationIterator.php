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

use Doctrine\DBAL;

/**
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 */
class DataAggregationIterator extends DataIterator {
	protected $packageSize;		// count of readings in tuple
	protected $aggregatedSize;	// total readings
	protected $aggregatedKey = -1;

	/**
	 * Constructor
	 *
	 * @param \PDOStatement $stmt
	 * @param unknown_type $size
	 * @param unknown_type $tuples
	 */
	public function __construct(\PDOStatement  $stmt, $size, $tuples) {
		parent::__construct($stmt, $size);

		if ($tuples < $this->size) {						// return $tuples values
			$this->packageSize = floor($this->size / $tuples);
			$this->aggregatedSize = $tuples;
		}
		else {												// return all values or grouped by year, month, week...
			$this->packageSize = 1;
			$this->aggregatedSize = $this->size;
		}
	}

	/**
	 * Aggregate data
	 */
	public function next() {
		$current = array (0, 0);

		for ($c = 0; $c < $this->packageSize; $c++) {
			parent::next();

			if (parent::valid()) {
				$tuple = parent::current();
				$current[1] += $tuple[1];
			}
			else {
				$this->current = FALSE;
				return;
			}
		}

		$this->aggregatedKey++;
		$this->current = $current;
		$this->current[0] = $tuple[0];				// the last timestamp of a package
		$this->current[2] = $this->packageSize;		// how many pulses do we have aggregated? how accurate is our result?
	}

	/**
	 * @return array with data
	 */
	public function current() {
		return $this->current;
	}

	/**
	 * @return integer the nth data row
	 */
	public function key() {
		return $this->aggregatedKey;
	}

	/**
	 * Rewind the iterator
	 *
	 * Should only be called once
	 * PDOStatements doest support rewind()
	 */
	public function rewind() {
		parent::rewind();

		$offset = $this->size - 1 - $this->aggregatedSize * $this->packageSize;
		for ($i = 0; $i < $offset; $i++) {
			parent::next();
		}
		$this->next();
	}

	/**
	 * Getter & setter
	 */
	public function getPackageSize() { return $this->packageSize; }
}

?>