<?php
/**
 * Test harness
 *
 * @package Test
 * @author Andreas Goetz <cpuidle@gmx.de>
 */

require_once('simpletest/autorun.php');

SimpleTest::prefer(new TextReporter());

class Test extends TestSuite { 

	function __construct() {
		parent::__construct();

		// connectivity
		$this->addFile('MiddlewareTest.php');

		// entities
		$this->addFile('ChannelTest.php');
		$this->addFile('GroupTest.php');

		// meters
		$this->addFile('DataMeterTest.php');
		$this->addFile('DataCounterTest.php');
		$this->addFile('DataSensorTest.php');
	} 
} 

?>
