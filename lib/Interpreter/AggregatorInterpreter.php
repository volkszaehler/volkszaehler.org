<?php
/**
 * @copyright Copyright (c) 2011-2017, The volkszaehler.org project
 * @license https://opensource.org/licenses/gpl-license.php GNU Public License
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
 * Placeholder only- aggregating child data is not implemented
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @author Andreas Goetz <cpuidle@gmx.de>
 */
class AggregatorInterpreter extends Interpreter {

	/**
	 * Constructor
	 *
	 * @param Model\Aggregator $group should only contain channels of the same indicator
	 * @param ORM\EntityManager $em
	 * @param integer $from timestamp in ms since 1970
	 * @param integer $to timestamp in ms since 1970
	 */
	public function __construct(Model\Aggregator $aggregator, ORM\EntityManager $em, $from, $to, $tupleCount = null, $groupBy = null) {
		throw new \Exception('Getting data is not supported for groups');
	}

	/**
	 * Generate database tuples
	 */
	public function getIterator() {
	}

	/**
	 * Convert raw meter readings
	 */
	public function convertRawTuple($row) {
	}
}
