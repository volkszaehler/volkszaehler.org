<?php
/**
 * Basic test functionality
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;
use Proxy\Adapter\Guzzle\GuzzleAdapter;

use Volkszaehler\Router;

abstract class Middleware extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var Volkszaehler\Router
	 */
	static $app;

	/**
	 * @var Proxy\Adapter\Guzzle\GuzzleAdapter
	 */
	static $adapter;

	/**
	 * Initialize router
	 */
	static function setupBeforeClass() {
		parent::setupBeforeClass();

		if (testAdapter == 'HTTP') {
			// echo("Test using HTTP adapter\n");
			static::$adapter = new GuzzleAdapter(new Client());
		}
		// cache entity manager
		else if (null == self::$app) {
			// echo("Test using built-in Router\n");
			self::$app = new Router();
		}
	}

	/**
	 * Execute barebones JSON middleware request
	 */
	protected static function executeRequest(Request $request) {
		$json = false;

		if (testAdapter == 'HTTP') {
			$uri = str_replace('http://localhost', testHttpUri, $request->getUri());
			$response = static::$adapter->send($request, $uri);
		}
		else {
			$response = self::$app->handle($request);
		}

		if ($response->headers->get('Content-Type') == 'application/json') {
			try {
				return json_decode($response->getContent());
			}
			catch (\Exception $e) {}
		}

		return $response;
	}

	/**
	 * Execute JSON middleware request and validate result for
	 * - no exception ($hasException = false or omitted)
	 * - has exception ($hasException = true or string)
	 * - has exception with specific message ($hasException = message string)
	 */
	protected function getJson($url, $parameters = array(), $method = 'GET', $hasException = false) {
		if ($url instanceof Request) {
			$request = $url;
		}
		else {
			$request = Request::create($url, $method, $parameters);
		}

		$json = self::executeRequest($request);
		if (!$json) {
			$this->fail('Expected JSON got nothing');
		}

		if ($json instanceof Response) {
			$this->fail('Expected JSON got ' . print_r($json->getContent(), true));
		}

		if ($hasException) {
			if ($this->assertTrue(isset($json->exception), 'Expected <exception> got none.')) {
				if (is_string($hasException)) {
					$this->assertTrue($json->exception->message == $hasException);
				}
			}
		}
		else {
			$this->assertFalse(isset($json->exception),
				'Expected no <exception> got ' .
				(isset($json->exception) ? print_r($json->exception, true) : '') . '.');
		}

		$this->json = $json;
		return $json;
	}
}

?>
