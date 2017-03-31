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

/**
 * GroupedTimestampIterator filters timestamps of
 * underlying iterator by grouping period.
 * Only the last timestamp per period is returned.
 */
class GroupedTimestampIterator extends \IteratorIterator {

	private $group;

	public function __construct(\Traversable $iterator, $group) {
		parent::__construct(new DelayedIterator($iterator));

		$formats = array(
			'year' =>	'Y',
			'month' =>	'Y-m',
			// 'week' =>	'',
			'day' =>	'Y-m-d',
			'hour' =>	'Y-m-d H',
			// '15m' =>	'',
			'minute' =>	'Y-m-d H:i',
			'second' =>	'Y-m-d H:i:s'
		);

		$group = strtolower($group);
		if (!array_key_exists($group, $formats)) {
			throw new \Exception('Invalid group mode: ' . $group);
		}

		$this->group = $formats[$group];
	}

	private function timestampToPeriod($ts) {
		$date = new \DateTime('@' . (int)($ts / 1000));
		$period = $date->format($this->group);
		return $period;
	}

	/*
	 * Iterator
	 */

	public function rewind() {
		parent::rewind();

		if ($this->valid()) {
			$period = $this->timestampToPeriod($this->current());
			$peekPeriod = $this->timestampToPeriod($this->getInnerIterator()->peek());

			if ($period == $peekPeriod) {
				$this->next();
			}
		}
	}

	public function next() {
		do {
			parent::next();

			$continue = false;
			if ($this->valid()) {
				if (!isset($period)) {
					$period = $this->timestampToPeriod($this->current());
				}
				$peekPeriod = $this->timestampToPeriod($this->getInnerIterator()->peek());
				$continue = $period === $peekPeriod;
			}
		}
		while ($continue);
	}
}

?>
