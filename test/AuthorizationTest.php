<?php
/**
 * Authorization tests
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
 */

namespace Tests;

use Volkszaehler\Util;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizationTest extends Middleware
{
	static $authtoken;

	public function testAuthWithoutCredentialsDenied() {
		// no credentials - 403 FORBIDDEN
		$request = FirewallTest::createRemoteRequest(
			'/auth.json',
			'POST',
			FirewallTest::REMOTE_IP
		);
		$response = self::executeRequest($request);
		$this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
		$this->assertRegExp("/Invalid token request/", $response->getContent());
	}

	public function testAuthWithInvalidCredentialsDenied() {
		$this->assertTrue(null !== Util\Configuration::read('authorization.secretkey'), 'Authorization secret key missing');

		// invalid credentials - 403 FORBIDDEN
		$request = FirewallTest::createRemoteRequest(
			'/auth.json',
			'POST',
			FirewallTest::REMOTE_IP,
			json_encode(array(
				'username' => 'foo',
				'password' => 'foo'
			)),
			array(
				'CONTENT_TYPE' => 'application/json'
			)
		);
		$response = self::executeRequest($request);
		$this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
		$this->assertRegExp("/Invalid user credentials/", $response->getContent());
	}

	/**
	 * @depends testAuthWithInvalidCredentialsDenied
	 */
	public function testAuthWithValidCredentialsAccepted() {
		// valid credentials - 200 OK
		$request = FirewallTest::createRemoteRequest(
			'/auth.json',
			'POST',
			FirewallTest::REMOTE_IP,
			json_encode(array(
				'username' => 'user',
				'password' => 'pass'
			)),
			array(
				'CONTENT_TYPE' => 'application/json'
			)
		);
		$response = self::executeRequest($request);
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

		$json = $response->getContent();
		try {
			$json = json_decode($json);
		}
		catch (\Exception $e) {
			$this->assertNull($e); // fail
		}

		// get token from response
		$this->assertTrue(isset($json->authtoken)); // fail
		self::$authtoken = $json->authtoken;
		$this->assertRegExp("/[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+/", self::$authtoken);
	}

	/**
	 * @depends testAuthWithValidCredentialsAccepted
	 */
	public function testAccessWithValidTokenAccepted() {
		// valid credentials - 200 OK
		$request = FirewallTest::createRemoteRequest(
			'/capabilities.json',
			'GET',
			FirewallTest::REMOTE_IP,
			'',
			array(
				'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authtoken
			)
		);
		$response = self::executeRequest($request);
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
	}
}

?>
