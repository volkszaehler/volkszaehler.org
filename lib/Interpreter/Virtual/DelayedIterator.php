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
 * Helper iterator that allows peeking at the next value
 */
class DelayedIterator extends \IteratorIterator
{
	protected $valid;
	protected $key;
	protected $current;

	public function peek() {
		return parent::current();
	}

	/*
	 * Iterator
	 */

	public function rewind() : void {
		parent::rewind();
		$this->next();
	}

	public function next() : void {
		$this->valid = parent::valid();
		if ($this->valid) {
			$this->key = parent::key();
			$this->current = parent::current();
			parent::next();
		}
	}

	public function valid() : bool {
		return $this->valid;
	}

	#[\ReturnTypeWillChange]
	public function key() {
		return $this->key;
	}

	#[\ReturnTypeWillChange]
	public function current() {
		return $this->current;
	}
}

?>
