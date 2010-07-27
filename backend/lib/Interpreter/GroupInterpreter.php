<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
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

class GroupInterpreter {
	protected $channelInterpreter;

	public function __construct(Model\Group $group, ORM\EntityManager $em, $from, $to) {
		foreach ($group->getChannels() as $channel) {
			if (isset($indicator) && $indicator != $channel->getIndicator) {
				throw new \Exception('we only can aggregate channels of the same indicator');
			}
			else {
				$indicator = $channel->getIndicator();
			}

			$this->channelInterpreter[] = $channel->getInterpreter($em, $from, $to);
		}
	}

	/**
	 *
	 * @todo to be implemented
	 */
	public function getValues($groupBy = NULL) {

	}

	/**
	 *
	 */
	public function getMin() {
		$min = current($this->channelInterpreter)->getMax();
		foreach ($this->channelInterpreter as $channel) {
			$arr = $channel->getMax();
			if ($arr['value '] < $min['value']) {
				$min = $arr;
			}
		}
		return $min;
	}

	/**
	 *
	 */
	public function getMax() {
		$max = current($this->channelInterpreter)->getMax();
		foreach ($this->channelInterpreter as $channel) {
			$arr = $channel->getMax();
			if ($arr['value '] > $max['value']) {
				$max = $arr;
			}
		}
		return $max;
	}

	/**
	 *
	 */
	public function getAverage() {
		$sum = 0;

		foreach ($this->channels as $channel) {
			$sum += $channel->getAverage();
		}
		return ($sum / count($this->channelInterpreter));
	}
}