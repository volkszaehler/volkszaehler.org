<?php
/**
 * @package default
 * @copyright Copyright (c) 2011, The volkszaehler.org project
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
class ValidateCommand {

	public function run($property, $model = null) {
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
				echo(str_pad($entity->getName() . ":", 16));

				if (in_array($property, $entity->required))
					echo("required\n");
				elseif (in_array($property, $entity->optional))
					echo("optional\n");
				else
					echo("not allowed\n");
			}

			echo("\n");
		}
	}
}

$r = new ValidateCommand();

$r->run('resolution'/*, "Volkszaehler\\Model\\Channel"*/);

?>
