<?php
/**
 * Basic test functionality
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
 */

namespace Tests;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\Uri;

use Volkszaehler\Router;

abstract class Middleware extends \PHPUnit\Framework\TestCase
{
	/**
	 * @var \Volkszaehler\Router
	 */
	static $app;

	static $httpFoundationFactory;
	static $psrFoundationFactory;

	/**
	 * @var \GuzzleHttp\Client
	 */
	static $client;

	/**
	 * @var int memory consumption
	 */
	static $mem;

	/**
	 * @var Request
	 */
	static $request;

	/**
	 * @var bool setting
	 */
	static $debug = false;

	/**
	 * @var \stdClass
	 */
	protected $json;

	/**
	 * Initialize router
	 */
	static function setupBeforeClass() {
		parent::setupBeforeClass();

		if (testAdapter == 'HTTP') {
			static::$client = new Client();
			static::$httpFoundationFactory = new HttpFoundationFactory();
			static::$psrFoundationFactory = new RequestFactory();
		}
		// cache entity manager
		else if (null == self::$app) {
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
	 * @param Request $request
	 * @return Response
	 */
	protected static function send(Request $request) {
		$psrRequest = static::$psrFoundationFactory->createRequest($request);

		// map uri to httpd
		$uri = str_replace('http://localhost', testHttpUri, $request->getUri());
		$psrRequest = $psrRequest->withUri(new Uri($uri));

		try {
			$psrResponse = static::$client->send($psrRequest);
		}
		catch (RequestException $e) {
			$psrResponse = $e->hasResponse() ? $e->getResponse() : null;
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
	 * @param Request $request
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
	 * @param Request $request
	 * @return \stdClass
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
			// print_r($request);
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
			$this->assertTrue(isset($this->json->exception), 'Expected <exception> got none.');
			if (is_string($hasException)) {
				$this->assertEquals($hasException, $this->json->exception->message);
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
