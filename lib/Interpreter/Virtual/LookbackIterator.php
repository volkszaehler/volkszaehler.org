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
 * Helper iterator that allows accessing previous value
 */
class LookbackIterator extends \IteratorIterator {

	protected $previous;
	protected $current;

	/*
	 * IteratorIterator
	 */

	function rewind() : void {
		$this->previous = array(0, 0, 0);
		parent::rewind();
	}

	function next() : void {
		$this->previous = $this->current();
		parent::next();
	}

	/*
	 * Lookback access
	 */

	function previous() {
		return $this->previous;
	}
}

?>
