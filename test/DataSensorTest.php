<?php
/**
 * Meter tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

require_once('DataContext.php');

class DataSensorTest extends DataContext
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
