<?php
/**
 * Capability tests
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
 */

namespace Tests;

class CapabilitiesTest extends Middleware
{
	function testExistence() {
		$this->assertNotNull($this->getJson('/capabilities.json')->capabilities);
		$this->assertInternalType('object', $this->getJson('/capabilities.json')->capabilities);
	}
}

?>
