<?php
/**
 * @package default
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @license http://www.gnu.org/licenses/gpl.txt GNU Public License
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

namespace Volkszaehler;

/**
 * @package default
 * @author Steffen Vogel <info@steffenvogel.de>
 */
use Volkszaehler\Util;

use Doctrine\ORM;

class Router {
	protected $format;
	protected $controller;
	protected $identifier;
	protected $action;

	protected static $controllerMapping = array(
		'channels'		=> 'Volkszaehler\Controller\ChannelController',
		'groups'			=> 'Volkszaehler\Controller\GroupController',
		'tokens'			=> 'Volkszaehler\Controller\TokenController',
		'capabilities'	=> 'Volkszaehler\Controller\CapabilitiesController'
	);

	/**
	 * Constructor
	 *
	 * @param ORM\EntityManager $em
	 */
	public function __construct(ORM\EntityManager $em) {
		$this->parsePathInfo();
	}

	/**
	 * @todo add alternative url schema without PATH_INFO
	 */
	protected function parsePathInfo() {
		// Request: http://sub.domain.local/vz/backend/channel/550e8400-e29b-11d4-a716-446655440000/edit.json?title=New Title
		// PATH_INFO: /channel/550e8400-e29b-11d4-a716-446655440000/edit.json
		$pi = $this->getPathInfo();

		if ($pi) {
			$pi = substr($pi, 1);
			$pie = explode('/', $pi);
			$i = 0;

			if (isset($pie[$i]) && array_key_exists($pie[$i], self::$controllerMapping)) {
				$this->controller = self::$controllerMapping[$pie[$i]];
				$i++;
			}

			if (isset($pie[$i]) && preg_match('/[a-f0-9\-]{3,36}/', $pie[$i])) {
				$this->identifier = $pie[$i];
			}
			$i++;

			if (isset($pie[$i]) && strpos($pie[$i], '.') !== FALSE) {
				list($this->action, $this->format) = explode('.', $pie[$i]);
			}
			elseif (isset($pie[$i])) {
				$this->action = $pie[$i];
			}
		}
		else {
			throw new \Exception('no CGI PATH_INFO envvar found');
		}
	}

	/**
	 * Get CGI environmental var PATH_INFO from webserver
	 *
	 * @return string
	 */
	protected static function getPathInfo() {
		if (isset($_SERVER['PATH_INFO'])) {
			return $_SERVER['PATH_INFO'];
		}
		elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
			return $_SERVER['ORIG_PATH_INFO'];
		}
		elseif (strlen($_SERVER['PHP_SELF']) > strlen($_SERVER['SCRIPT_NAME'])) {
			return substr($_SERVER['PHP_SELF'], strlen($_SERVER['SCRIPT_NAME']));
		}
		else {
			return FALSE;
		}
	}

	/*
	 * Getter & setter
	 */
	public function getFormat() { return $this->format; }
	public function getController() { return $this->controller; }
	public function getIdentifier() { return $this->identifier; }
	public function getAction() { return $this->action; }
}

?>
