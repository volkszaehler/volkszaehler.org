<?php
/**
 * Doctrine cli configuration
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package doctrine
 * @copyright Copyright (c) 2011, The volkszaehler.org project
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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

define('VZ_DIR', realpath(__DIR__ . '/../..'));

require VZ_DIR . '/vendor/autoload.php';

// load configuration
Util\Configuration::load(VZ_DIR . '/etc/volkszaehler.conf');

define('DOCTRINE_DIR', Util\Configuration::read('lib.doctrine') ? Util\Configuration::read('lib.doctrine') : 'Doctrine');

$serviceContainer = new ContainerBuilder();
$loader = new XmlFileLoader($serviceContainer, new FileLocator(__DIR__));
$loader->load(VZ_DIR . '/etc/services.xml');

$em = Volkszaehler\Router::createEntityManager(TRUE); // get admin credentials

$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
	'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
	'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)
));

\Doctrine\ORM\Tools\Console\ConsoleRunner::run($helperSet);
