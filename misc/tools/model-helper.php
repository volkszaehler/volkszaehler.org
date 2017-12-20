#!/usr/bin/env php
<?php
/**
 * @package default
 * @copyright Copyright (c) 2011-2017, The volkszaehler.org project
 * @license http://www.gnu.org/licenses/gpl.txt GNU Public License
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

use Volkszaehler\Definition;
use Volkszaehler\Util\ConsoleApplication;

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
 * Model validation helper
 *
 * This class can report property usage across all entitiy definitions
 *
 * @package default
 * @author Andreas Goetz <cpuidle@gmx.de>
 */
class ValidateCommand extends Command {

	protected function configure() {
		$this->setName('propertyusage')
			->setDescription('Validate property usage')
 		->addArgument('property', InputArgument::REQUIRED, 'Property name');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$property = $input->getArgument('property');
		$model = null; // "Volkszaehler\\Model\\Channel"

		$entity_groups = array();

		echo("Reporting property usage of '" . $property . "'\n");

		foreach (Definition\EntityDefinition::get() as $entity) {
			if ($model && $entity->getModel() !== $model)
				continue;

			if (!isset($entity_groups[$entity->getInterpreter()]))
				$entity_groups[$entity->getInterpreter()] = array();

			$entity_groups[$entity->getInterpreter()][] = $entity;
		}

		foreach($entity_groups as $group => $entities) {
			echo("\n== " . $group . " ==\n");

			foreach($entities as $entity) {
				$name = $entity->getName();
				echo(str_pad($name . ":", 20));
				$value = (isset($entity->$property)) ? $entity->$property : '';

				if ($property == "optional") {
					// meta definition
					if (is_array($value)) {
						$value = sprintf("[%s]", join(',', $value));
					}

					printf("%s\n", $value);
				}
				else {
					// actual property
					if (in_array($property, $entity->required))
						$required = "required";
					elseif (in_array($property, $entity->optional))
						$required = "optional";
					else
						$required = "not allowed";

					printf("%s\t%s\n", $required, $value);
				}
			}

			echo("\n");
		}
	}
}


$app = new Util\ConsoleApplication('Model validation tool');

$app->addCommands(array(
	new ValidateCommand
));

$app->run();

?>
