<?php
/**
 * Format tests
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
 */

namespace Tests;

use Symfony\Component\HttpFoundation\Request;

class FormatTest extends Data
{
	const INVALID_UUID = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

	static function setupBeforeClass() {
		parent::setupBeforeClass();

		// create channel
		self::$uuid = self::createChannel('Meter', 'power', 1);

		// add data
		self::executeJsonRequest(Request::create('/data/' . self::$uuid . '.json', 'POST',
			array(), array(), array(), array(),
			json_encode(array(
				array(1000000, 1000),
				array(2000000, 2000),
				array(3000000, 1000),
			))
		));
	}

	/**
	 * @group jpgraph
	 */
	function testImage() {
		$response = $this->getResponse(Request::create('/data/' . static::$uuid . '.png', 'GET',
			array('from' => 0, 'to' => 'now')
		));

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('image/png', $response->headers->get('Content-Type'));
	}

	/**
	 * @group jpgraph
	 *
	 * NOTE: this cannot be tested due to JpGraph design issues
	 */
	// function testImageInvalidUuidException() {
	// 	$response = $this->getResponse(Request::create('/data/' . self::INVALID_UUID . '.png', 'GET',
	// 		array('from' => 0, 'to' => 'now')
	// 	));

	// 	$this->assertEquals(200, $response->getStatusCode());
	// 	$this->assertEquals('image/png', $response->headers->get('Content-Type'));
	// }

	function testCSV() {
		$response = $this->getResponse(Request::create('/data/' . static::$uuid . '.csv', 'GET',
			array('from' => 0, 'to' => 'now')
		));

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('text/csv', $response->headers->get('Content-Type'));
		$this->assertContains(static::$uuid, $response->getContent());
	}

	function testCSVInvalidUuidException() {
		$response = $this->getResponse(Request::create('/data/' . self::INVALID_UUID . '.csv', 'GET',
			array('from' => 0, 'to' => 'now')
		));

		$this->assertEquals(400, $response->getStatusCode());
	}

	function testTxt() {
		$response = $this->getResponse(Request::create('/data/' . static::$uuid . '.txt', 'GET',
			array('from' => 0, 'to' => 'now')
		));

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertContains('text/plain', $response->headers->get('Content-Type'));
		$this->assertContains('W', $response->getContent());
	}
}

?>
