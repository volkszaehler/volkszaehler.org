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

use Volkszaehler\Interpreter\Interpreter;

/**
 * InterpreterCoordinator provides timestamp coordination
 * and value access across multiple interpreters
 */
trait InterpreterCoordinatorTrait {

	protected $interpreters;
	protected $timestampGenerator;

	protected function setupCoordinator() {
		$this->interpreters = array();
		$this->timestampGenerator = new TimestampGenerator();
	}

	protected function addCoordinatedInterpreter($key, Interpreter $interpreter) {
		// __construct
		if (!isset($this->interpreters)) {
			$this->setupCoordinator();
		}

		// timestamp strategy mode
		$proxy = new InterpreterProxy($interpreter);
		if ($this->groupBy)
			$proxy->setStrategy(InterpreterProxy::STRATEGY_TS_BEFORE);
		else
			$proxy->setStrategyByEntityType($interpreter->getEntity());
		$this->interpreters[$key] = $proxy;

		// add timestamp iterator to generator
		$iterator = new TimestampIterator($proxy->getIterator());
		$this->timestampGenerator->add($iterator);
	}

	public function getCoordinatedInterpreter($key) {
		if (!isset($this->interpreters[$key])) {
			throw new \Exception('No coordinated interpreter ' . $key);
		}

		$interpreter = $this->interpreters[$key];
		return $interpreter;
	}

	public function getTimestampGenerator() {
		$generator = $this->timestampGenerator;

		// timestamps must be consolidated by period
		if ($this->groupBy) {
			$generator = new GroupedTimestampIterator($generator, $this->groupBy);
		}

		return $generator;
	}

	public function getCoordinatedFrom() {
		$from = null;

		// create first timestmap as min from interpreters
		foreach ($this->interpreters as $interpreter) {
			$value = $interpreter->getFrom();
			$from = min($from ?? $value, $value);
		}

		return $from;
	}
}

?>
