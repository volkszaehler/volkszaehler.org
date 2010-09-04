<?php
/**
 * Configuration template
 *
 * You should use this file to obtain your custom configuration
 * new parameters should be documented
 *
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package default
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
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

/**
 * @var string PDO driver for Doctrine DBAL
 * @link http://www.doctrine-project.org/projects/dbal/2.0/docs/reference/configuration/en#getting-a-connection:driver
 */
$config['db']['driver']				= 'pdo_mysql';

/**
 * @var string hostname of database server. Use 'localhost' for the machine your webserver is running on.
 * @link http://www.doctrine-project.org/projects/dbal/2.0/docs/reference/configuration/en
 */
$config['db']['host']				= 'localhost';

/**
 * @var string username for the database server
 * @link http://www.doctrine-project.org/projects/dbal/2.0/docs/reference/configuration/en
 */
$config['db']['user']				= 'vz';

/**
 * @var string password for the database server
 * @link http://www.doctrine-project.org/projects/dbal/2.0/docs/reference/configuration/en
 */
$config['db']['password']			= 'demo';

/**
 * @var string name of the database the backend should use
 * @link http://www.doctrine-project.org/projects/dbal/2.0/docs/reference/configuration/en
 */
$config['db']['dbname']				= 'volkszaehler';

/**
 * @var string path of the sqlite database
 * @link http://www.doctrine-project.org/projects/dbal/2.0/docs/reference/configuration/en
 */
//$config['db']['path']				= 'volkszaehler';

/** @var boolean disables some optimizations. Only use it when you exactly know what you are doing. */
$config['devmode']					= TRUE;

/** @var integer set to > 0 to show debugging messages */
$config['debug']					= 5;

?>
