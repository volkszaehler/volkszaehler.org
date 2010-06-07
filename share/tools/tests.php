<?php
/*
 * Copyright (c) 2010 by Justin Otherguy <justin@justinotherguy.org>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License (either version 2 or
 * version 3) as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * For more information on the GPL, please go to:
 * http://www.gnu.org/copyleft/gpl.html
 */

include '../../backend/init.php';

$user = User::getByEMail($_GET['email']);
$groups = $user->getGroups(true);

foreach ($groups as $group) {
	$channels = $group->getChannels();
	echo $group->description . ' (' . count($channels) . '):<br />';
	foreach ($channels as $channel) {
		echo '&nbsp;&nbsp;[' . $channel->ucid . '] ' . $channel->description . ': ' . $channel::unit;
		echo ' Min: ' . implode('|', $channel->getMin());
		echo ' Max: ' . implode('|', $channel->getMax());
		echo ' Avg: ' . implode('|', $channel->getAverage());
		echo '<br />';
	}
}

echo '<pre>';
var_dump(Database::getConnection());
echo '</pre>';

/*$meter = current(Channel::getByFilter(array('id' => 19)));

$start = microtime(true);
$readings = $meter->getData(0, time(), 'day');
echo microtime(true) - $start;

echo '<br /><table>';
foreach ($readings as $i => $reading) {
	echo '<tr><td>' . ($i + 1) . '</td><td>' . date('l jS \of F Y h:i:s A', $reading['timestamp']) . '</td><td>' . $reading['value'] . '</td><td>' . $reading['count'] . '</td></tr>';
}
echo '</table>';

$max = $meter->getAverage();
echo 'Maximal value: ' . date('l jS \of F Y h:i:s A', $max['timestamp']) . ' (' . $max['value'] . ')';*/

?>