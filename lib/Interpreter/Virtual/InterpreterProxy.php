<?php
/**
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
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

use Volkszaehler\Interpreter\Interpreter;
use Volkszaehler\Model;

/**
 * InterpreterProxy wraps an interpreter to give direct access to
 * the implicitly defined iterator methods
 */
class InterpreterProxy implements \IteratorAggregate {

	const STRATEGY_TS_BEFORE = 1;	// states
	const STRATEGY_TS_AFTER = 2;	// steps & lines

	private $strategy = self::STRATEGY_TS_AFTER;

	/**
	 * @var Interpreter
	 */
	private $interpreter;

	/**
	 * @var LookbackIterator
	 */
	private $iterator;

	function __construct(Interpreter $interpreter) {
		$this->interpreter = $interpreter;
	}

	/**
	 * Wrap underlying iterator to give access to previous tuple
	 */
	public function getIterator() {
		if ($this->iterator == null) {
			$this->iterator = new LookbackIterator($this->interpreter->getIterator());
		}
		return $this->iterator;
	}

	/**
	 * Set value evaluation strategy
	 */
	public function setStrategy($strategy) {
		$this->strategy = $strategy;
	}

	/**
	 * Take entity line style into consideration for
	 * how timestamps need be interpreted
	 */
	public function setStrategyByEntityType(Model\Entity $entity) {
		if (!$entity->hasProperty('style')) {
			// default strategy
			return;
		}

		if ($lineStyle = $entity->getProperty('style')) {
			switch ($lineStyle) {
				case 'steps':
				case 'lines':
					// only use values of timestamps >= current
					$this->strategy = self::STRATEGY_TS_AFTER;
					break;
				default:
					// only use values of timestamps < current
					// @TODO check if < is enforced or <=
					$this->strategy = self::STRATEGY_TS_BEFORE;
			}
		}
	}

	/**
	 * Get current or previous interpreter value according to leading timestamp
	 */
	public function getValueForTimestamp($ts) {
		$previous = $this->iterator->previous();
		$current = $this->iterator->current();

		switch ($this->strategy) {
			case self::STRATEGY_TS_AFTER:
				// use previous timestamp if already _after_ $ts
				if ($previous[0] >= $ts || !$this->iterator->valid())
					$tuple = &$previous;
				else
					$tuple = &$current;
				break;

			case self::STRATEGY_TS_BEFORE:
				// use current timestamp if still _before_ $ts
				if ($this->iterator->valid() && $current[0] < $ts)
					$tuple = &$current;
				else
					$tuple = &$previous;
				break;

			default:
				throw new \Exception('Not implemented');
		}

		return $tuple[1];
	}

	/**
	 * Expose interpreter functions
	 */
	public function __call($name, $arguments) {
		return call_user_func_array(array($this->interpreter, $name), $arguments);
	}
}

?>
