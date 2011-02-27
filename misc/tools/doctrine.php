<?php
/**
 * Doctrine cli configuration
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

define('VZ_DIR', realpath(__DIR__ . '/../..'));

// class autoloading
require_once VZ_DIR . '/lib/Util/ClassLoader.php';

$classLoaders = array(
	new Volkszaehler\Util\ClassLoader('Doctrine', VZ_DIR . '/lib/vendor/Doctrine'),
	new Volkszaehler\Util\ClassLoader('Symfony', VZ_DIR . '/lib/vendor/Doctrine/Symfony'),
	new Volkszaehler\Util\ClassLoader('Volkszaehler', VZ_DIR . '/lib')
);

foreach ($classLoaders as $loader) {
	$loader->register(); // register on SPL autoload stack
}

// load configuration
Util\Configuration::load(VZ_DIR . '/etc/volkszaehler.conf');

$em = Volkszaehler\Router::createEntityManager(TRUE); // get admin credentials

$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
	'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
	'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)
));


\Doctrine\ORM\Tools\Console\ConsoleRunner::run($helperSet);

?>
