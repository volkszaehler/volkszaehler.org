<?php
/**
 * Basic test functionality
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

require_once('simpletest/autorun.php'); 
require_once('simpletest/browser.php'); 
// require_once('simpletest/web_tester.php');

class MiddlewareTest extends UnitTestCase  
{
	public static $mw = '';				// middleware url (auto-detected or manually set)
	private static $mwTested = false;	// helper to test middleware reachable only once

	protected $context;					// context base url

	protected $url;						// request url
	protected $json;					// decoded json response

	/**
	 * @todo support non-HTTP traffic
	 */
	function __construct() {
		// auto-discover middleware url if not declared
		if (empty(self::$mw)) {
			self::$mw = self::getMwUrl();
		}

		$this->browser = new SimpleBrowser();
	}

	/**
	 * auto-discover middleware url from request
	 */
	static function getMwUrl($postfix = '') {
		$host = $_SERVER['HTTP_HOST'];
		$uri  = $_SERVER['REQUEST_URI'];

		$uricomponents = preg_split('/\//', $uri);
		while (array_pop($uricomponents) !== 'test');

		$mw = 'http://' . $host . (isset($_SERVER['HTTP_PORT']) ? ':'.$_SERVER['HTTP_PORT'] : '');
		$mw .= join('/', $uricomponents) . '/middleware' . $postfix . '/';

		return($mw);
	}

	/**
	 * Execute JSON middleware request and validate result for 
	 * - no exception ($hasException = false or omitted)
	 * - has exception ($hasException = true or string)
	 * - has exception with specific message ($hasException = message string)
	 */
	public function _getJson($url) {
		$this->url = $url;
		return ($this->json = json_decode($this->browser->get($url)));
	}

	protected function getJson($url, $hasException = false) {
		if ($this->assertTrue($this->_getJson($url) !== null, "Expected JSON got " . print_r($this->browser->getContent(),1))) {
			if ($hasException) {
				if ($this->assertTrue(isset($this->json->exception), 'Expected <exception> got none.')) {
					if (is_string($hasException)) {
						$this->assertTrue($this->json->exception->message == $hasException);
					}
				}
			} else {
				$this->assertFalse(isset($this->json->exception), 
					'Expected no <exception> got ' . 
					(isset($this->json->exception) ? print_r($this->json->exception,1) : '') . '.');
			}
		}
	}

	function testMiddlewareAvailable() {
		// test mw access only once
		if (!self::$mwTested) {
			// test MW access
			$this->getJson(self::getMwUrl('.php'), 'Missing format');
			// test Apache Rewrite
			$this->getJson(self::$mw, 'Missing format');

			self::$mwTested = true;
		}
	}
}

?>
