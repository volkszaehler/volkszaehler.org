<?php
/**
 * Firewal tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FirewallTest extends Middleware
{
	const REMOTE_IP = '8.8.8.8';

	public static function createRemoteRequest($url, $method = 'GET', $ip = self::REMOTE_IP, $content = null, $headers = null) {
		$server = array(
			'REMOTE_ADDR' => $ip
		);

		// httpd adapter acts as proxy- set forwarded header
		if (testAdapter == 'HTTP') {
			$server['HTTP_X_FORWARDED_FOR'] = $ip;
		}

		if (isset($headers)) {
			$server = array_merge($server, $headers);
		}

		return Request::create($url,
			$method,	// method
			array(),	// parameters
			array(),	// cookies
			array(),	// files
			$server,	// server
			$content
		);
	}

	public function testLocalhostAllowed() {
		$response = self::executeRequest(self::createRemoteRequest(
			'/capabilities.json',
			'GET',
			'127.0.0.1'
		));
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
	}

	public function testLocalIpAllowed() {
		$response = self::executeRequest(self::createRemoteRequest(
			'/capabilities.json',
			'GET',
			'192.168.0.1'
		));
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
	}

	public function testRemoteIpDenied() {
		$response = self::executeRequest(self::createRemoteRequest('/capabilities.json'));
		$this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
	}

	public function testRemoteIpAuthAllowed() {
		// GET not authorized - 401 UNAUTHORIZED
		$response = self::executeRequest(self::createRemoteRequest('/auth.json'));
		$this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

		// POST allowed but fails without token - therefore 403 FORBIDDEN
		$response = self::executeRequest(self::createRemoteRequest(
			'/auth.json',
			'POST'
		));
		$this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
		$this->assertRegExp("/Invalid token request/", $response->getContent());
	}
}

?>
