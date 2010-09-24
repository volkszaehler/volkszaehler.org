<?php
/**
 * Installer
 *
 * For creating/updating the configuration/database
 * and downloading of required libraries
 * and configuration of of the PHP interpreter/webserver
 *
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package tools
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
 * @todo finish
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
?>

<?= '<?xml version="1.0"' ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>volkszaehler.org - setup</title>
	</head>
	<body>
	<h1>volkszaehler.org - setup</h1>

<?php

// TODO start session with GET-parameter (no cookies)

if (isset($_GET['step'])) {
	if (file_exists($_GET['step'] . '.php')) {
		include $_GET['step'] . '.php';
	}
	else {
		// TODO check js history call
		echo '<span style="color: red; font-weight: bold;">Invalid step during installation: ' . $_GET['step'] . '</span>
			<p><a href="javascript:window.location.back()">back</a></p>';
	}
}
else {
	echo <<<EOT
<p>Welcome to the installation of your volkszaehler.org!<br />
This installer will:
<ul>
	<li>check for all requirements</li>
	<li>install missing libraries</li>
	<li>setup your database + configuration</li>
	<li>import your existing data</li>
	<li>test your installation</li>
</ul>
Only fundamental administration knowledge is required :)
</p>
<p>
So, ready? Take a cup of coffee and lets take a look on your system:</p>
<p><a href="?step=check">Start with step 1: analyze your system</a></p>
EOT;
}

?>

</body>
</html>