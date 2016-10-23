<?php
/**
 * @copyright Copyright (c) 2016, The volkszaehler.org project
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

use Volkszaehler\Interpreter\Interpreter;
use Volkszaehler\Util;

/**
 * InterpreterProxy wraps an interpreter to give direct access to
 * the implicitly defined iterator methods
 */
class InterpreterProxy implements \IteratorAggregate {

	const MATCH = 1;

	protected $interpreter;		// wrapped Interpreter instance
	protected $iterator;		// interpreter's tuple iterator

	protected $passthrough;		// true indicates timestamps will be used as they are
	protected $current;			// tuple for matching iterator timestamp

	/**
	 * Constructor
	 *
	 * @param Interpreter $interpreter
	 */
	public function __construct(Interpreter $interpreter, $passthrough = false) {
		$this->interpreter = $interpreter;
		$this->passthrough = $passthrough;
	}

	/*
	 * IteratorAggregate
	 */
	public function getIterator() {
		if (null === $this->iterator) {
			$this->iterator = $this->interpreter->getIterator();
		}

		return $this->iterator;
	}

	/**
	 * Select matching tuple by moving iterator n tuples ahead
	 */
	public function advanceIteratorToTimestamp($ts, $strategy = InterpreterProxy::MATCH) {
		$iterator = $this->getIterator();

		while ($iterator->valid() && $this->current[0] <= $ts) {
			$this->current = $iterator->current();

			if ($this->current[0] == $ts) {
				return true;
			}
			elseif ($this->current[0] < $ts) {
				$iterator->next();
			}
			else /*if ($this->current[0] > $ts)*/ {
				$iterator->next();
			}
		}

		return false;
	}

	/**
	 * Get current tuple from iterator
	 */
	public function getTuple() {
		return $this->passthrough ? $this->getIterator()->current() : $this->current;
	}

	/**
	 * Expose interpreter functions
	 */
	public function __call($name, $arguments) {
		return call_user_func_array(array($this->interpreter, $name), $arguments);
	}
}

?>
