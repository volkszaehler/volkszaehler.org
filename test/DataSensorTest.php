<?php
/**
 * Meter tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

require_once('MiddlewareTest.php');
require_once('DataBaseFunctions.php');

class DataSensorTest extends DataBaseFunctions
{
	// channel properties
	protected $title = 'Sensor';
	protected $type = 'powersensor';
	protected $resolution = NULL;

	function testSensor() {
		$this->assertTrue(false, "Not Implemented");
	}
}

?>
