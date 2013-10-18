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
	/**
	 * Initialize context
	 */
	static function setupBeforeClass() {
		parent::setupBeforeClass();
		self::$context = self::$mw . 'group';
	}

	function testGroup() {
		echo('Not Implemented');
	}
}

?>
