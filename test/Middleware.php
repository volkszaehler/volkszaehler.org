<?php
/**
 * Basic test functionality
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Proxy\Adapter\Guzzle\GuzzleAdapter;
use Symfony\Bridge\PsrHttpMessage\Factory;
use Zend\Diactoros\Uri;

use Volkszaehler\Router;

abstract class Middleware extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var Volkszaehler\Router
	 */
	static $app;

	static $httpFoundationFactory;
	static $psrFoundationFactory;

	/**
	 * @var Proxy\Adapter\Guzzle\GuzzleAdapter
	 */
	static $adapter;

	/**
	 * @var Request memory consumption
	 */
	static $mem;

	/**
	 * @var Symfony\Component\HttpFoundation\Request
	 */
	static $request;

	/**
	 * @var Debug setting
	 */
	static $debug = false;

	/**
	 * Initialize router
	 */
	static function setupBeforeClass() {
		parent::setupBeforeClass();

		if (testAdapter == 'HTTP') {
			// echo("Test using HTTP adapter\n");
			static::$adapter = new GuzzleAdapter(new Client());
			static::$httpFoundationFactory = new Factory\HttpFoundationFactory();
			static::$psrFoundationFactory = new Factory\DiactorosFactory();
		}
		// cache entity manager
		else if (null == self::$app) {
			// echo("Test using built-in Router\n");
			self::$app = new Router();
		}
	}

	/**
	 * Return request memory usage
	 */
	protected static function memUsage() {
		return self::$mem / 1024 / 1024;
	}

	/**
	 * Send request via Guzzle
	 * @param Request
	 * @return Response
	 */
	protected static function send(Request $request) {
		$psrRequest = static::$psrFoundationFactory->createRequest($request);

		// map uri to httpd
		$uri = str_replace('http://localhost', testHttpUri, $request->getUri());
		$psrRequest = $psrRequest->withUri(new Uri($uri));

		try {
			$psrResponse = static::$adapter->send($psrRequest);
		}
		catch (GuzzleException $e) {
			$psrResponse = $e->getResponse();
			if (null === $psrResponse) {
				var_dump($e);
			}
		}
		finally {
			$response = static::$httpFoundationFactory->createResponse($psrResponse);
		}

		return $response;
	}

	/**
	 * Execute barebones JSON middleware request
	 * @param Request
	 * @return Response
	 */
	protected static function executeRequest(Request $request) {
		if (testAdapter == 'HTTP') {
			$response = self::send($request);
			self::$mem = 0;
		}
		else {
			self::$mem = memory_get_peak_usage();
			$response = self::$app->handle($request);
			self::$mem = memory_get_peak_usage() - self::$mem;
		}

		if (self::$debug) {
			echo("\nRequest: " . ($method = $request->getMethod()) . ' ' . $request->getUri() . "\n");
			if ($method == 'POST') {
				echo("Content: " . $request->getContent() . "\n");
			}
		}

		// always provide normal Response to test cases
		if ($response instanceof StreamedResponse) {
			ob_start();
			$response->sendContent();
			$content = ob_get_contents();
			ob_end_clean();

			$response = Response::create($content, $response->getStatusCode(), $response->headers->all());
		}

		if (self::$debug) {
			echo("\nResponse: ".$response."\n");
		}

		return $response;
	}

	/**
	 * Execute and parse barebones JSON middleware request
	 * @param Request
	 * @return array
	 */
	protected static function executeJsonRequest(Request $request) {
		$response = self::executeRequest($request);
		$json = json_decode($response->getContent());

		return $json;
	}

	/**
	 * Build HTTP request and get response
	 */
	protected static function getResponse($request, $parameters = array(), $method = 'GET') {
		if (!$request instanceof Request) {
			$request = Request::create($request, $method, $parameters);
		}

		return self::executeRequest(self::$request = $request);
	}

	/**
	 * Execute JSON middleware request and validate result for
	 * - no exception ($hasException = false or omitted)
	 * - has exception ($hasException = true or string)
	 * - has exception with specific message ($hasException = message string)
	 */
	protected function getJson($request, $parameters = array(), $method = 'GET', $hasException = false) {
		$response = self::getResponse($request, $parameters, $method);

		if (!$response) {
			$this->fail('Expected response got nothing');
		}

		$this->assertEquals('application/json', $response->headers->get('Content-Type'), 'Expected JSON response got ' . print_r($response->getContent(), true));

		if (null === ($this->json = json_decode($response->getContent()))) {
			$this->fail("Failed to decode JSON for " . self::$request->getUri() . "\n" . $response->getContent());
		}

		if ($hasException) {
			if ($this->assertTrue(isset($this->json->exception), 'Expected <exception> got none.')) {
				if (is_string($hasException)) {
					$this->assertTrue($this->json->exception->message == $hasException);
				}
			}
		}
		else {
			$this->assertFalse(isset($this->json->exception),
				'Expected no <exception> got ' .
				(isset($this->json->exception) ? print_r($this->json->exception, true) : '') . '.');
		}

		return $this->json;
	}
}

?>
