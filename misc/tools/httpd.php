#!/usr/bin/env php
<?php
/**
 * ppm-httpd is a high-performance standalone webserver providing
 * middleware capabilities based on PHP Process Manager (ppm)
 *
 * This implementation is multi-threaded and can be run stand-alone or
 * using built-in load balancer or behind an nginx load balancer
 * See https://github.com/marcj/php-pm for details
 *
 * To run on startup add this line to /etc/inittab
 *
 *  # VOLKSZAEHLER
 *  vzmw:235:respawn:/usr/bin/php /home/pi/volkszaehler.org/misc/tools/httpd.php start --bootstrap=Volkszaehler\\Util\\ReactInterface --bridge=HttpKernel
 *
 * Use `init q` to activate
 *
 * The server will listen on port 8080, to change add the --port switch
 *
 * @package default
 * @copyright Copyright (c) 2015, The volkszaehler.org project
 * @license http://www.gnu.org/licenses/gpl.txt GNU Public License
 * @author Andreas Goetz <cpuidle@gmx.de>
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

define('VZ_DIR', realpath(__DIR__ . '/../..'));

require_once VZ_DIR . '/lib/bootstrap.php';

set_time_limit(0);

use Symfony\Component\Console\Application;
use PHPPM\Commands\StartCommand;
use PHPPM\Commands\StatusCommand;

$app = new Application('volkszaehler.org middleware');
$app->add(new StartCommand);
$app->add(new StatusCommand);
$app->run();
