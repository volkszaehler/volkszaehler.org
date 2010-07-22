<?php
/**
 * install script
 *
 * for creating/updating of the configuration/database
 *
 * @copyright Copyright (c) 2010, The volkszaehler.org project
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
?>

<?= '<?xml version="1.0"' ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>volkszaehler.org installer</title>
	</head>
	<body>

<?php

// TODO complete installer

switch (@$_GET['step']) {
	case '1':
		echo 'bla';
		break;

	default:
		echo '<p>welcome to the installation of your volkszaehler backend!</p>
			<p>lets proceed with the <a href="?step=1">next step</a></p>';
}

?>

</body>
</html>