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

namespace Volkszaehler;

/**
 * @author Steffen Vogel <info@steffenvogel.de>
 */
use Doctrine\DBAL;

class DataAggregationIterator extends DataIterator {
	protected $packageSize;		// count of readings in tuple
	protected $aggregatedSize;	// total readings
	protected $aggregatedKey = -1;

	public function __construct(DBAL\Statement $stmt, $size, $tuples) {
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
	 * aggregate data
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
		$this->current[0] = $tuple[0];
		$this->current[2] = $this->packageSize;
	}

	public function current() {
		return $this->current;
	}

	public function key() {
		return $this->aggregatedKey;
	}

	public function rewind() {
		parent::rewind();

		$offset = $this->size - 1 - $this->aggregatedSize * $this->packageSize;
		for ($i = 0; $i < $offset; $i++) {
			parent::next();
		}
		$this->next();
	}

	/**
	 * getter & setter
	 */
	public function getPackageSize() { return $this->packageSize; }
}

?>