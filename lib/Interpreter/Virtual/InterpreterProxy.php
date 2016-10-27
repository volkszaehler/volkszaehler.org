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

	protected $interpreter;			// wrapped Interpreter instance
	protected $iterator;			// interpreter's tuple iterator

	const MODE_BEST = -1;			// best match
	const MODE_BEFORE = 0;			// match up to timestamp

	protected $matchMode;

	protected $passthrough;			// true indicates timestamps will be used as they are
	protected $current;				// tuple for matching iterator timestamp
	protected $delta;				// timestamp delta for current tuple

	const STATE_INITIAL = 0;		// no valid tuple
	const STATE_VALID = 10;			// valid tuple
	const STATE_USE_CURRENT = 20;	// current tuple matches
	const STATE_USE_PREVIOUS = 30;	// previous tuple matches

	protected $state;

	/**
	 * Constructor
	 *
	 * @param Interpreter $interpreter
	 */
	public function __construct(Interpreter $interpreter, $passthrough = false) {
		$this->interpreter = $interpreter;
		$this->passthrough = $passthrough;
		$this->matchMode = InterpreterProxy::MODE_BEST;

		// first result if no matching tuple exists
		$this->current = array(0, 0, 0);
	}

	public function setMatchMode($matchMode) {
		return $this->matchMode = $matchMode;
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
	public function advanceIteratorToTimestamp($ts) {
		$iterator = $this->getIterator();

		$this->state = InterpreterProxy::STATE_INITIAL;

		while ($iterator->valid()) {
			$this->lastCurrent = $this->current;
			$this->current = $iterator->current();

			$this->lastDelta = $this->delta;
			$this->delta = abs($this->current[0] - $ts);

			// printf("* %d %d (%s)\n", $this->current[0], $this->delta, $this->lastDelta);

			if ($this->delta === 0) {
				$this->state = InterpreterProxy::STATE_USE_CURRENT;
				return;
			}

			// MODE_BEFORE or before delta: timestamp > target + delta
			if ($this->matchMode >= InterpreterProxy::MODE_BEFORE && ($this->current[0] > $ts + $this->matchMode)) {
				// printf("b >>\n");
				$this->state = InterpreterProxy::STATE_USE_PREVIOUS;
				return;
			}

			// MODE_BEST: delta getting larger
			if ($this->state !== InterpreterProxy::STATE_INITIAL && ($this->delta > $this->lastDelta)) {
				// printf("* >>\n");
				$this->state = InterpreterProxy::STATE_USE_PREVIOUS;
				return;
			}

			$iterator->next();
			$this->state = InterpreterProxy::STATE_VALID;
		}

		// printf("* <<\n");
		$this->state = InterpreterProxy::STATE_USE_CURRENT;
	}

	/**
	 * Get current tuple from iterator
	 */
	public function current() {
		if ($this->passthrough) {
			return $this->getIterator()->current();
		}

		if ($this->state == InterpreterProxy::STATE_USE_CURRENT) {
			// printf("o >>\n");
			return $this->current;
		}
		elseif ($this->state == InterpreterProxy::STATE_USE_PREVIOUS) {
			// printf("o <<\n");
			return $this->lastCurrent;
		}
		else {
			throw new \LogicException('Invalid InterpreterProxy state');
		}
	}

	/**
	 * Expose interpreter functions
	 */
	public function __call($name, $arguments) {
		return call_user_func_array(array($this->interpreter, $name), $arguments);
	}
}

?>
