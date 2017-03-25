<?php
/**
 * @copyright Copyright (c) 2017, The volkszaehler.org project
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @package default
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
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

class IteratorCollection {

	private $iterators = array();
	private $depleted = array();

	public function add($key, \Iterator $iterator) {
		$this->iterators[$key] = $iterator;
	}

	public function get($key) {
		if (null === $iterator = $this->iterators[$key]) {
			$iterator = $this->depleted[$key];
		}
		return $iterator;
	}

	public function timestamps() {
		foreach ($this->iterators as $iterator) {
			$iterator->rewind();
		}

		// get minimum from timestamp
		$from = array_reduce($this->iterators, function($carry, $iterator) {
			$current = $iterator->current();
			if ($carry === null || $current < $carry)
				return $current;
			return $carry;
		});

		while (true) {
			// remove invalid iterators
			foreach ($this->iterators as $key => $iterator) {
				if (!$iterator->valid()) {
					// remove depleted iterator
					$this->depleted[$key] = $this->iterators[$key];
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

			$timestamps = array_map(function($iterator) {
				return $iterator->current();
			}, $this->iterators);

			// yield
			yield $ts;

			// move consumed iterators
			foreach ($this->iterators as $key => $iterator) {
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
