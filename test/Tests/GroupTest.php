<?php
/**
 * Entity tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

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
