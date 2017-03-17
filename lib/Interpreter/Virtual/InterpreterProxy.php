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

	const MODE_AFTER = -2;			// match starting at timestamp
	const MODE_BEST  = -1;			// best match
	const MODE_BEFORE = 0;			// match up to timestamp (must be highest value)

	protected $matchMode;

	protected $passthrough;			// true indicates timestamps will be used as they are
	protected $current;				// tuple for matching iterator timestamp
	protected $lastCurrent;			// tuple for last matching iterator timestamp
	protected $delta;				// timestamp delta for current tuple
	protected $lastDelta;			// timestamp delta for last tuple

	const STATE_INITIAL = 0;		// no valid tuple
	const STATE_USE_CURRENT = 10;	// current tuple matches
	const STATE_USE_PREVIOUS = 20;	// previous tuple matches

	protected $state;

	/**
	 * Constructor
	 *
	 * @param Interpreter $interpreter
	 */
	public function __construct(Interpreter $interpreter, $passthrough = false) {
		$this->interpreter = $interpreter;
		$this->passthrough = $passthrough;
		$this->matchMode = self::MODE_BEST;

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
			$this->state = self::STATE_INITIAL;
			$this->iterator = $this->interpreter->getIterator();
		}

		return $this->iterator;
	}

	/**
	 * Select matching tuple by moving iterator n tuples ahead
	 */
	public function advanceIteratorToTimestamp($ts) {
		$iterator = $this->getIterator();
// Util\Logger::debug(sprintf("ts  %d", $ts));

		while ($iterator->valid()) {
// printf("* last > curr %d > %d\n", $this->lastCurrent[0], $this->current[0]);
			if ($this->state == self::STATE_USE_CURRENT) {
				$this->lastCurrent = $this->current;
			}
			$this->current = $iterator->current();
// if ($this->lastCurrent) Util\Logger::debug("lst", $this->lastCurrent);
// Util\Logger::debug("cur", $this->current);

			$this->lastDelta = abs($this->lastCurrent[0] - $ts);
			$this->delta = abs($this->current[0] - $ts);

// Util\Logger::debug(sprintf("    %d %s %d",
// 	$this->lastDelta,
// 	($this->lastDelta < $this->delta) ? '<' : '>',
// 	$this->delta)
// );

// printf("* loop %d: %d > %d (%d > %d)\n", $this->state, $this->lastCurrent[0], $this->current[0], $this->lastDelta, $this->delta);

			// printf("* %d %d (%s)\n", $this->current[0], $this->delta, $this->lastDelta);

			if ($this->delta === 0) {
// printf("* use curr %d + next\n", $this->current[0]);
				// $iterator->next();
				$this->state = self::STATE_USE_CURRENT;
				return;
			}

			// MODE_AFTER: use current if timestamp > target
			if ($this->matchMode == self::MODE_AFTER && ($this->current[0] >= $ts)) {
// printf("* use curr %d + next\n", $this->current[0]);
				// $iterator->next();
				$this->state = self::STATE_USE_CURRENT;
				return;
			}

			// MODE_BEFORE or before delta: use previous if current timestamp > target + delta
			if ($this->matchMode >= self::MODE_BEFORE && ($this->current[0] > $ts + $this->matchMode)) {
				// printf("b >>\n");
				$this->state = self::STATE_USE_PREVIOUS;
				return;
			}

			// MODE_BEST: use previous if current delta > previous delta
			if ($this->state !== self::STATE_INITIAL && ($this->delta > $this->lastDelta)) {
				// printf("* >>\n");
// printf("* use prev %d\n", $this->lastCurrent[0]);
				$this->state = self::STATE_USE_PREVIOUS;
				return;
			}

// printf("* next\n");
			$iterator->next();
			$this->state = self::STATE_USE_CURRENT;
		}
	}

	/**
	 * Get current tuple from iterator
	 */
	public function current() {
		if ($this->passthrough) {
			return $this->getIterator()->current();
		}

		if ($this->state == self::STATE_USE_CURRENT) {
			// printf("o >>\n");
			return $this->current;
		}
		elseif ($this->state == self::STATE_USE_PREVIOUS) {
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
