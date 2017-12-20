<?php
/**
 * Capability tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2017, The volkszaehler.org project
 * @package tests
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
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
