<?php
/**
 * @copyright Copyright (c) 2011, The volkszaehler.org project
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

namespace Volkszaehler\Interpreter;

use Doctrine\ORM;
use Volkszaehler\Model;
use Volkszaehler\Util;

/**
 * Interpreter to aggregate child Channels or Aggregators
 *
 * The AggregatorInterpreter is used to aggregate multiple channels with the same
 * indicator
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @package default
 */
class AggregatorInterpreter extends Interpreter {
	/**
	 * @var array of Interpreter
	 */
	protected $childrenInterpreter = array();

	protected $aggregator;

	/**
	 * Constructor
	 *
	 * @param Model\Aggregator $group should only contain channels of the same indicator
	 * @param ORM\EntityManager $em
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 * @todo handle channels in nested aggregators
	 * @todo handle child entities of different units
	 */
	public function __construct(Model\Aggregator $aggregator, ORM\EntityManager $em, $from, $to, $tupleCount = null, $groupBy = null) {
		$this->aggregator = $aggregator;

		foreach ($aggregator->getChildren() as $child) {
			if ($child instanceof Model\Channel) {
				$class = $child->getDefinition()->getInterpreter();
				$this->childrenInterpreter[] = new $class($child, $em, $from, $to, $tupleCount, $groupBy);
			}
		}
	}

	/**
	 * Convert raw meter readings
	 */
	public function convertRawTuple($row) {
		return null;
	}

	/*
	 * Iterator methods - not implemented
	 */
	public function rewind() {
	}

	public function current() {
	}

	public function valid() {
		return false;
	}

	/**
	 * Get total consumption of all channels - not implemented
	 */
	public function getConsumption() {
	}

	/**
	 * Just a passthrough to the channel interpreters
	 *
	 * @return array with the smallest value
	 */
	public function getMin() {
		$min = null;
		foreach ($this->childrenInterpreter as $interpreter) {
			$arr = $interpreter->getMax();
			if (! $min or $arr[1] < $min[1]) {
				$min = $arr;
			}
		}
		return $min;
	}

	/**
	 * Just a passthrough to the channel interpreters
	 *
	 * @return array with the biggest value
	 */
	public function getMax() {
		$max = null;
		foreach ($this->childrenInterpreter as $interpreter) {
			$arr = $interpreter->getMax();
			if (! $max or $arr[1] > $max[1]) {
				$max = $arr;
			}
		}
		return $max;
	}

	/**
	 * Just a passthrough to the channel interpreters
	 *
	 * @return float average value
	 */
	public function getAverage() {
		$sum = 0;
		foreach ($this->childrenInterpreter as $interpreter) {
			$sum += $interpreter->getAverage();
		}
		return (count($this->childrenInterpreter)) ? $sum / count($this->childrenInterpreter) : null;
	}

	/*
	 * Getter & setter
	 */

	public function getEntity() { return $this->aggregator; }
	public function getChildrenInterpreter() { return $this->childrenInterpreter; }
}
