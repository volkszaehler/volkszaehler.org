<?php

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
if (!file_exists(BACKEND_DIR . '/volkszaehler.conf.php')) {
	throw new Exception('No configuration available! Use volkszaehler.conf.default.php as an template');
}
else {
	Util\Configuration::load(BACKEND_DIR . '/volkszaehler.conf.php');
}

$em = Volkszaehler\Dispatcher::createEntityManager();

$helperSet = new \Symfony\Components\Console\Helper\HelperSet(array('em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)));

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
