<?php
/**
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @package default
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
 */
/*
 * This file is part of volkzaehler.org
 *
 * volkzaehler.org is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * volkzaehler.org is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Volkszaehler\View;

use Volkszaehler\View\HTTP;
use Volkszaehler\Util;
use Volkszaehler\Model;

/**
 * JSON view
 *
 * @package default
 * @author Steffen Vogel <info@steffenvogel.de>
 */
class JSON extends View {
	protected $json;

	protected $padding = FALSE;

	/**
	 * constructor
	 */
	public function __construct(HTTP\Request $request, HTTP\Response $response) {
		parent::__construct($request, $response);

		$this->json = new Util\JSON();

		$this->json['source'] = 'volkszaehler.org';
		$this->json['version'] = VZ_VERSION;

		$this->response->setHeader('Content-type', 'application/json');

		$this->padding = $this->request->getParameter('padding');
	}

	public function setPadding($padding) { $this->padding = $padding; }

	public function addChannel(Model\Channel $channel, array $data = NULL) {
		$jsonChannel['uuid'] = (string) $channel->getUuid();


		foreach ($channel->getProperties() as $property) {
			$jsonChannel[$property->getName()] = $property->getValue();
		}

		if (isset($data)) {
			$jsonChannel['data'] = self::convertData($data);
		}

		$this->json['channels'][] = $jsonChannel;
	}

	public function addGroup(Model\Group $group, $recursive = FALSE) {
		$this->json['groups'][] = self::convertJson($group, $recursive);
	}

	public function addDebug(Util\Debug $debug) {
		$this->json['debug'] = array(
			'time' => $debug->getExecutionTime(),
			'messages' => $debug->getMessages(),
			'database' => array(
				'driver' => Util\Configuration::read('db.driver'),
				'queries' => $debug->getQueries()
			)
		);
	}

	protected function addException(\Exception $exception) {
		$this->json['exception'] = array(
			'type' => get_class($exception),
			'message' => $exception->getMessage(),
			'code' => $exception->getCode(),
			'file' => $exception->getFile(),
			'line' => $exception->getLine(),
			'trace' => $exception->getTrace()
		);
	}

	protected static function convertGroup(Model\Group $group, $recursive = FALSE) {
		$jsonGroup = array();

		$jsonGroup['uuid'] = (string) $group->getUuid();
		$jsonGroup['name'] = $group->getName();
		$jsonGroup['description'] = $group->getDescription();
		$jsonGroup['channels'] = array();

		foreach ($group->getChannels() as $channel) {
			$jsonGroup['channels'][] = (string) $channel->getUuid();
		}

		if ($recursive) {
			$jsonGroup['children'] = array();

			foreach ($group->getChildren() as $subGroup) {
				$jsonGroup['children'][] = $this->toJson($subGroup, $recursive);	// recursion
			}
		}

		return $jsonGroup;
	}

	protected static function convertData($data) {
		$jsonData = array();

		foreach ($data as $reading) {
			$jsonData[] = array(
				(int) $reading[0],
				(float) round($reading[1], View::PRECISSION),
				(int) $reading[2]
			);
		}

		return $jsonData;
	}

	public function renderResponse() {
		$json = $this->json->encode((Util\Debug::isActivated()) ? JSON_PRETTY : 0);

		if ($this->padding) {
			$json = 'if (self.' . $this->padding . ') { ' . $this->padding  . '(' . $json . '); }';
		}

		echo $json;
	}
}

?>