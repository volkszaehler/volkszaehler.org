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

use Volkszaehler\Interpreter\Interpreter;
use Volkszaehler\Model;

/**
 * InterpreterProxy wraps an interpreter to give direct access to
 * the implicitly defined iterator methods
 */
class InterpreterProxy implements \IteratorAggregate {

	const STRATEGY_TS_BEFORE = 1;	// states
	const STRATEGY_TS_AFTER = 2;	// steps
	const STRATEGY_TS_BEST = 3;		// lines

	private $strategy = self::STRATEGY_TS_BEFORE;

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

	public function setStrategy($strategy) {
		$this->strategy = $strategy;
	}

	/**
	 * Take entity line style into consideration for
	 * how timestamps need be interpreted
	 */
	public function setStrategyByEntityType(Model\Entity $entity) {
		if (!$entity->hasProperty('style')) {
			// assume STRATEGY_BEST which is the default
			return;
		}

		if ($lineStyle = $entity->getProperty('style')) {
			switch ($lineStyle) {
				case 'states':
					// only use values of timestamps < current
					// @TODO check if < is enforced or <=
					$this->strategy = self::STRATEGY_TS_BEFORE;
					break;
				case 'steps':
					// only use values of timestamps >= current
					$this->strategy = self::STRATEGY_TS_AFTER;
					break;
			}
		}
	}

	/*
	 * Proxied results
	 */

	public function getTimestamp() {
		throw new \Exception("Not implemented");
	}

	public function getValueForTimestamp($ts) {
		$previous = $this->iterator->previous();
		$current = $this->iterator->current();

		switch ($this->strategy) {
			case self::STRATEGY_TS_AFTER:
				return $current[1];
				break;
			default:
				// STRATEGY_TS_BEFORE
				return $previous[1];
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
