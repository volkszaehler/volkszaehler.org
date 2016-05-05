<?php
/**
 * Meter tests
 *
 * @package Test
 * @author Andreas Götz <cpuidle@gmx.de>
 */

namespace Tests;

use Symfony\Component\HttpFoundation\Request;

class ProtocolTest extends Data
{
	// channel properties
	static $resolution = 100;

	// data properties
	static $ts = 3600000;
	static $value = 1000;

	/**
	 * Create channel
	 */
	static function setupBeforeClass() {
		parent::setupBeforeClass();
		self::$uuid = self::createChannel('Counter', 'electric meter', self::$resolution);
	}

	function testAddTupleGet() {
		// 1 row added
		$this->assertEquals(1, $this->getJson('/data/' . static::$uuid . '.json', array(
			'operation' => 'add',
			'ts' => self::$ts,
			'value' => self::$value
		), 'GET')->rows);
	}

	function testAddTuplePost() {
		// 1 row added
		$this->assertEquals(1, $this->getJson('/data/' . static::$uuid . '.json', array(
			'ts' => ++self::$ts,
			'value' => self::$value
		), 'POST')->rows);
	}

	function testAddMultipleTuplesPost() {
		// multiple INSERT syntax not portable
		if (($db = \Volkszaehler\Util\Configuration::read('db.driver')) === 'pdo_sqlite')
			$this->markTestSkipped('not implemented for ' . $db);

		$data = array(
			array(++self::$ts, self::$value),
			array(++self::$ts, self::$value)
		);

		$request = Request::create('/data/' . static::$uuid . '.json', 'POST',
			array(), array(), array(), array(),
			json_encode($data));
		$request->headers->set('Content-type', 'application/json');

		// 2 rows added
		$this->assertEquals(2, $this->getJson($request)->rows);
	}

	/**
	 * @depends testAddTupleGet
	 */
	function testDuplicate() {
		// INSERT IGNORE syntax not portable
		if (($db = \Volkszaehler\Util\Configuration::read('db.driver')) !== 'pdo_mysql')
			$this->markTestSkipped('not implemented for ' . $db);

		$url = '/data/' . static::$uuid . '.json';

		// insert duplicate value
		$data = array(
			array(self::$ts, self::$value)
		);

		// encode query parameters in url for https://github.com/symfony/symfony/issues/14400
		$request = Request::create($url . '?' . http_build_query(array('options' => 'skipduplicates')), 'POST',
			array(), array(), array(), array(),
			json_encode($data));
		$request->headers->set('Content-type', 'application/json');

		// 0 rows added, no failure
		$this->assertEquals(0, $this->getJson($request)->rows);

		// insert duplicate value: UniqueConstraintViolationException - currently this will close the EntityManager
		$this->assertEquals('UniqueConstraintViolationException', $this->getJson($url, array(
			'operation' => 'add',
			'ts' => self::$ts,
			'value' => self::$value
		), 'GET', true)->exception->type);
	}

	function testJsonException() {
		$response = $this->getResponse('/data/' . static::$uuid . '.json', array(
			'from' => 1,
			'to' => 0
		), 'GET');

		// exception must be HTTP 400
		$this->assertEquals(400, $response->getStatusCode());
		$this->assertEquals('application/json', $response->headers->get('Content-Type'));
	}

	function testJsonP() {
		$response = $this->getResponse('/data/' . static::$uuid . '.json', array(
			'padding' => 'callback'
		), 'GET');

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('application/javascript', $response->headers->get('Content-Type'));
		$this->assertRegExp('/callback\(.*\);/', $response->getContent());
	}

	function testJsonPException() {
		$response = $this->getResponse('/data/' . static::$uuid . '.json', array(
			'padding' => 'callback',
			'from' => 1,
			'to' => 0
		), 'GET');

		// if JsonP response must always be HTTP 200
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('application/javascript', $response->headers->get('Content-Type'));
		$this->assertRegExp('/callback\(.*\);/', $response->getContent());

		$json = (preg_match('/callback\((.*)\);/', $response->getContent(), $matches)) ? json_decode($matches[1]) : null;
		$this->assertNotNull($json, 'Not valid JSON');
	}

	function testDebug() {
		$this->assertNotNull($this->getJson('/data/' . static::$uuid . '.json', array(
			'debug' => 1
		))->debug, 'Missing debug output');
		$this->assertNotNull($this->json->debug->sql, 'Missing debug sql trace');
		$this->assertNotNull($this->json->debug->sql->totalTime, 'Missing debug sql timing');
	}

	function testExceptionDebug() {
		$response = $this->getResponse('/data/' . static::$uuid . '.json', array(
			'from' => 1,
			'to' => 0,
			'debug' => 1
		), 'GET');

		$json = json_decode($response->getContent());
		$this->assertNotNull($json, 'Not valid JSON');

		$this->assertNotNull($json->debug, 'Missing debug output');
	}
}

?>
