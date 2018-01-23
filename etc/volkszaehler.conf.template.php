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
 * See Doctrine doc for details: http://www.doctrine-project.org/projects/dbal/2.0/docs/reference/configuration/en
 */
$config['db']['host']				= 'localhost';

/**
 * Username for the database server
 *
 * See Doctrine doc for details: http://www.doctrine-project.org/projects/dbal/2.0/docs/reference/configuration/en
 */
$config['db']['user']				= 'vz';

/**
 * Password for the database server
 *
 * See Doctrine doc for details: http://www.doctrine-project.org/projects/dbal/2.0/docs/reference/configuration/en
 */
$config['db']['password']			= 'demo';

/**
 * Database name
 *
 * See Doctrine doc for details: http://www.doctrine-project.org/projects/dbal/2.0/docs/reference/configuration/en
 */
$config['db']['dbname']				= 'volkszaehler';

/**
 * Database connection encoding - should not be changed
 *
 * See http://stackoverflow.com/questions/13399912/symfony2-charset-for-queries-parameters
 */
$config['db']['charset']			= 'UTF8';

/**
 * Path of the SQLite database - only used if $config['db']['driver'] = 'pdo_sqlite'
 *
 * See Doctrine doc for details: http://www.doctrine-project.org/projects/dbal/2.0/docs/reference/configuration/en
 */
$config['db']['path']				= 'volkszaehler.db3';

/**
 * Database optimizer class
 *
 * For automatic leave empty. Other options:
 *   - MysqlOptimizer: provides additional group=15m setting (does not work with aggregation)
 */
// $config['db']['optimizer']			= 'Volkszaehler\Interpreter\SQL\MysqlOptimizer';

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
 * See misc/tools/aggregation.php for details
 */
$config['aggregation'] = true;

/**
 * Push server settings
 *
 * See misc/tools/push-server.php for details
 */
$config['push'] = array(
	'enabled' => false,					// set to true to enable push updates
	'server' => 5582,					// vzlogger will push to this ports (binds on 0.0.0.0)
	'broadcast' => 8082,				// frontend will subscribe on this port (binds on 0.0.0.0)
	'routes' => array(
		'wamp' => array('/', '/ws'),	// routes for wamp access
		'websocket' => array()			// routes for plain web sockets, try array('/socket')
	)
);

/**
 * Security settings
 */
$config['security']['maxbodysize'] = false;	// limit maximum POST body size, e.g. 4096

/**
 * Access control
 *
 * Define firewall rules per ip, path or method and actions 'allow', 'deny' and 'auth'
 */
$config['firewall'] = array(
	[	// localhost
		'ips'		=> ['127.0.0.1/8', '::1'],
		'action'	=> 'allow'
	],
	[	// local network
		'ips'		=> ['192.168.0.0/16', '172.16.0.0/12', '10.0.0.0/8', 'fe80::/64'],
		'action'	=> 'allow'
	],
	[	// always allow /auth - this is required
		'path'		=> '/auth',
		'methods'	=> 'POST',
		'action'	=> 'allow'
	],
	[	// always allow GET - makes read access public
		'methods'	=> 'GET',
		'action'	=> 'allow'
	],
	[	// authorize all other requests
		'action'	=> 'auth'
	]
);

/**
 * Proxies
 *
 * Trust any local machine as (reverse) proxy
 */
$config['proxies'] = array(
	'127.0.0.1/8', '::1', '192.168.0.0/16', '172.16.0.0/12', '10.0.0.0/8', 'fe80::/64'
);

/**
 * User authorization for firewall rule action 'auth'
 *
 * NOTE: if using authorization *ALWAYS* make sure HTTPS is enforced
 */
$config['authorization'] = array(
	'secretkey' => '',		// define your own random secret key
	'valid' => 24 * 3600,	// token validity period
);

// add users below as 'user' => 'pass'
$config['users']['plain'] = array(
	// 'user' => 'pass'
);

// add users restrictions below as understood by misc/tools/token-helper.php
// empty context/operation array means no constraints
$config['users']['constraints'] = array(
/*
	'user' => [
		'context' => [ array of allowed contexts ],
		'operation' => [ array of allowed operations ],
	]
*/
);

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
 * This disables all caching mechanisms
 */
$config['devmode']				= false;

?>
