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
		<title>volkszaehler.org - documentation generator</title>
	</head>
	<body>
		<pre>

<?php

$vzDir = '/var/www/vz';
$output = array();
$rc = 0;

# change directory
chdir($vzDir . '/github/');

# update git
$cmd = 'git pull';
$output[] = $cmd . PHP_EOL;
exec($cmd, $output, $rc);

if ($rc == 0) {
	# update documentation
	$cmd = $vzDir . '/phpdoc/phpdoc -c ' . $vzDir . '/github/share/tools/phpdoc.ini';
	//$cmd = 'php5 ' . $vzDir . '/phpdoctor/phpdoc.php ' . $vzDir . '/github/share/tools/phpdoctor.ini';
	$output[] = PHP_EOL . $cmd . PHP_EOL;
	exec($cmd, $output, $rc);
}

foreach ($output as $line) {
	echo $line . PHP_EOL;
}

?>
		</pre>
	</body>
</html>