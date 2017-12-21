<?php
/**
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2017, The volkszaehler.org project
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

namespace Volkszaehler\Server;

use Volkszaehler\Router;
use PHPPM\Bootstraps\BootstrapInterface;

// move out of lib/server
define('VZ_DIR', realpath(__DIR__ . '/../..'));
// check config file before bootstrap dies
if (!file_exists(VZ_DIR . '/etc/volkszaehler.conf.php')) {
	throw new \Exception('Could not find config file.');
}
require_once(VZ_DIR . '/lib/bootstrap.php');

/**
 * Bootstrap bridge for Router
 */
class PPMBootstrapAdapter implements BootstrapInterface
{
    /**
     * Create middleware router
     */
    public function getApplication()
    {
        $app = new Router();
        return $app;
    }

    /**
     * Static directory - only used if frontend served via ppm httpd
     */
    public function getStaticDirectory()
    {
        return VZ_DIR . '/htdocs';
    }
}
