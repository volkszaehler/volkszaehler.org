<?php
/**
 * Data aggregation command line tool
 * Frontend controller for Util\Aggregation
 *
 * To setup aggregation job run crontab -e
 * 0 0 * * * /usr/bin/php aggregate.php -m delta -l hour -l day
 *
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2013, The volkszaehler.org project
 * @package tools
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
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

use Volkszaehler\Util;

define('VZ_DIR', realpath(__DIR__ . '/../..'));

require_once VZ_DIR . '/lib/bootstrap.php';

class AggregationController {
	/**
	 * @var \Doctrine\ORM\EntityManager Doctrine EntityManager
	 */
	protected $em;

	protected $aggregator;

	public function __construct() {
		$this->em = Volkszaehler\Router::createEntityManager(true); // get admin credentials
		$this->aggregator = new Util\Aggregation($this->em->getConnection());
	}

	/**
	 * (Re)create aggregation table
	 */
	public function cmdCreate() {
		$conn = $this->em->getConnection();

		echo("Recreating aggregation table.\n");
		$conn->executeQuery('DROP TABLE IF EXISTS `aggregate`');
		$conn->executeQuery(
			'CREATE TABLE `aggregate` (' .
			'  `id` int(11) NOT NULL AUTO_INCREMENT,' .
			'  `channel_id` int(11) NOT NULL,' .
			'  `type` tinyint(1) NOT NULL,' .
			'  `timestamp` bigint(20) NOT NULL,' .
			'  `value` double NOT NULL,' .
			'  `count` int(11) NOT NULL,' .
			'  PRIMARY KEY (`id`),' .
			'  UNIQUE KEY `ts_uniq` (`channel_id`,`type`,`timestamp`)' .
			')');
	}

	/**
	 * Optimize data and aggregate tables
	 */
	public function cmdOptimize() {
		$conn = $this->em->getConnection();

		echo("Optimizing aggregate table.\n");
		$conn->executeQuery('OPTIMIZE TABLE aggregate');
		echo("Optimizing data table (slow).\n");
		$conn->executeQuery('OPTIMIZE TABLE data');
	}

	/**
	 * Clear aggregate table for channel
	 * @todo add levels support
	 */
	public function cmdClear($uuids) {
		foreach ($uuids as $uuid) {
			$msg = "Clearing aggregation table";
			if ($uuid) $msg .= " for UUID " . $uuid;
			echo($msg . ".\n");

			$this->aggregator->clear($uuid);
			echo("Done clearing aggregation table.\n");
		}
	}

	/**
	 * Aggregate selected channels and levels
	 */
	public function cmdAggregate($uuids, $levels, $mode, $period = null) {
		if (!in_array($mode, array('full', 'delta'))) {
			throw new \Exception('Unsupported aggregation mode ' . $mode);
		}

		// loop through all uuids
		foreach ($uuids as $uuid) {
			// loop through all aggregation levels
			foreach ($levels as $level) {
				if (!Util\Aggregation::isValidAggregationLevel($level))
					throw new \Exception('Unsupported aggregation level ' . $level);

				$msg = "Performing '" . $mode . "' aggregation";
				if ($uuid) $msg .= " for UUID " . $uuid;
				echo($msg . " on '" . $level . "' level.\n");

				$rows = $this->aggregator->aggregate($uuid, $level, $mode, $period);
				echo("Updated $rows rows.\n");
			}
		}
	}
}


if (Util\Console::isConsole()) {
	$console = new Util\Console(array(
		'u:'=>'uuid:',
		'm:'=>'mode:',
		'l:'=>'level',
		'p:'=>'period',
		'h'=>'help'));

	// make sure uuid array is populated
	$uuid    = $console->getMultiOption('u', array(null));
	$level   = $console->getMultiOption('l', array('day'));
	$mode    = $console->getSimpleOption('m', 'delta');
	$period  = $console->getSimpleOption('p');
	$help    = $console->getSimpleOption('h');

	$commands = $console->getCommand();

	if ($help || count($commands) == 0) {
		echo("Usage: aggregate.php [options] command[s]\n");
		echo("Commands:\n");
		echo("       aggregate|run Run aggregation\n");
		echo("              create Create aggregation table (DESTRUCTIVE)\n");
		echo("               clear Clear aggregation table\n");
		echo("            optimize Opimize data and aggregate tables\n");
		echo("Options:\n");
		echo("             -u[uid] uuid\n");
		echo("            -l[evel] hour|day|month|year\n");
		echo("             -m[ode] full|delta\n");
		echo("           -p[eriod] number of previous time periods\n");
		echo("Example:\n");
		echo("         aggregate.php --uuid ABCD-0123 --mode delta -l month\n");
		echo("Create monthly and daily aggregation data since last run for specified UUID\n");
	}

	$job = new AggregationController();

	foreach ($commands as $command) {
		switch ($command) {
			case 'create':
				$job->cmdCreate();
				break;
			case 'clear':
				$job->cmdClear($uuid);
				break;
			case 'aggregate':
			case 'run';
				$job->cmdAggregate($uuid, $level, $mode, $period);
				break;
			default:
				throw new \Exception('Invalid command \'' . $command . '\'.');
		}
	}
}
else
	throw new \Exception('This tool can only be run locally.');

?>
