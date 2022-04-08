<?php
/**
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2020, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
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

namespace Volkszaehler\Interpreter\Virtual;

/**
 * TimestampGenerator yields sequence of timestamps by synchronously
 * moving a collection of underlying tuple iterators
 */
class TimestampGenerator implements \IteratorAggregate {

	private $iterators = array();

	public function add(\Iterator $iterator) {
		$this->iterators[] = $iterator;
	}

	/**
	 * Yield sequential timestamps from all iterators
	 */
	public function getIterator() {
		foreach ($this->iterators as $iterator) {
			$iterator->rewind();
		}

		// get minimum from timestamp
		$from = array_reduce($this->iterators, function($carry, $iterator) {
			if (!$iterator->valid())
				return $carry;
			$current = $iterator->current();
			if ($carry === null || $current < $carry)
				return $current;
			return $carry;
		});

		while (true) {
			// remove invalid iterators
			foreach ($this->iterators as $key => $iterator) {
				if (!$iterator->valid()) {
					unset($this->iterators[$key]);

					// stop if no iterators left
					if (count($this->iterators) === 0)
						return;
				}
			}

			// get minimum current timestamp
			$ts = array_reduce($this->iterators, function($carry, $iterator) {
				$current = $iterator->current();
				if ($carry === null || $current < $carry)
					return $current;
				return $carry;
			});

			// all timestamps >= $ts

			// yield
			yield $ts;

			// move consumed iterators
			foreach ($this->iterators as $iterator) {
				// advance all iterators that are at current timestamp
				if ($iterator->current() <= $ts) {
					$iterator->next();
				}
			}

			// all timestamps > $ts
		}

		// catch all - iterators empty
		yield new \EmptyIterator();
	}
}

?>
