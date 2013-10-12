<?php
/**
 * Test harness
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

require_once('simpletest/autorun.php');

class AllFileTests extends TestSuite { 

	function __construct() {
		parent::__construct();

		// connectivity
		$this->addFile('MiddlewareTest.php');

		// entities
		$this->addFile('ChannelTest.php');
		// not implemented
		// $this->addFile('GroupTest.php');

		// meters
		$this->addFile('DataMeterTest.php');
		$this->addFile('DataCounterTest.php');
		$this->addFile('DataSensorTest.php');
	} 
} 

?>
