<?php
/**
 * JSON view
 *
 * also used for data
 *
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @author Steffen Vogel <info@steffenvogel.de>
 * @license http://www.opensource.org/licenses/gpl-license.php GNU Public License
 *
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

namespace Volkszaehler\View\JSON;

use Volkszaehler\View\HTTP;

use Volkszaehler\View;
use Volkszaehler\Util;

abstract class JSON extends View\View {
	protected $json = array();

	/**
	 * constructor
	 */
	public function __construct(HTTP\Request $request, HTTP\Response $response) {
		parent::__construct($request, $response);

		$this->json['source'] = 'volkszaehler.org';
		$this->json['version'] = \Volkszaehler\VERSION;

		$this->response->setHeader('Content-type', 'application/json');
	}

	public function render() {
		$json = json_encode($this->json);

		if (Util\Debug::isActivated()) {
			$json = self::format($json);
		}

		echo $json;

		parent::render();
	}

	protected static function format($json) {
		$formatted = '';
		$indentLevel = 0;
		$inString = FALSE;

		$len = strlen($json);
		for($c = 0; $c < $len; $c++) {
			$char = $json[$c];
			switch($char) {
				case '{':
				case '[':
					$formatted .= $char;
					if (!$inString && (ord($json[$c+1]) != ord($char)+2)) {
						$indentLevel++;
						$formatted .= "\n" . str_repeat("\t", $indentLevel);
					}
					break;
				case '}':
				case ']':
					if (!$inString && (ord($json[$c-1]) != ord($char)-2)) {
						$indentLevel--;
						$formatted .= "\n" . str_repeat("\t", $indentLevel);
					}
					$formatted .= $char;
					break;
				case ',':
					$formatted .= $char;
					if (!$inString) {
						$formatted .= "\n" . str_repeat("\t", $indentLevel);
					}
					break;
				case ':':
					$formatted .= $char;
					if (!$inString) {
						$formatted .= ' ';
					}
					break;
				case '"':
					if ($c > 0 && $json[$c-1] != '\\') {
						$inString = !$inString;
					}
				default:
					$formatted .= $char;
					break;
			}
		}

		return $formatted;
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

	public function addException(\Exception $exception) {
		$this->json['exception'] = array(
			'type' => get_class($exception),
			'message' => $exception->getMessage(),
			'code' => $exception->getCode(),
			'file' => $exception->getFile(),
			'line' => $exception->getLine(),
			'trace' => $exception->getTrace()
		);
	}
}

?>