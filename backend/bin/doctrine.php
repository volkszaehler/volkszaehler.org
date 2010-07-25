<?php
/**
 * doctrine cli configuration and bootstrapping
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package doctrine
 * @copyright Copyright (c) 2010, The volkszaehler.org project
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

// TODO replace by state class
const BACKEND_DIR = '/home/steffen/workspace/volkszaehler.org/backend';
const DEV_ENV = TRUE;

// class autoloading
require BACKEND_DIR . '/lib/Util/ClassLoader.php';

$classLoaders = array();
$classLoaders[] = new Volkszaehler\Util\ClassLoader('Doctrine', BACKEND_DIR . '/lib/vendor/Doctrine');
$classLoaders[] = new Volkszaehler\Util\ClassLoader('Symfony', BACKEND_DIR . '/lib/vendor/Symfony');
$classLoaders[] = new Volkszaehler\Util\ClassLoader('Volkszaehler', BACKEND_DIR . '/lib');

foreach ($classLoaders as $loader) {
	$loader->register(); // register on SPL autoload stack
}

// load configuration
Util\Configuration::load(BACKEND_DIR . '/volkszaehler.conf');

$em = Volkszaehler\Dispatcher::createEntityManager();

$helperSet = new \Symfony\Components\Console\Helper\HelperSet(array(
	'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
	'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)
));

$cli = new \Symfony\Components\Console\Application('Doctrine Command Line Interface', Doctrine\ORM\Version::VERSION);
$cli->setCatchExceptions(TRUE);
$cli->setHelperSet($helperSet);
$cli->addCommands(array(
	// DBAL Commands
	new \Doctrine\DBAL\Tools\Console\Command\RunSqlCommand(),
	new \Doctrine\DBAL\Tools\Console\Command\ImportCommand(),

	// ORM Commands
	new \Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand(),
	new \Doctrine\ORM\Tools\Console\Command\ClearCache\ResultCommand(),
	new \Doctrine\ORM\Tools\Console\Command\ClearCache\QueryCommand(),
	new \Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand(),
	new \Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand(),
	new \Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand(),
	new \Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand(),
	new \Doctrine\ORM\Tools\Console\Command\ConvertDoctrine1SchemaCommand(),
	new \Doctrine\ORM\Tools\Console\Command\GenerateRepositoriesCommand(),
	new \Doctrine\ORM\Tools\Console\Command\GenerateEntitiesCommand(),
	new \Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand(),
	new \Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand(),
	new \Doctrine\ORM\Tools\Console\Command\RunDqlCommand(),
	new \Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand(),
));
$cli->run();

?>
