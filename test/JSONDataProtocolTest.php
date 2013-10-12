<?php
/**
 * Entity tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

require_once('DataBaseFunctions.php');

class DataMeterTest extends DataBaseFunctions
{
	// channel properties
	protected $title = 'Meter';
	protected $type = 'power';
	protected $resolution = 100;

	// data properties
	protected $ts1 = 100000000;
	protected $ts2 = 107200000;	// +2hr
	protected $ts3 = 110800000; // +3hr

	protected $value1 = 1000;
	protected $value2 = 1000;
	protected $value3 = 2000;
}

?>
