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
	function testMiddlewareAvailablePHP() {
		// test MW access
		$this->getJson(self::getMwUrl('.php'), 'Missing format');
	}	
	function testMiddlewareAvailableRewrite() {
		// test Apache Rewrite
		$this->getJson(self::$mw, 'Missing format');
	}
}

?>
