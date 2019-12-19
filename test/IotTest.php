<?php
/**
 * IoT tests
 *
 * @author Andreas Götz <cpuidle@gmx.de>
 * @copyright Copyright (c) 2011-2018, The volkszaehler.org project
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
 */

namespace Tests;

use Webpatser\Uuid\Uuid as UUID;

class IotTest extends Middleware
{
	protected $uuid;

	function testRetrievel() {
		$secret = str_replace('-', '', UUID::generate());
		$this->getJson('/channel.json', array(
			'operation' => 'add',
			'title' => 'Power',
			'type' => 'power',
			'resolution' => 1,
			'owner' => $secret
		), 'GET');
		$this->assertTrue(isset($this->json->entity), "Expected <entity> got none");
		$this->assertTrue(isset($this->json->entity->uuid));

		$uuid = $this->json->entity->uuid;

		// retrieve by secret
		$this->getJson('/iot/' . $secret . '.json');
		$this->assertTrue(isset($this->json->entities), "Expected <entities> got none");
		$this->assertEquals(1, count($this->json->entities));
		$this->assertEquals($uuid, $this->json->entities[0]->uuid);
	}
}

?>
