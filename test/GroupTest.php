<?php
/**
 * Entity tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

require_once('Middleware.php');

class GroupTest extends Middleware
{
	function __construct() {
		parent::__construct();
		$this->context = self::$mw . 'group';
	}

	function testGroup() {
		$this->assertTrue(false, "Not Implemented");
	}
}

?>
