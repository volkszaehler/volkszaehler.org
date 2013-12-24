<?php
/**
 * Data aggregation command line tool
 *
 * To setup aggregation job run crontab -e
 * 0 0 * * * /usr/bin/php cron.php
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

/**
 * @author Andreas Goetz <cpuidle@gmx.de>
 */
class Cron {
	/**
	 * @var \Doctrine\ORM\EntityManager Doctrine EntityManager
	 */
	protected $em;

	protected $aggregator;

	public function __construct() {
		$this->em = Volkszaehler\Router::createEntityManager(true); // get admin credentials
	}

	/**
	 * Aggregate channel
	 */
	public function aggregate($uuid, $levels, $mode, $period) {
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

	/**
	 * Clear aggregate table for channel
	 * @todo add levels support
	 */
	public function clear($uuid) {
		$msg = "Clearing aggregation table";
		if ($uuid) $msg .= " for UUID " . $uuid;
		echo($msg . ".\n");
		$this->aggregator->clear($uuid);
		echo("Done clearing aggregation table.\n");
	}

	public function run($command, $uuids, $levels, $mode, $period = null) {
		$this->aggregator = new Util\Aggregation($this->em->getConnection());

		if (!in_array($mode, array('full', 'delta')))
			throw new \Exception('Unsupported aggregation mode ' . $mode);

		$conn = $this->em->getConnection();

		if ($command == 'create') {
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
		elseif ($command == 'optimize') {
			echo("Optimizing aggregate table.\n");
			$conn->executeQuery('OPTIMIZE TABLE aggregate');
			echo("Optimizing data table (slow).\n");
			$conn->executeQuery('OPTIMIZE TABLE data');
		}
		elseif ($command == 'clear') {
			// loop through all uuids
			if (is_array($uuids)) {
				foreach ($uuids as $uuid) {
					$this->clear($uuid, $levels, $mode, $period);
				}
			}
			else {
				$this->clear(null, $levels, $mode, $period);
			}
		}
		elseif ($command == 'aggregate' || $command == 'run') {
			// loop through all uuids
			if (is_array($uuids)) {
				foreach ($uuids as $uuid) {
					$this->aggregate($uuid, $levels, $mode, $period);
				}
			}
			else {
				$this->aggregate(null, $levels, $mode, $period);
			}
		}
		else
			throw new \Exception('Unknown command ' . $command);
	}
}

$console = new Util\Console($argv, array('u:'=>'uuid:', 'm:'=>'mode:', 'l:'=>'level', 'p:'=>'period', 'h'=>'help'));

if ($console::isConsole()) {
	$help    = $console->getOption('h');
	$uuid    = $console->getOption('u');
	$mode    = $console->getOption('m', array('delta'));
	$level   = $console->getOption('l', array('day'));
	$period  = $console->getOption('p');

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

	$cron = new Cron();

	foreach ($commands as $command) {
		$cron->run($command, $uuid, $level, $mode[0], $period[0]);
	}
}
else
	throw new \Exception('This tool can only be run locally.');

?>
