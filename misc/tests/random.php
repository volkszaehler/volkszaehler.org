<?php
/**
 * Some tests for our Random, Token & UUID classes
 *
 * @package tests
 * @copyright Copyright (c) 2011, The volkszaehler.org project
 * @license http://www.gnu.org/licenses/gpl.txt GNU Public License
 * @author Steffen Vogel <info@steffenvogel.de>
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

include '../lib/Volkszaehler/Util/Random.php';
include '../lib/Volkszaehler/Util/UUID.php';

use Volkszaehler\Model;
use Volkszaehler\Util;

$chars = array(
		'0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
		'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'v', 'u', 'w', 'x', 'y', 'z',
		'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'V', 'U', 'W', 'X', 'Y', 'Z'
	);

?>
<h4>PRNG tests</h4>

<p>PNRG generator: <?= Util\Random::init() ?></p>

<pre>

<?php
echo 'Numbers: ' . implode(',', Util\Random::getNumbers(0, 15, 10)) . PHP_EOL;
echo 'String: ' . Util\Random::getString($chars, 100) . PHP_EOL;
echo 'Bytes: ' . Util\Random::getBytes(100) . PHP_EOL;
?>
</pre>
<h4>UUId tests</h4>
<pre>
<?php
for ($i = 0; $i < 100; $i++) {
	echo Util\UUID::mint(4) . PHP_EOL;
}
?>
</pre>
