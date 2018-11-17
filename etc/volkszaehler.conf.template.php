<?php
/**
 * Configuration template
 *
 * You should use this file to obtain your custom configuration
 * new parameters should be documented
 *
 * @author Steffen Vogel <info@steffenvogel.de>
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

/**
 * PDO driver for Doctrine DBAL
 *
 * See Doctrine doc for details: http://www.doctrine-project.org/projects/dbal/2.0/docs/reference/configuration/en#getting-a-connection:driver
 */
$config['db']['driver']				= 'pdo_mysql';

/**
 * Hostname of database server
 *
 * Use 'localhost' for the machine your webserver is running on.
 */
$config['db']['host']				= 'localhost';

/**
 * Port of database server
 *
 * Only needed for other Ports than 3306 on MySQL servers.
 * Only needed for other Ports than 5432 on PostgreSQL servers.
 */
//$config['db']['port']				= 3306;

/**
 * Username for the database server
 */
$config['db']['user']				= 'vz';

/**
 * Password for the database server
 */
$config['db']['password']			= 'demo';

/**
 * @var string database name
 */
$config['db']['dbname']				= 'volkszaehler';

/**
 * @var database connection encoding - should not be changed
 *
 * See http://stackoverflow.com/questions/13399912/symfony2-charset-for-queries-parameters
 */
$config['db']['charset']			= 'UTF8';

/**
 * @var database optimizer class
 *
 * For automatic leave empty. Other options:
 *   - MySQLOptimizer: provides additional group=15m setting (does not work with aggregation)
 */
// $config['db']['optimizer']			= 'Volkszaehler\Interpreter\SQL\MySQLOptimizer';

/**
 * Administration credentials
 *
 * For administration tasks (used by doctrine cli and the setup script)
 * the following $['db']['admin'] settings will be merged with $config['db']
 */
//$config['db']['admin']['user']		= 'vz_admin';
//$config['db']['admin']['password']		= 'admin_demo';

/**
 * Database aggregation
 *
 * See bin/aggregation for details
 */
$config['aggregation'] = true;

/**
 * Path of the SQLite database
 *
 * See Doctrine doc for details: http://www.doctrine-project.org/projects/dbal/2.0/docs/reference/configuration/en
 */
//$config['db']['path']				= 'volkszaehler';

/**
 * Push server settings
 */
$config['push']['enabled'] = false;		// set to true to enable push updates
$config['push']['server'] = 5582;		// vzlogger will push to this ports (binds on 0.0.0.0)
$config['push']['broadcast'] = 8082;	// frontend will subscribe on this port (binds on 0.0.0.0)

$config['push']['routes']['wamp'] = array('/', '/ws');		// routes for wamp access
$config['push']['routes']['websocket'] = array();			// routes for plain web sockets, try array('/socket')

/**
 * Security settings
 */
$config['security']['maxbodysize'] = false;	// limit maximum POST body size, e.g. 4096

/**
 * Timezone for the middleware
 *
 * See PHP doc for details: http://www.php.net/manual/de/timezones.php
 * http://www.php.net/manual/de/datetime.configuration.php#ini.date.timezone
 */
//$config['timezone']				= 'Europe/Berlin';

/**
 * Locale used for regular expressions
 *
 * See PHP doc for details: http://php.net/manual/de/function.setlocale.php
 */
$config['locale']				= array('de_DE', 'en_US', 'C');

/**
 * Array of colors for plot series
 *
 * @attention Only used by jpGraph for server-side plotting!
 */
$config['colors'] = array('#83CAFF', '#7E0021', '#579D1C', '#FFD320', '#FF420E', '#004586', '#0084D1', '#C5000B', '#FF950E', '#4B1F6F', '#AECF00', '#314004');

/**
 * Developer mode
 *
 * This disables all caching mechanisms and enabled debugging by default
 */
$config['devmode']				= FALSE;
$config['cache']['ttl']			= 3600;	// only used if devmode == FALSE

/**
 * Debugging level
 *
 * Set to > 0 to enable debug messages by default
 */
$config['debug']				= 0;

?>
