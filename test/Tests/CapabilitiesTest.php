<?php
/**
 * Capability tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
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
