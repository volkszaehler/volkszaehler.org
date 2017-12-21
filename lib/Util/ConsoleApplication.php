<?php
/**
 * Data aggregation command line tool
 * Frontend controller for Util\Aggregation
 *
 * To setup aggregation job run crontab -e
 * 0 0 * * * /usr/bin/php aggregate.php -m delta -l hour -l day
 *
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2017, The volkszaehler.org project
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

namespace Volkszaehler\Util;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Application;

/**
 * Base class for console applications
 */
class ConsoleApplication extends Application {

	public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN') {
		parent::__construct($name, $version);

		if (!self::isConsole())
			throw new \Exception('This tool can only be run locally.');
	}

	/**
	 * Check if script is run from console
	 */
	public static function isConsole() {
		return php_sapi_name() == 'cli' || (isset($_SERVER['SESSIONNAME']) && $_SERVER['SESSIONNAME'] == 'Console');
	}

    /**
	 * Returns the long version of the application.
	 *
	 * @return string The long application version
	 */
	public function getLongVersion()
	{
		if ('UNKNOWN' !== $this->getName()) {
			return sprintf('<info>%s</info>', $this->getName());
		}
		return '<info>Console Tool</info>';
	}

	/**
	 * Gets the default input definition.
	 *
	 * @return InputDefinition An InputDefinition instance
	 */
	protected function getDefaultInputDefinition()
	{
		return new InputDefinition(array(
			new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),
			new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display this help message.'),
		));
	}
}

?>
