<?php
/**
 * @copyright Copyright (c) 2011-2017, The volkszaehler.org project
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
 * @author Andreas Goetz <cpuidle@gmx.de>
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

namespace Volkszaehler\Interpreter\SQL;

/**
 * PostgreSQLOptimizer provides basic DB-specific optimizations
 */
class PostgreSQLOptimizer extends SQLOptimizer {

	/**
	 * DB-specific data grouping by date functions
	 *
	 * @param string $groupBy
	 * @return string the sql part
	 */
	public static function buildGroupBySQL($groupBy) {
		$ts = "TIMESTAMP 'epoch' + timestamp * INTERVAL '1 millisecond'"; // just for saving space

		switch ($groupBy) {
			case 'year':
			case 'month':
			case 'week':
			case 'day':
			case 'hour':
			case 'minute':
			case 'second':
				return "DATE_TRUNC('" . $groupBy . "', " . $ts . ")";
			default:
				return FALSE;
		}
	}
}

?>
