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

	function testJsonP() {
		$response = $this->getJson('/data/' . static::$uuid . '.json', array(
			'padding' => 'callback'
		), 'GET');

		$this->assertEquals('application/javascript', $response->headers->get('Content-Type'));
		$this->assertRegExp('/callback\(.*\);/', $response->getContent());
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

	function testDuplicate() {
		$url = '/data/' . static::$uuid . '.json';

		// insert duplicate value
		$data = array(
			array(self::$ts, self::$value)
		);

		// @TODO: support GET as well
		$request = Request::create($url, 'POST',
			array('options' => 'skipduplicates'), array(), array(), array(),
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
}

?>
