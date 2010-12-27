<?php
/**
 * Check for required libraries and versions
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @license http://www.gnu.org/licenses/gpl.txt GNU Public License
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
 * @param boolean
 * @return string HTML formatted output
 */
function print_test($bool) {
	if ($bool) {
		return '<span style="text-align: right; color: green; font-weight: bold;">ok</span>';
	}
	else {
		return '<span style="text-align: right; color: red; font-weight: bold;">fail</span>';
	}
}

array(
	'php_version' => version_compare(phpversion(), '5.3.0'),
	'pdo' => extension_loaded('pdo'),
	'pdo_version' => version_compare(phpversion('pdo'), '5.3.0')),
	'pdo_my'

extension_loaded('pdo_firebird')
extension_loaded('pdo_informix')
extension_loaded('pdo_mssql')
extension_loaded('pdo_mysql')
extension_loaded('pdo_oci')
extension_loaded('pdo_oci8')
extension_loaded('pdo_odbc')
extension_loaded('pdo_pgsql')
extension_loaded('pdo_sqlite')

pdo_firebird
pdo_informix
pdo_mssql
pdo_mysql
pdo_oci
pdo_oci8
pdo_odbc
pdo_pgsql
pdo_sqlite

)


?>

<h2>PHP version</h2>
<p>Your PHP version: <?= phpversion() ?><br />
Required version: 5.3.0<br />
<?= print_test()?></p>

<h2>PDO (PHP Data Objects)</h2>
<p>Your PDO version: <?= phpversion('pdo') ?><br />
Required version: 5.3.0<br />
<?= print_test(FALSE && version_compare(phpversion('pdo'), '5.3.0'))?></p>

// TODO check for PDO drivers (at least one)
// TODO check for additional Doctrine requirements

// TODO check for APC (optional)

// TODO check for Doctrine libraries & version
// TODO check for Symfony libraries &version (optional)
// TODO check for volkszaehler.org backend
// TODO check for volkszaehler.org (optional)

?>