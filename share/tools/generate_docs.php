<?php
/**
 * phpdoc generation
 *
 * This is simple bash script to update the project documentation
 * based on PHPDocumentor. It's used to be invoked by post-commit hooks
 * of GitHub or the release script.
 *
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @author Steffen Vogel <info@steffenvogel.de>
 * @package tools
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
 *
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

$vzDir = '/var/www/vz';

# change directory
chdir($vzDir . '/github/');

# update git
passthru('git pull');

chdir($vzDir);

# update dokumentation
passthru('phpdoc/phpdoc -c ' . $vzDir . '/github/share/tools/phpdoc.ini');

?>

