<?php
/**
 * Basic test functionality
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

require_once('Middleware.php');

class MiddlewareTest extends Middleware  
{
	function testMiddlewareAvailable() {
		// test MW access
		$this->getJson(self::getMwUrl('.php'), 'Missing format');
		// test Apache Rewrite
		$this->getJson(self::$mw, 'Missing format');
	}
}

?>
