<?php
/**
 * Redirect to default frontend
 *
 * @author Steffen Vogel <info@steffenvogel.de>
 * @copyright Copyright (c) 2011, The volkszaehler.org project
 * @package frontend
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @todo check for requirements (Javascript etc)
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

<?php echo '<?xml version="1.0" encoding="UTF-8" ?>' ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
<head>
	<title>volkszaehler.org - frontend</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
</head>
<body>
	<noscript>
		<p>JavaScript is turned off in your web browser!</p>
		<p>To use the web frontend you need to turn it on!</p>
	</noscript>
	<script>
		// TODO add user agent detection for smartphones
		window.location = 'frontend/';
	</script>
</body>
</html>
