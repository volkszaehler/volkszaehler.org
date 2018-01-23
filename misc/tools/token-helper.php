#!/usr/bin/env php
<?php
/**
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
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

namespace Volkszaehler;

use Volkszaehler\Util;

use Firebase\JWT\JWT;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Application;

define('VZ_DIR', realpath(__DIR__ . '/../..'));

require VZ_DIR . '/lib/bootstrap.php';

/**
 * Create token
 */
class CreateCommand extends Command {

	protected function configure() {
		$this->setName('create')
			->setDescription('Create JWT token')
 		->addArgument('username', InputArgument::REQUIRED, 'User name')
 		->addOption('operation', 'o', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Operation constraint (any of add, delete, get, edit or their HTTP equivalents POST, DELETE, GET, PATCH', [])
 		->addOption('context', 'c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Context constraint (any of data, entity etc.)', [])
 		->addOption('valid', 'v', InputOption::VALUE_REQUIRED, 'Valid data (php notation)');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$user = $input->getArgument('username');
		$auth = Util\Configuration::read('users.plain');

		if (!isset($auth[$user])) {
			throw new \Exception('Invalid user credentials');
		}

		$tokenHelper = new Util\TokenHelper();
		$claims = $tokenHelper->getUserConstraints($user);

		if ($input->getOption('valid')) {
			$claims['exp'] = strtotime($input->getOption('valid'));
		}

		$contextConstraints = array_map(function($context) {
			return strtolower($context);
		}, $input->getOption('context'));
		$operationConstraints = $tokenHelper->mapOperationsToHttpMethods($input->getOption('operation'));

		if (count($contextConstraints)) {
			$claims['vz:ctx'] = join(',', $contextConstraints);
		}
		if (count($operationConstraints)) {
			$claims['vz:ops'] = join(',', $operationConstraints);
		}

		$token = $tokenHelper->issueToken($user, $claims);
		echo $token;
	}
}

/**
 * Decode token
 */
class DecodeCommand extends Command {

	protected function configure() {
		$this->setName('decode')
			->setDescription('Decode JWT token')
 		->addArgument('token', InputArgument::REQUIRED, 'token');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$jwt = $input->getArgument('token');

		if (!($key = Util\Configuration::read('authorization.secretkey'))) {
			throw new \Exception('Missing authorization.secretkey');
		}

		echo json_encode(JWT::decode($jwt, $key, array(Util\TokenHelper::TOKEN_CIPHER)));
	}
}


$app = new Util\ConsoleApplication('Token helper');

$app->addCommands(array(
	new CreateCommand,
	new DecodeCommand
));

$app->run();

?>
