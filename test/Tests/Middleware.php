<?php
/**
 * Basic test functionality
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

abstract class Middleware extends \PHPUnit_Framework_TestCase
{
	// middleware url (basis for parsing)
	static $mw = 'http://localhost/volkszaehler/middleware/';

	// all basic members static for consistency
	static $context;			// context base url
	static $response;			// request response
	static $url;				// request url

	protected $json;			// decoded json response

	/**
	 * Setup evironment for executing 'fake' middleware call
	 */
	static function callMiddleware($url) {
		self::$url = $url;

		$request = new \Wrapper\View\HTTP\Request($url);
		$response = new \Wrapper\View\HTTP\Response();

		$r = new \Wrapper\Router($request, $response);
		$r->run();
		$r->view->send();

		self::$response = $response->getResponseContents();
		return self::$response;
	}

	/**
	 * Execute barebones JSON middleware request
	 */
	static function getJsonRaw($url) {
		$response = self::callMiddleware($url);
		$json = json_decode($response);
		return $json;
	}

	/**
	 * Execute JSON middleware request and validate result for
	 * - no exception ($hasException = false or omitted)
	 * - has exception ($hasException = true or string)
	 * - has exception with specific message ($hasException = message string)
	 */
	protected function getJson($url, $hasException = false) {
		$this->json = self::getJsonRaw($url);
		$this->assertTrue($this->json !== null, "Expected JSON got " . print_r(self::$response,1));

		if (isset($this->json)) {
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

		return $this->json;
	}
}

?>
