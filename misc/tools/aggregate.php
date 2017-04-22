#!/usr/bin/env php
<?php
/**
 * Data aggregation command line tool
 * Frontend controller for Util\Aggregation
 *
 * To setup aggregation job run crontab -e
 * 0 0 * * * /usr/bin/php aggregate.php run -m delta -l hour -l day
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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Application;

define('VZ_DIR', realpath(__DIR__ . '/../..'));

require_once VZ_DIR . '/lib/bootstrap.php';


/**
 * BasicCommand
 */
abstract class BasicCommand extends Command {

	/**
	 * @var \Doctrine\ORM\EntityManager Doctrine EntityManager
	 */
	protected $em;

	protected $aggregator;

	public function __construct() {
		parent::__construct();

		$this->em = Volkszaehler\Router::createEntityManager(true); // get admin credentials
		$this->aggregator = new Util\Aggregation($this->em->getConnection());
	}
}

/**
 * Optimize data and aggregate tables
 */
class OptimizeCommand extends BasicCommand {

	protected function configure() {
		$this->setName('optimize')
			->setDescription('Optimize data and aggregate tables');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$conn = $this->em->getConnection();

		echo("Optimizing aggregate table.\n");
		$conn->executeQuery('OPTIMIZE TABLE aggregate');
		echo("Optimizing data table (slow).\n");
		$conn->executeQuery('OPTIMIZE TABLE data');
	}
}

/**
 * Clear aggregate table for channel
 * @todo add levels support
 */
class ClearCommand extends BasicCommand {

	protected function configure() {
		$this->setName('clear')
			->setDescription('Clear aggregation table')
 		->addArgument('uuid', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'UUID(s)', array(null))
			->addOption('level', 'l', InputOption::VALUE_REQUIRED, 'Level (all|hour|day|month|year)', 'all')
			->addOption('after', 'a', InputOption::VALUE_REQUIRED, 'Clear aggregation data after specified date');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		foreach ($input->getArgument('uuid') as $uuid) {
			$msg = "Clearing aggregation table";
			if ($uuid) $msg .= " for UUID " . $uuid;
			echo($msg . ".\n");

			$this->aggregator->clear($uuid, $input->getOption('level'), $input->getOption('after'));
			echo("Done clearing aggregation table.\n");
		}
	}
}


/**
 * Aggregate selected channels and levels
 */
class RunCommand extends BasicCommand {

	protected function configure() {
		$this->setName('run')
			->setDescription('Run aggregation')
 		->addArgument('uuid', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'UUID(s)', array(null))
			->addOption('level', 'l', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Level (hour|day|month|year)', array('day'))
			->addOption('mode', 'm', InputOption::VALUE_REQUIRED, 'Mode (full|delta)', 'delta')
			->addOption('period', 'p', InputOption::VALUE_REQUIRED, 'Previous time periods (full|delta)');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		if (!in_array($mode = $input->getOption('mode'), array('full', 'delta'))) {
			throw new \Exception('Unsupported aggregation mode ' . $mode);
		}

		// loop through all uuids
		foreach ($input->getArgument('uuid') as $uuid) {
			// loop through all aggregation levels
			foreach ($input->getOption('level') as $level) {
				if (!Util\Aggregation::isValidAggregationLevel($level))
					throw new \Exception('Unsupported aggregation level ' . $level);

				$msg = "Performing '" . $mode . "' aggregation";
				if ($uuid) $msg .= " for UUID " . $uuid;
				echo($msg . " on '" . $level . "' level.\n");

				$rows = $this->aggregator->aggregate($uuid, $level, $mode, $input->getOption('period'));
				echo("Updated $rows rows.\n");
			}
		}
	}
}


$app = new Util\ConsoleApplication('Data aggregation tool');

$app->addCommands(array(
	new OptimizeCommand,
	new ClearCommand,
	new RunCommand
));

$app->run();

?>
