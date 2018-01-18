<?php
/**
 * Firewall tests
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
 */

namespace Tests;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FirewallTest extends Middleware
{
	const LOCALHOST = '127.0.0.1';
	const LOCALNET	= '192.168.0.1';
	const REMOTE_IP = '8.8.8.8';

	public static function createRemoteRequest($url, $method = 'GET', $ip = self::REMOTE_IP, $content = null, $headers = null) {
		$server = array(
			'REMOTE_ADDR' => $ip
		);

		// httpd adapter acts as proxy- set forwarded header
		if (testAdapter == 'HTTP') {
			// set both headers for time being
			$server['HTTP_X_FORWARDED_FOR'] = $ip;
			$server['HTTP_FORWARDED'] = sprintf("for=%s", $ip);
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

	protected function assertRequestResult($result, $url, $method = 'GET', $ip = self::REMOTE_IP, $content = null, $headers = null) {
		$response = self::executeRequest(self::createRemoteRequest(
			$url,
			$method,
			$ip,
			$content,
			$headers
		));
		$this->assertEquals($result, $response->getStatusCode());
		return $response;
	}

	public function testLocalhostAllowed() {
		$this->assertRequestResult(Response::HTTP_OK,
			'/capabilities.json',
			'GET',
			self::LOCALHOST
		);
	}

	public function testLocalIpAllowed() {
		$this->assertRequestResult(Response::HTTP_OK,
			'/capabilities.json',
			'GET',
			self::LOCALNET
		);
	}

	public function testRemoteIpGetAllowed() {
		$this->assertRequestResult(Response::HTTP_OK,
			'/capabilities.json',
			'GET'
		);
	}

	public function testRemoteIpPostPatchDeleteDenied() {
		$this->assertRequestResult(Response::HTTP_UNAUTHORIZED,
			'/capabilities.json',
			'POST'
		);

		$this->assertRequestResult(Response::HTTP_UNAUTHORIZED,
			'/capabilities.json',
			'PATCH'
		);

		$this->assertRequestResult(Response::HTTP_UNAUTHORIZED,
			'/capabilities.json',
			'DELETE'
		);
	}

	public function testRemoteIpAuthAllowed() {
		// POST allowed but fails without token - therefore 403 FORBIDDEN
		$response = $this->assertRequestResult(Response::HTTP_FORBIDDEN,
			'/auth.json',
			'POST'
		);
		$this->assertRegExp("/Invalid token request/", $response->getContent());
	}
}

?>
